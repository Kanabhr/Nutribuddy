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
        $result = $payments->verifyRazorpayPayment($request->user(), $request->validated());

        // Add a questionnaire_url to the JSON so the frontend JS can redirect there
        // when needs_questionnaire is true, without the controller knowing the route.
        if (($result['success'] ?? false) && ($result['needs_questionnaire'] ?? false)) {
            $childId = $result['child_id'] ?? null;
            $planSlug = $result['plan_slug'] ?? null;
            $result['questionnaire_url'] = $childId
                ? route('frontend.nutrition.questionnaire.child', $childId).'?plan='.urlencode((string) $planSlug)
                : route('frontend.nutrition.questionnaire').'?plan='.urlencode((string) $planSlug);
        }

        return $this->jsonResult($result);
    }

    public function handleCashfreeReturn(Request $request, DietPaymentService $payments)
    {
        $result = $payments->confirmCashfreeReturn(
            $request->query('order_id'),
            $request->query('subscription_id')
        );

        if (! $result['success']) {
            return redirect()->route('diet_chart')->with('error', $result['message']);
        }

        // Rule 1: if the purchased plan needs more answers, send the user to the questionnaire.
        if ($result['needs_questionnaire'] ?? false) {
            $childId  = $result['child_id'] ?? null;
            $planSlug = $result['plan_slug'] ?? null;

            if ($childId && $planSlug) {
                // Append ?plan=<slug> so QuestionnaireController::showForChild() can
                // enter the upgrade-filter branch and show only the higher-level
                // questions (Plus/Pro/Premium above the child's existing Basic level).
                // Without this parameter showForChild() falls back to reading the
                // child's current model_level ('basic'), passes plan=basic into show(),
                // and the user sees all 12 Basic questions instead of the ~9 Plus ones.
                $url = route('frontend.nutrition.questionnaire.child', $childId)
                    . '?plan=' . urlencode((string) $planSlug);

                return redirect($url)
                    ->with('info', 'Payment successful! Please answer a few more questions to complete your plan.');
            }

            return redirect(
                route('frontend.nutrition.questionnaire') . '?plan=' . urlencode((string) $planSlug)
            )->with('info', 'Payment successful! Please complete the questionnaire to generate your plan.');
        }

        return redirect()->route('meal-plan', ['child_id' => $result['child_id']]);
    }

    private function jsonResult(array $result): JsonResponse
    {
        $status = $result['_status'] ?? 200;
        unset($result['_status']);

        return response()->json($result, $status);
    }
}
