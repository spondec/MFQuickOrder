<?php

namespace MF\QuickOrder\Storefront\Controller;

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
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class QuickOrderController extends StorefrontController
{
    protected LineItemFactoryRegistry $lineItemFactoryRegistry;
    protected CartService $cartService;

    public function __construct(LineItemFactoryRegistry $lineItemFactoryRegistry, CartService $cartService)
    {
        $this->lineItemFactoryRegistry = $lineItemFactoryRegistry;
        $this->cartService = $cartService;
    }

    /**
     * @Route("/quick-order", name="frontend.quickorder", methods={"GET"})
     */
    public function showQuickOrder(): Response
    {
        return $this->renderStorefront('@MFQuickOrder/storefront/page/quick.order.html.twig');
    }

    /**
     * @Route("/quick-order/to-cart", name="frontend.quickorder.to-cart", methods={"POST"})
     */
    public function sendQuickOrderProductsToCart(
        Request             $request,
        Cart                $cart,
        Context             $context,
        SalesChannelContext $salesContext
    ): Response
    {
        $productQuantityMap = $this->getProductQuantityMap($request);
        $activeProducts = $this->getActiveProducts($productQuantityMap, $context);

        $lineItems = $this->prepareLineItems($activeProducts, $productQuantityMap, $salesContext);

        $this->cartService->add($cart, $lineItems, $salesContext);

        return $this->redirectToRoute('frontend.checkout.cart.page');
    }

    public function getActiveProducts(array $productQuantityMap, Context $context): ProductCollection
    {
        /** @var EntityRepository $productRepo */
        $productRepo = $this->container->get('product.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addFilter(new EqualsAnyFilter('productNumber', array_keys($productQuantityMap)));

        /** @var ProductCollection<ProductEntity> $activeProducts */
        $activeProducts = $productRepo->search($criteria, $context)->getEntities();
        return $activeProducts;
    }

    public function getProductQuantityMap(Request $request): array
    {
        $products = $request->get('products');
        $productMap = array_filter(
                array_combine(
                    array_column($products, 'number'),
                    array_column($products, 'quantity')
                )
            ) ?? [];
        return $productMap;
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
                'quantity'     => (int)$productQuantityMap,
                'payload'      => []
            ], $salesContext);
        }
        return $lineItems;
    }
}