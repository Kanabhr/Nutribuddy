<?php

namespace App\Http\Controllers\Frontend\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CartStoreRequest;
use App\Http\Requests\Frontend\CartUpdateRequest;
use App\Models\Cart;
use App\Services\PricingService;
use App\Services\Storefront\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request, CartService $cartService, PricingService $pricingService): JsonResponse
    {
        return response()->json($cartService->contents($request, $pricingService));
    }

    public function store(CartStoreRequest $request, CartService $cartService, PricingService $pricingService): JsonResponse
    {
        $cart = $cartService->addItem($request, $request->validated());

        return response()->json($cartService->response($cart, $pricingService, 'Item added to cart successfully.'));
    }

    public function update(CartUpdateRequest $request, int $itemId, CartService $cartService, PricingService $pricingService): JsonResponse
    {
        $cart = $cartService->updateItem($request, $itemId, (int) $request->validated('quantity'));

        return response()->json($cartService->response($cart, $pricingService, 'Cart item updated successfully.'));
    }

    public function destroy(Request $request, int $itemId, CartService $cartService, PricingService $pricingService): JsonResponse
    {
        $cart = $cartService->removeItem($request, $itemId);

        return response()->json($cartService->response($cart, $pricingService, 'Cart item removed successfully.'));
    }
}
