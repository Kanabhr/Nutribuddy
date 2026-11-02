<?php

namespace App\Services\Ecommerce;

use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class InvoiceNumberService
{
    /**
     * Get Indian Financial Year string (e.g. "2026-27") for the given date.
     * The financial year runs from 1st April to 31st March.
     */
    public function getFinancialYear(?CarbonInterface $date = null): string
    {
        $target = $date ? Carbon::instance($date) : now();
        $year = (int) $target->format('Y');
        $month = (int) $target->format('n');

        if ($month >= 4) {
            $startYear = $year;
            $endYear = ($year + 1) % 100;
        } else {
            $startYear = $year - 1;
            $endYear = $year % 100;
        }

        return sprintf('%04d-%02d', $startYear, $endYear);
    }

    /**
     * Generate the next sequential invoice number for the given date's FY.
     * Format: NB/{FY}/{sequence} (e.g. NB/2026-27/0001, NB/2026-27/0002).
     */
    public function generateNextInvoiceNumber(?CarbonInterface $date = null): string
    {
        $fy = $this->getFinancialYear($date);
        $prefix = "NB/{$fy}/";

        $latest = Order::query()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(invoice_number) DESC, invoice_number DESC')
            ->lockForUpdate()
            ->value('invoice_number');

        $nextSeq = 1;
        if ($latest && preg_match('/\/(\d+)$/', $latest, $matches)) {
            $nextSeq = ((int) $matches[1]) + 1;
        }

        do {
            $candidate = sprintf('%s%04d', $prefix, $nextSeq);
            $nextSeq++;
        } while (Order::query()->where('invoice_number', $candidate)->exists());

        return $candidate;
    }
}

