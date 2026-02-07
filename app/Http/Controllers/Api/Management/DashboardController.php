<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tenantId = $user->tenants()->first()?->id;
        if (!$tenantId) {
            return response()->json([
                'stats' => [],
                'salesByDay' => [],
                'topProducts' => [],
            ]);
        }
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        $todaySales = 0;
        $monthSales = 0;
        $salesChange = 0;
        $totalCustomers = 0;
        $totalProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
        $lowStockProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('track_inventory', true)
            ->whereHas('stock', function ($q) {
                $q->havingRaw('SUM(quantity - reserved) <= products.min_stock');
            })
            ->count();
        $salesByDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $salesByDay[] = [
                'date' => $date->format('D'),
                'total' => 0,
                'count' => 0,
            ];
        }
        $topProducts = [];
        return response()->json([
            'stats' => [
                'todaySales' => (float) $todaySales,
                'monthSales' => (float) $monthSales,
                'salesChange' => $salesChange,
                'totalProducts' => $totalProducts,
                'lowStockProducts' => $lowStockProducts,
                'totalCustomers' => $totalCustomers,
            ],
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
        ]);
    }
}
