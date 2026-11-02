<?php

namespace App\Exports\Dashboard;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardExport implements WithMultipleSheets
{
    /** @param array<string, array<int, array<int, mixed>>> $sheets */
    public function __construct(private readonly array $sheets) {}

    public function sheets(): array
    {
        $exports = [];
        foreach ($this->sheets as $title => $rows) {
            $exports[] = new DashboardSheetExport($title, $rows);
        }

        return $exports;
    }
}
