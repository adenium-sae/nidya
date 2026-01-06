<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\CreateProductRequest;
use App\Http\Requests\Admin\Products\GetProductsRequest;
use App\Services\Admin\Products\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(GetProductsRequest $request) {
        $filters = $request->validated();
        $products = $this->productService->getProducts($filters);
        return response()->json([
            'message' => 'Products retrieved successfully.',
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function show(string $id) {
        $product = $this->productService->getProduct($id);
        return response()->json([
            'message' => 'Product retrieved successfully.',
            'data' => $product,
        ]);
    }

    public function store(CreateProductRequest $request) {
        $data = $request->validated();
        $product = $this->productService->createProduct($data);
        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }
}
