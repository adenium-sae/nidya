<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\StoreProductRequest;
use App\Http\Requests\Admin\Products\UpdateProductRequest;
use App\Models\Product;
use App\Services\Products\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->all(),
            [
                'by' => $request->get('sort_by', 'name'),
                'order' => $request->get('sort_order', 'asc')
            ],
            $request->get('per_page', 15)
        );
        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());
        return response()->json([
            'message' => __('messages.product_created_successfully'),
            'data' => $product->load('category')
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);
        return response()->json($product);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $updatedProduct = $this->productService->update($id, $request->validated());
        return response()->json([
            'message' => __('messages.product_updated_successfully'),
            'data' => $updatedProduct->load('category')
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->productService->delete($id);
        return response()->json([
            'message' => __('messages.product_deleted_successfully')
        ]);
    }

    public function checkStock(string $id): JsonResponse
    {
        $status = $this->productService->getStockStatus($id);
        return response()->json($status);
    }
}
