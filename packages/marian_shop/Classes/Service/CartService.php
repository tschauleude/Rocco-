<?php

declare(strict_types=1);

namespace Marian\Shop\Service;

use Marian\Shop\Domain\Cart\Cart;
use Marian\Shop\Domain\Cart\CartItem;
use Marian\Shop\Domain\Repository\ProductRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Hält den Warenkorb in der Frontend-Sitzung.
 *
 * In der Sitzung stehen ausschließlich Artikel-ID, Ausführung und Menge.
 * Preise werden bei jedem Aufruf frisch aus dem Katalog geholt – damit sind
 * sie weder manipulierbar noch veraltet, wenn sich zwischendurch etwas ändert.
 *
 * Der Request wird übergeben statt aus Globals gezogen: so bleibt der Dienst
 * testbar und macht nachvollziehbar, woher die Sitzung kommt.
 */
class CartService
{
    private const SESSION_KEY = 'marianshop_cart';

    /**
     * Obergrenze je Position, unabhängig vom Artikel. Schützt vor
     * Tippfehlern und davor, dass jemand den Bestand mit einem Klick blockiert.
     */
    private const MAX_QUANTITY = 99;

    public function __construct(private readonly ProductRepository $productRepository) {}

    public function getCart(ServerRequestInterface $request): Cart
    {
        $rows = $this->readSession($request);
        if ($rows === []) {
            return new Cart();
        }

        $products = $this->productRepository->findByUids(array_column($rows, 'product'));

        $items = [];
        $changed = false;
        foreach ($rows as $row) {
            $product = $products[$row['product']] ?? null;
            if ($product === null) {
                // Artikel gelöscht oder deaktiviert: Position still entfernen.
                $changed = true;
                continue;
            }

            $variant = $row['variant'] > 0 ? $product->findVariant($row['variant']) : null;
            if ($row['variant'] > 0 && $variant === null) {
                $changed = true;
                continue;
            }

            $item = new CartItem($product, $variant, $row['quantity']);
            $items[$item->getKey()] = $item;
        }

        if ($changed) {
            $this->writeSession($request, $this->toRows($items));
        }

        return new Cart($items);
    }

    /**
     * Legt einen Artikel in den Warenkorb und gibt die tatsächlich
     * hinzugefügte Menge zurück – sie kann durch den Bestand begrenzt sein.
     */
    public function add(ServerRequestInterface $request, int $productUid, int $variantUid, int $quantity): int
    {
        $quantity = max(1, $quantity);
        $product = $this->productRepository->findByUids([$productUid])[$productUid] ?? null;
        if ($product === null) {
            return 0;
        }

        $variant = $variantUid > 0 ? $product->findVariant($variantUid) : null;
        if ($variantUid > 0 && $variant === null) {
            return 0;
        }

        // Artikel mit Ausführungen brauchen eine Auswahl.
        if ($product->hasVariants() && $variant === null) {
            return 0;
        }

        $key = CartItem::keyFor($productUid, $variantUid);
        $rows = $this->readSession($request);
        $current = $rows[$key]['quantity'] ?? 0;

        $limit = min(
            self::MAX_QUANTITY,
            $product->getMaxPerOrder(),
            $product->getAvailableStock($variant),
        );

        $target = min($current + $quantity, $limit);
        if ($target <= 0) {
            return 0;
        }

        $rows[$key] = ['product' => $productUid, 'variant' => $variantUid, 'quantity' => $target];
        $this->writeSession($request, $rows);

        return $target - $current;
    }

    /**
     * Setzt die Menge einer Position. Menge 0 entfernt sie.
     */
    public function updateQuantity(ServerRequestInterface $request, string $key, int $quantity): void
    {
        $rows = $this->readSession($request);
        if (!isset($rows[$key])) {
            return;
        }

        if ($quantity <= 0) {
            unset($rows[$key]);
            $this->writeSession($request, $rows);

            return;
        }

        $productUid = $rows[$key]['product'];
        $product = $this->productRepository->findByUids([$productUid])[$productUid] ?? null;
        if ($product === null) {
            unset($rows[$key]);
            $this->writeSession($request, $rows);

            return;
        }

        $variant = $rows[$key]['variant'] > 0 ? $product->findVariant($rows[$key]['variant']) : null;
        $limit = min(
            self::MAX_QUANTITY,
            $product->getMaxPerOrder(),
            $product->getAvailableStock($variant),
        );

        $rows[$key]['quantity'] = max(1, min($quantity, $limit));
        $this->writeSession($request, $rows);
    }

    public function remove(ServerRequestInterface $request, string $key): void
    {
        $rows = $this->readSession($request);
        if (isset($rows[$key])) {
            unset($rows[$key]);
            $this->writeSession($request, $rows);
        }
    }

    public function clear(ServerRequestInterface $request): void
    {
        $this->writeSession($request, []);
    }

    /**
     * Rohdaten aus der Sitzung, gegen Manipulation abgeklopft.
     *
     * @return array<string, array{product: int, variant: int, quantity: int}>
     */
    private function readSession(ServerRequestInterface $request): array
    {
        $user = $this->getFrontendUser($request);
        if ($user === null) {
            return [];
        }

        $raw = $user->getKey('ses', self::SESSION_KEY);
        if (!is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $row) {
            if (!is_array($row) || !isset($row['product'], $row['quantity'])) {
                continue;
            }

            $productUid = (int)$row['product'];
            $variantUid = (int)($row['variant'] ?? 0);
            $quantity = (int)$row['quantity'];

            if ($productUid <= 0 || $quantity <= 0) {
                continue;
            }

            $rows[CartItem::keyFor($productUid, $variantUid)] = [
                'product' => $productUid,
                'variant' => $variantUid,
                'quantity' => min($quantity, self::MAX_QUANTITY),
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array{product: int, variant: int, quantity: int}> $rows
     */
    private function writeSession(ServerRequestInterface $request, array $rows): void
    {
        $user = $this->getFrontendUser($request);
        $user?->setAndSaveSessionData(self::SESSION_KEY, $rows);
    }

    /**
     * @param array<string, CartItem> $items
     * @return array<string, array{product: int, variant: int, quantity: int}>
     */
    private function toRows(array $items): array
    {
        $rows = [];
        foreach ($items as $key => $item) {
            $rows[$key] = [
                'product' => $item->product->getUid() ?? 0,
                'variant' => $item->variant?->getUid() ?? 0,
                'quantity' => $item->quantity,
            ];
        }

        return $rows;
    }

    private function getFrontendUser(ServerRequestInterface $request): ?FrontendUserAuthentication
    {
        $user = $request->getAttribute('frontend.user');

        return $user instanceof FrontendUserAuthentication ? $user : null;
    }
}
