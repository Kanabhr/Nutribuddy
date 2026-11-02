<?php

namespace App\Http\Controllers\Admin\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class AssessmentSectionController extends Controller
{
    public function rename(): JsonResponse
    {
        return response()->json(['success' => false, 'message' => 'Sections are predefined and cannot be renamed.']);
    }
}
