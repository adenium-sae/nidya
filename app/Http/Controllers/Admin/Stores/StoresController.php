<?php

namespace App\Http\Controllers\Admin\Stores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Stores\CreateStoreRequest;
use App\Http\Requests\Admin\Stores\UpdateStoreRequest;
use App\Services\Admin\Stores\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoresController extends Controller
{
    public function __construct(private readonly StoreService $storeService) {}

    public function index(Request $request) {
        $user_id = Auth::user()->id;
        $result = $this->storeService->findAllByAdmin($request->all(), $user_id);
        return response()->json([
            "status" => true,
            "message" => __('messages.stores_retrieved_successfully'),
            "data" => $result
        ]);
    }

    public function store(CreateStoreRequest $request) {
        $data = $request->validated();
        $user_id = Auth::user()->id;
        $store = $this->storeService->create($data, $user_id);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_created_successfully'),
            "data" => $store
        ], 201);
    }

    public function update(UpdateStoreRequest $request, string $id) {
        $data = $request->validated();
        $store = $this->storeService->update($id, $data);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_updated_successfully'),
            "data" => $store
        ]);
    }

    public function show(string $id) {
        $store = $this->storeService->getById($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.store_retrieved_successfully'),
            "data" => $store
        ]);
    }
}