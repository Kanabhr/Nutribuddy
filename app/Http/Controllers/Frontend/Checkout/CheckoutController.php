<?php

namespace App\Http\Controllers\Frontend\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CheckoutGuestSummaryRequest;
use App\Http\Requests\Frontend\CheckoutPlaceOrderRequest;
use App\Http\Requests\Frontend\CheckoutStoreRequest;
use App\Http\Requests\Frontend\CheckoutSummaryRequest;
use App\Http\Requests\Frontend\SendCodOtpRequest;
use App\Models\Cart;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Services\Checkout\CheckoutSummaryService;
use App\Services\Checkout\CodOtpService;
use App\Services\Checkout\OrderPlacementService;
use App\Services\Checkout\StateCityService;
use App\Services\Payments\CashfreePaymentService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function index(StateCityService $stateCityService): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $states = $stateCityService->checkoutStates();
        $savedAddresses = collect();

        if (auth()->check()) {
            $cart = Cart::where('user_id', auth()->id())->withCount('items')->first();
            if (!$cart || $cart->items_count < 1) {
                return redirect()
                    ->route('cart.page')
                    ->with('warning', 'Please add at least one item to your cart before checkout.');
            }

            $savedAddresses = CustomerAddress::where('user_id', auth()->id())
                ->latest()
                ->get();
        }

        return view('frontend.storefront.checkout.checkout', compact('savedAddresses', 'states'));
    }

    public function getCities(string $stateCode, StateCityService $stateCityService): JsonResponse
    {
        $cities = $stateCityService->checkoutCitiesForState($stateCode);

        if ($cities === null) {
            return response()->json(['cities' => []], 404);
        }

        return response()->json(['cities' => $cities]);
    }

    public function store(CheckoutStoreRequest $request): \Illuminate\Http\RedirectResponse
    {
        $request->session()->put('checkout_details', $request->validated());

        return redirect()
            ->route('checkout.index')
            ->with('success', 'Checkout details saved successfully.');
    }

    public function summary(CheckoutSummaryRequest $request, CheckoutSummaryService $checkoutSummaryService): JsonResponse
    {
        return response()->json(
            $checkoutSummaryService->forUser($request->user(), $request->validated())
        );
    }

    public function guestSummary(CheckoutGuestSummaryRequest $request, CheckoutSummaryService $checkoutSummaryService): JsonResponse
    {
        return response()->json(
            $checkoutSummaryService->forGuest(
                $request->validated('items'),
                $request->validated('coupon_code'),
                $request->validated('shipping_state')
            )
        );
    }

    public function sendCodOtp(SendCodOtpRequest $request, CodOtpService $codOtpService): JsonResponse
    {
        $address = CustomerAddress::where('user_id', $request->user()->id)->findOrFail($request->validated('address_id'));
        $result = $codOtpService->sendOtp($request->user(), $address);

        return response()->json($result);
    }

    public function placeOrder(
        CheckoutPlaceOrderRequest $request,
        OrderPlacementService $orderPlacementService,
        CashfreePaymentService $cashfreePaymentService,
        \App\Services\Payments\RazorpayPaymentService $razorpayPaymentService
    ): JsonResponse
    {
        $paymentMethod = $request->validated('payment_method') ?? null;
        $paymentData = [];

        try {
            $payload = $orderPlacementService->place(
                $request->user(),
                $request->validated(),
                function (Order $order) use ($paymentMethod, $cashfreePaymentService, $razorpayPaymentService, &$paymentData) {
                    if ($paymentMethod === 'cashfree') {
                        $cashfreeOrder = $cashfreePaymentService->createOrder($order);
                        $paymentData = [
                            'provider' => 'cashfree',
                            'mode' => $cashfreePaymentService->environment(),
                            'payment_session_id' => $cashfreeOrder['payment_session_id'],
                        ];
                    } elseif ($paymentMethod === 'razorpay') {
                        $razorpayOrder = $razorpayPaymentService->createOrder($order);
                        $paymentData = [
                            'provider' => 'razorpay',
                            'razorpay_order_id' => $razorpayOrder['id'],
                            'razorpay_key_id' => config('services.razorpay.key_id'),
                        ];
                    }
                }
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $requestId = (string) \Illuminate\Support\Str::uuid();

            \Illuminate\Support\Facades\Log::error('Order placement failed: ' . $e->getMessage(), [
                'request_id' => $requestId,
                'user_id' => $request->user()?->id,
                'payment_method' => $paymentMethod,
                'exception' => $e,
            ]);

            $statusCode = match (true) {
                $e instanceof \InvalidArgumentException => 422,
                $e instanceof \RuntimeException => 502,
                default => 500,
            };

            $isCod = $paymentMethod === 'cod';
            $defaultMessage = $isCod ? 'Unable to place order. Please try again.' : 'Unable to start payment. Please try again.';
            $defaultCode = $isCod ? 'ORDER_PLACEMENT_FAILED' : 'PAYMENT_INIT_FAILED';

            $message = ($e instanceof \RuntimeException || $e instanceof \InvalidArgumentException)
                ? $e->getMessage()
                : $defaultMessage;
            if ($e instanceof \RuntimeException || $e instanceof \InvalidArgumentException) {
                $message = $e->getMessage();
            } else {
                $message = $defaultMessage;
            }

            return response()->json([
                'success' => false,
                'code' => $defaultCode,
                'message' => $message,
                'request_id' => $requestId,
            ], $statusCode);
        }

        if (!empty($paymentData)) {
            $payload['payment'] = $paymentData;
            $payload['message'] = ucfirst($paymentMethod) . ' payment initiated.';
        }

        return response()->json($payload);
    }
}
