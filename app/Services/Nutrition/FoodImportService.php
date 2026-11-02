<?php

namespace App\Services\Nutrition;

use App\Imports\FoodsImport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class FoodImportService
{
    /** @return array{success: bool, message: string, failedRows?: array, failedCount?: int, duplicateCount?: int} */
    public function import(UploadedFile $file): array
    {
        try {
            $import = new FoodsImport();
            Excel::import($import, $file);

            $failedRows = $import->failedRows ?? [];
            $failedCount = count($failedRows);
            $duplicateCount = collect($failedRows)
                ->filter(fn (array $row) => stripos((string) ($row['reason'] ?? ''), 'duplicate') !== false)
                ->count();
            $message = "Import complete: {$import->imported} inserted, {$import->skipped} skipped.";

            if ($failedCount > 0) {
                $message .= " {$failedCount} failed";
                if ($duplicateCount > 0) {
                    $message .= " ({$duplicateCount} duplicate)";
                }
                $message .= '.';
            }

            return [
                'success' => true,
                'message' => $message,
                'failedRows' => $failedRows,
                'failedCount' => $failedCount,
                'duplicateCount' => $duplicateCount,
            ];
        } catch (\Throwable $exception) {
            Log::error('Food import failed.', ['exception' => $exception]);

            return ['success' => false, 'message' => 'Import failed. Please check the file and try again.'];
        }
    }
}
