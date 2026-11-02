<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Exports\Dashboard\DashboardExport;
use App\Services\Admin\Dashboard\DashboardExportDataService;
use App\Services\Admin\Dashboard\DashboardOverviewService;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardExportController extends Controller
{
    public function __invoke(
        DashboardOverviewService $overview,
        DashboardExportDataService $exportData,
    ): BinaryFileResponse {
        [$startDate, $endDate, $period, $compare, $hasCustomRange] = $overview->dateRange();
        $stats = $overview->stats();
        $filename = 'nutribuddy-dashboard-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.xlsx';
        $sheets = $exportData->buildSheets($stats, $startDate, $endDate, $period, $compare, $hasCustomRange);

        return Excel::download(new DashboardExport($sheets), $filename);
    }
}
