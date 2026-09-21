<?php

declare(strict_types=1);

namespace Marian\Shop\Controller;

use Marian\Shop\Domain\Repository\ShippingMethodRepository;
use Marian\Shop\Service\CartService;
use Marian\Shop\Service\PriceCalculator;
use Psr\Http\Message\ResponseInterface;

/**
 * Der Warenkorb.
 *
 * Alle verändernden Aktionen antworten mit einer Umleitung (Post/Redirect/Get),
 * damit ein Neuladen der Seite nichts doppelt in den Korb legt.
 */
class CartController extends AbstractShopController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly PriceCalculator $priceCalculator,
        private readonly ShippingMethodRepository $shippingMethodRepository,
    ) {}

    public function showAction(): ResponseInterface
    {
        $cart = $this->cartService->getCart($this->request);
        $shipping = $this->shippingMethodRepository->findDefault();

        $this->view->assignMultiple([
            'cart' => $cart,
            'totals' => $this->priceCalculator->calculate($cart, $shipping),
            'shipping' => $shipping,
            'checkoutPid' => $this->intSetting('checkoutPid'),
            'shopPid' => $this->intSetting('shopPid'),
        ]);

        return $this->htmlResponse();
    }

    public function addAction(int $product, int $variant = 0, int $quantity = 1): ResponseInterface
    {
        $added = $this->cartService->add($this->request, $product, $variant, $quantity);

        if ($added > 0) {
            $this->addFlashMessage(
                $added === 1 ? 'Artikel in den Warenkorb gelegt.' : $added . ' Artikel in den Warenkorb gelegt.'
            );
        } else {
            $this->addFlashMessage(
                'Der Artikel konnte nicht hinzugefügt werden – bitte prüfe Auswahl und Verfügbarkeit.',
                '',
                \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING
            );
        }

        return $this->redirect('show');
    }

    public function updateAction(string $item, int $quantity): ResponseInterface
    {
        $this->cartService->updateQuantity($this->request, $item, $quantity);

        return $this->redirect('show');
    }

    public function removeAction(string $item): ResponseInterface
    {
        $this->cartService->remove($this->request, $item);
        $this->addFlashMessage('Artikel entfernt.');

        return $this->redirect('show');
    }

    public function clearAction(): ResponseInterface
    {
        $this->cartService->clear($this->request);
        $this->addFlashMessage('Der Warenkorb ist jetzt leer.');

        return $this->redirect('show');
    }
}
