<?php

namespace App\Http\Controllers\Api\Management\Organization\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Organization\Branches\GetBranchesRequest;
use App\Http\Requests\Management\Organization\Branches\UpdateBranchRequest;
use App\Services\Organization\BranchService;
use Illuminate\Http\Request;

use App\Http\Requests\Management\Organization\Branches\CreateBranchRequest;

class BranchesController extends Controller
{
    public function __construct(private readonly BranchService $branchService) {}

    public function index(GetBranchesRequest $request)
    {
        $filters = $request->validated();
        $branches = $this->branchService->findAll($filters);
        return response()->json([
            "status" => true,
            "message" => __('messages.branches_retrieved_successfully'),
            "data" => $branches
        ]);
    }

    public function store(CreateBranchRequest $request)
    {
        $data = $request->validated();
        $branch = $this->branchService->create($data);
        return response()->json([
            "status" => true,
            "message" => __('messages.branch_created_successfully'),
            "data" => $branch
        ], 201);
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

    public function destroy(string $id)
    {
        $this->branchService->delete($id);
        return response()->json([
            "status" => true,
            "message" => __('messages.branch_deleted_successfully')
        ]);
    }
}
