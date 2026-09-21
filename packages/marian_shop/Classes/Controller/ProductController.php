<?php

declare(strict_types=1);

namespace Marian\Shop\Controller;

use Marian\Shop\Domain\Model\Product;
use Marian\Shop\Domain\Repository\ProductRepository;
use Marian\Shop\Service\CartService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Annotation as Extbase;

/**
 * Der Katalog: Artikelliste und Artikeldetails.
 */
class ProductController extends AbstractShopController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CartService $cartService,
    ) {}

    public function listAction(int $category = 0): ResponseInterface
    {
        $categoryUid = $category > 0 ? $category : $this->intSetting('category');

        $this->view->assignMultiple([
            'products' => $this->productRepository->findForShop($this->intSetting('limit'), $categoryUid),
            'featured' => $this->productRepository->findFeatured($this->intSetting('featuredLimit', 3)),
            'activeCategory' => $categoryUid,
            'cart' => $this->cartService->getCart($this->request),
        ]);

        return $this->htmlResponse();
    }

    #[Extbase\IgnoreValidation(['value' => 'product'])]
    public function showAction(?Product $product = null): ResponseInterface
    {
        if ($product === null) {
            $this->pageNotFound('Diesen Artikel gibt es nicht.');
        }

        $this->view->assignMultiple([
            'product' => $product,
            'cart' => $this->cartService->getCart($this->request),
            'available' => $product->isAvailable(),
        ]);

        return $this->htmlResponse();
    }
}
