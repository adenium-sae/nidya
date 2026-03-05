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

class ProductController extends Controller
{
    use LogsActivity;

    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getProducts(
            $request->all()
        );
        return response()->json($products);
    }

    public function storeSingle(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $product = $this->productService->create($data);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.created',
            description: "Producto '{$product->name}' creado en tienda seleccionada",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'store_id' => $data['store_id'] ?? null,
            ],
            storeId: $request->user()?->store_id
        );

        return response()->json([
            'message' => __('messages.product_created_in_single_store'),
            'data' => $product->load('category')
        ], 201);
    }

    public function storeMultiple(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $product = $this->productService->create($data);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.created',
            description: "Producto '{$product->name}' creado en múltiples tiendas",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'store_ids' => $data['store_ids'] ?? [],
            ],
            storeId: $request->user()?->store_id
        );

        return response()->json([
            'message' => __('messages.product_created_in_multiple_stores'),
            'data' => $product->load('category')
        ], 201);
    }

    public function storeAll(StoreProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = '/storage/' . $path;
        }

        $product = $this->productService->create($data + ['all_stores' => true]);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.created',
            description: "Producto '{$product->name}' creado en todas las tiendas",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'all_stores' => true,
            ],
            storeId: $request->user()?->store_id
        );

        return response()->json([
            'message' => __('messages.product_created_in_all_stores'),
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
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = '/storage/' . $path;
        }
        $updatedProduct = $this->productService->update($id, $data);

        // Log activity
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
            storeId: $request->user()?->store_id
        );

        return response()->json([
            'message' => __('messages.product_updated_successfully'),
            'data' => $updatedProduct->load('category')
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);
        $this->productService->delete($id);

        // Log activity
        $this->logActivity(
            type: ActivityLog::TYPE_CATALOG,
            event: 'product.deleted',
            description: "Producto '{$product->name}' eliminado",
            metadata: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
            ],
            storeId: auth()?->user()?->store_id
        );

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