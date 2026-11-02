<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\CreateDietPaymentRequest;
use App\Http\Requests\Frontend\VerifyDietPaymentRequest;
use App\Services\Nutrition\DietPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DietPaymentController extends Controller
{
    public function createOrder(CreateDietPaymentRequest $request, DietPaymentService $payments): JsonResponse
    {
        return $this->jsonResult($payments->createOrder($request->user(), $request->validated()));
    }

    public function verifyPayment(VerifyDietPaymentRequest $request, DietPaymentService $payments): JsonResponse
    {
        return $this->jsonResult($payments->verifyRazorpayPayment($request->user(), $request->validated()));
    }

    public function handleCashfreeReturn(Request $request, DietPaymentService $payments)
    {
        $result = $payments->confirmCashfreeReturn(
            $request->query('order_id'),
            $request->query('subscription_id')
        );

        if ($result['success']) {
            return redirect()->route('meal-plan', ['child_id' => $result['child_id']]);
        }

        return redirect()->route('diet_chart')->with('error', $result['message']);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = $result['_status'] ?? 200;
        unset($result['_status']);

        return response()->json($result, $status);
    }
}
