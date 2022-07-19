<?php

namespace MF\QuickOrder\Storefront\Controller;

use MF\QuickOrder\Service\QuickOrderService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
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
    protected QuickOrderService $quickOrderService;

    public function __construct(QuickOrderService $quickOrderService)
    {
        $this->quickOrderService = $quickOrderService;
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
        /** @var EntityRepository $productRepo */
        $productRepo = $this->container->get('product.repository');
        $productQuantityMap = $this->getProductQuantityMap($request);

        $activeProducts = $this->quickOrderService->getActiveProducts(
            $productRepo,
            array_keys($productQuantityMap),
            $context
        );

        $lineItems = $this->quickOrderService->prepareLineItems(
            $activeProducts,
            $productQuantityMap,
            $salesContext
        );

        $this->quickOrderService->addToCart($cart, $lineItems, $salesContext);

        return $this->redirectToRoute('frontend.checkout.cart.page');
    }

    protected function getProductQuantityMap(Request $request): array
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
}