<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Admin\Dashboard\DashboardAnalyticsService;
use App\Services\Admin\Dashboard\DashboardOverviewService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardOverviewService $overview, private readonly DashboardAnalyticsService $analytics) {}

    public function index(): View
    {
        return view('admin.dashboard.overview', $this->overview->stats());
    }

    public function analytics(): View
    {
        return view('admin.dashboard.analytics', $this->analytics->build());
    }

    public function analyticsTable(Request $request, string $table): JsonResponse
    {
        abort_unless(in_array($table, ['users', 'shipments', 'cohorts'], true), 404);

        return response()->json($this->analytics->table($table));
    }

}
