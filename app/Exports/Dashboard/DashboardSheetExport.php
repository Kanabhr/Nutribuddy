<?php

namespace App\Exports\Dashboard;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class DashboardSheetExport implements FromArray, WithTitle
{
    /** @param array<int, array<int, mixed>> $rows */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly array $rows,
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }
}
