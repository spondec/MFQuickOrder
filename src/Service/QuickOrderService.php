<?php

namespace MF\QuickOrder\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class QuickOrderService
{
    protected LineItemFactoryRegistry $lineItemFactoryRegistry;
    protected CartService $cartService;

    public function __construct(
        LineItemFactoryRegistry $lineItemFactoryRegistry,
        CartService             $cartService
    )
    {
        $this->lineItemFactoryRegistry = $lineItemFactoryRegistry;
        $this->cartService = $cartService;
    }

    public function getActiveProducts(
        EntityRepository $productRepo,
        array            $productNumbers,
        Context          $context
    ): ProductCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new EqualsAnyFilter('productNumber', $productNumbers));

        /** @var ProductCollection<ProductEntity> $activeProducts */
        $activeProducts = $productRepo->search($criteria, $context)->getEntities();
        return $activeProducts;
    }

    /**
     * @return LineItem[]
     */
    public function prepareLineItems(
        ProductCollection   $products,
        array               $productQuantityMap,
        SalesChannelContext $salesContext
    ): array
    {
        $lineItems = [];

        foreach ($products as $product)
        {
            $lineItems[] = $this->lineItemFactoryRegistry->create([
                'type'         => LineItem::PRODUCT_LINE_ITEM_TYPE,
                'referencedId' => $product->getId(),
                'quantity'     => (int)$productQuantityMap[$product->getProductNumber()],
                'payload'      => []
            ], $salesContext);
        }
        return $lineItems;
    }

    public function addToCart(Cart $cart, array $lineItems, SalesChannelContext $salesContext): void
    {
        $this->cartService->add($cart, $lineItems, $salesContext);
    }
}