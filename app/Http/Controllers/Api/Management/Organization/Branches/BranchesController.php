<?php

namespace App\Http\Controllers\Api\Management\Organization\Branches;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\Organization\Branches\CreateBranchRequest;
use App\Http\Requests\Management\Organization\Branches\GetBranchesRequest;
use App\Http\Requests\Management\Organization\Branches\UpdateBranchRequest;
use App\Models\ActivityLog;
use App\Services\Organization\BranchService;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchesController extends Controller
{
    use LogsActivity;
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

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'branch.created',
            description: "Sucursal '{$branch->name}' creada",
            metadata: ['branch_id' => $branch->id, 'name' => $branch->name],
            storeId: Auth::user()?->store_id,
        );

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

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'branch.updated',
            description: "Sucursal '{$branch->name}' actualizada",
            metadata: ['branch_id' => $branch->id, 'name' => $branch->name, 'changes' => array_keys($data)],
            storeId: Auth::user()?->store_id,
        );

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
        $branch = $this->branchService->getById($id);
        $branchName = $branch->name;

        $this->branchService->delete($id);

        $this->logActivity(
            type: ActivityLog::TYPE_ORGANIZATION,
            event: 'branch.deleted',
            description: "Sucursal '{$branchName}' eliminada",
            metadata: ['branch_id' => $id, 'name' => $branchName],
            level: ActivityLog::LEVEL_WARNING,
            storeId: Auth::user()?->store_id,
        );

        return response()->json([
            "status" => true,
            "message" => __('messages.branch_deleted_successfully')
        ]);
    }
}
