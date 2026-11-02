<?php

namespace App\Http\Controllers\Admin\Nutrition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Nutrition\FoodBulkUploadRequest;
use App\Http\Requests\Admin\Nutrition\FoodDataRequest;
use App\Models\Food;
use App\Services\Nutrition\FoodImportService;
use App\Services\Nutrition\FoodTableDataService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class FoodController extends Controller
{
    public function __construct(
        private readonly FoodTableDataService $foodTable,
        private readonly FoodImportService $foodImporter,
    ) {}

    public function index()
    {
        return view('admin.nutrition.foods.index', [
            'foodFilters' => $this->foodTable->filterOptions(),
            'canManageFoods' => request()->user('admin')?->can('manage diet chart') ?? false,
        ]);
    }

    public function data(FoodDataRequest $request): JsonResponse
    {
        return response()->json($this->foodTable->response($request));
    }

    public function show(int $id): JsonResponse
    {
        $food = Food::query()->findOrFail($id);

        return response()->json([
            'data' => $food->getAttributes(),
        ]);
    }

    /**
     * Delete a food record.
     */
    public function destroy($id)
    {
        try {
            $food = Food::findOrFail($id);

            if ($this->foodTable->isReferencedByNutritionHistory((int) $food->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This food is used by diet charts or nutrition records and cannot be deleted.',
                ], 409);
            }

            $food->delete();

            return response()->json([
                'success' => true,
                'message' => 'Food deleted successfully.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Food not found.',
            ], 404);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete food.',
            ], 500);
        }
    }

    /**
     * Process the uploaded Excel/CSV file (AJAX).
     */
    public function bulkUploadStore(FoodBulkUploadRequest $request): JsonResponse
    {
        $result = $this->foodImporter->import($request->file('file'));

        return response()->json($result, $result['success'] ? 200 : 500);
    }
}
