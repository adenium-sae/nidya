<?php

namespace App\Http\Controllers\Api\Management\Catalog;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Catalog\CategoryService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list($request->all());
        return response()->json($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = $this->categoryService->create($validated);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'category.created',
            description: "Categoría '{$category->name}' creada",
            metadata: [
                'category_id' => $category->id,
                'name' => $category->name,
            ],
            storeId: auth()?->user()?->store_id
        );

        return response()->json([
            'message' => 'Categoría creada exitosamente',
            'data' => $category,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $category = $this->categoryService->getById($id);
        return response()->json($category);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $category = $this->categoryService->update($id, $validated);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'category.updated',
            description: "Categoría '{$category->name}' actualizada",
            metadata: [
                'category_id' => $category->id,
                'name' => $category->name,
                'changes' => array_keys($validated),
            ],
            storeId: auth()?->user()?->store_id
        );

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'data' => $category,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $category = $this->categoryService->getById($id);
        $categoryName = $category->name;

        $this->categoryService->delete($id);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'category.deleted',
            description: "Categoría '{$categoryName}' eliminada",
            metadata: [
                'category_id' => $id,
                'name' => $categoryName,
            ],
            level: ActivityLog::LEVEL_WARNING,
            storeId: auth()?->user()?->store_id
        );

        return response()->json(['message' => 'Categoría eliminada exitosamente']);
    }
}