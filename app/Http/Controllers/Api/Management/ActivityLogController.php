<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $logs = $this->activityLogService->list(
            $request->only(['type', 'event', 'level', 'user_id', 'store_id', 'from', 'to', 'search']),
            $request->get('per_page', 30)
        );

        return response()->json($logs);
    }
}
