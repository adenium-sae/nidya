<?php

namespace App\Http\Controllers\Admin\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Branches\GetBranchesRequest;
use App\Http\Requests\Admin\Branches\UpdateBranchRequest;
use App\Services\Admin\Branches\BranchService;
use Illuminate\Http\Request;

class BranchesController extends Controller
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(GetBranchesRequest $request) {
        $filters = $request->validated();
        $branches = $this->branchService->findAll($filters);
        return response()->json([
            "status" => true,
            "message" => __('messages.branches_retrieved_successfully'),
            "data" => $branches
        ]);
    }

    public function update(UpdateBranchRequest $request, string $id)
    {
        $data = $request->validated();
        $branch = $this->branchService->update($id, $data);
        return response()->json([
            "status" => true,
            "message" => __('messages.branch_updated_successfully'),
            "data" => $branch
        ]);
    }

    public function show(string $id)
    {
        $branch = $this->branchService->getById($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.branch_retrieved_successfully'),
            "data" => $branch
        ]);
    }
}
