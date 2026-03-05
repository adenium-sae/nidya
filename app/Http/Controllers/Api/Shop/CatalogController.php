<?php

namespace App\Http\Controllers\Api\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Store;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        // For the public shop, we assume the integration with the primary/first store.
        $store = Store::where('is_active', true)->first();

        if (!$store) {
            return response()->json([
                'data' => [],
                'meta' => ['total' => 0]
            ]);
        }

        $query = Product::with(['category', 'images' => function($q) {
            $q->orderBy('order', 'asc');
        }])
        ->where('is_active', true)
        ->whereHas('storeProducts', function ($q) use ($store) {
            $q->where('store_id', $store->id)
              ->where('is_active', true)
              ->where('is_visible', true);
        })
        ->with(['storeProducts' => function ($q) use ($store) {
            $q->where('store_id', $store->id);
        }]);

        // Filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->has('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $products = $query->paginate(12);

        return response()->json($products);
    }

    public function show(string $id)
    {
        $store = Store::where('is_active', true)->first();

        $product = Product::with([
            'category',
            'images' => fn($q) => $q->orderBy('order', 'asc'),
            'attributes',
            'storeProducts' => fn($q) => $store ? $q->where('store_id', $store->id) : $q,
        ])
        ->where('is_active', true)
        ->findOrFail($id);

        return response()->json($product);
    }
}
