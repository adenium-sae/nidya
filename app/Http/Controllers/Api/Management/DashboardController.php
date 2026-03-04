<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $period = $request->query('period', '7d');

        ['salesByDay' => $salesByDay, 'dateKeys' => $dateKeys] = $this->dashboardService->salesByDay($period);

        return response()->json([
            'data' => array_merge(
                $this->dashboardService->summary(),
                [
                    'sales_by_day'    => $salesByDay,
                    'sales_by_store'  => $this->dashboardService->salesByStore($dateKeys),
                    'top_products'    => $this->dashboardService->topProducts(),
                    'recent_activity' => $this->dashboardService->recentActivity(),
                ],
            ),
        ]);
    }
}
