<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\DietSubscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DietSubscriptionInvoiceController extends Controller
{
    public function download(Request $request, DietSubscription $subscription)
    {
        abort_unless($subscription->user_id === $request->user()->id, 403);
        $subscription->load(['user', 'child', 'subscriptionPlan']);

        return Pdf::loadView('pdf.invoices.diet-subscription', compact('subscription'))->setPaper('a4')->download('NB-DIET-' . $subscription->id . '.pdf');
    }
}
