<?php

namespace App\Http\Controllers\Api\Management\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Catalog\StoreProductRequest;
use App\Http\Requests\Management\Catalog\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Services\Catalog\ProductService;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku',
            'barcode' => 'nullable|string|max:100|unique:products,barcode',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'store_ids' => 'required|array|min:1',
            'store_ids.*' => 'exists:stores,id',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = '/storage/' . $path;
        }

        $product = $this->productService->create($validated);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.created',
            description: "Producto '{$product->name}' creado",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'store_ids' => $validated['store_ids'] ?? [],
            ],
            storeId: !empty($validated['store_ids']) ? $validated['store_ids'][0] : null
        );

        return response()->json([
            'message' => __('messages.product_created_successfully'),
            'data' => $product->load('category')
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->all()
        );
        return response()->json($products);
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->getById($id);
        return response()->json($product);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = '/storage/' . $path;
        }
        $updatedProduct = $this->productService->update($id, $data, $request->user());
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.updated',
            description: "Producto '{$updatedProduct->name}' actualizado",
            metadata: [
                'product_id' => $updatedProduct->id,
                'sku' => $updatedProduct->sku,
                'name' => $updatedProduct->name,
                'changes' => array_keys($data),
            ],
            storeId: null
        );
        return response()->json([
            'message' => __('messages.product_updated_successfully'),
            'data' => $updatedProduct->load('category')
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = $this->productService->getById($id);
        $this->productService->delete($id);
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.deleted',
            description: "Producto '{$product->name}' eliminado",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
            ],
            storeId: $product->stores->first()?->id
        );
        return response()->json([
            'message' => __('messages.product_deleted_successfully')
        ]);
    }

    public function checkStock(string $id): JsonResponse
    {
        $status = $this->productService->getStockStatus($id, Auth::user());
        return response()->json($status);
    }
}
