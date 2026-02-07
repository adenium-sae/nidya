<?php

namespace App\Http\Controllers\Api\Management\Catalog;

use App\Http\Controllers\Controller;
use App\Services\Catalog\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
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

        $tenantId = $request->user()->tenants()->first()?->id;
        $category = $this->categoryService->create($validated, $tenantId);

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

        return response()->json([
            'message' => 'Categoría actualizada exitosamente',
            'data' => $category,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->categoryService->delete($id);
        return response()->json(['message' => 'Categoría eliminada exitosamente']);
    }
}
