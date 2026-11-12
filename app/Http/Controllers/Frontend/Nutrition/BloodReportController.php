<?php

namespace App\Http\Controllers\Frontend\Nutrition;

use App\Http\Controllers\Controller;
use App\Models\Nutrition\ChildAssessment;
use App\Models\Nutrition\QuestionnaireQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * BloodReportController
 *
 * Serves a privately stored blood-report file uploaded for Q31.
 *
 * Access:
 *  - download():        owning parent (web guard). The route is
 *                       nutrition.blood-report.download (auth:web).
 *  - downloadForAdmin(): nutritionist / admin reviewer (admin guard).
 *                       The route is admin.nutrition.blood-report.download
 *                       and sits under permission:manage diet chart,admin
 *                       in routes/admin.php, the same middleware group as
 *                       the lead-review routes.
 *
 * The storage path is NEVER echoed into any page; we look it up server-side
 * from the questionnaire_answers table using the assessment id and Q31's id.
 */
class BloodReportController extends Controller
{
    /**
     * Parent-facing download. Only the owning parent (web guard) may access.
     */
    public function download(Request $request, ChildAssessment $assessment)
    {
        $user = $request->user();
        abort_if($user === null, 403);

        // Owner check only — reviewers use downloadForAdmin().
        $isOwner = (int) $assessment->child?->user_id === (int) $user->id;
        abort_unless($isOwner, 403);

        return $this->serveFile($assessment);
    }

    /**
     * Admin-guard download for nutritionist / admin reviewers.
     * The route in routes/admin.php is wrapped by permission:manage diet chart,admin,
     * so by the time we reach here the admin guard user already holds that permission.
     * We still verify the admin guard is populated as a safety net.
     */
    public function downloadForAdmin(ChildAssessment $assessment)
    {
        $admin = Auth::guard('admin')->user();
        abort_if($admin === null, 403);

        return $this->serveFile($assessment);
    }

    /**
     * Shared path resolution + download. Never exposes the raw storage path.
     */
    private function serveFile(ChildAssessment $assessment)
    {
        // Look up Q31 answer (file path).
        $q31 = QuestionnaireQuestion::where('sort_order', 31)->first();
        abort_unless($q31, 404);

        $answer = $assessment->answers()
            ->where('question_id', $q31->id)
            ->first();

        $path = $answer?->answer_value;
        abort_if(! $path || ! is_string($path), 404);

        // Sanitise path — no traversal.
        $normalised = str_replace('\\', '/', $path);
        abort_if(str_contains($normalised, '../') || str_starts_with($normalised, '/'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($normalised), 404);

        return $disk->download($normalised);
    }
}
