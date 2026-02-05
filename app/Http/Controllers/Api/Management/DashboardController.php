<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
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
        $todaySales = Sale::where('tenant_id', $tenantId)
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('total');
        $monthSales = Sale::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfMonth, Carbon::now()])
            ->where('status', 'completed')
            ->sum('total');
        $lastMonthSales = Sale::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->where('status', 'completed')
            ->sum('total');
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
        $totalCustomers = Customer::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->count();
        $salesByDay = Sale::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [Carbon::now()->subDays(6)->startOfDay(), Carbon::now()->endOfDay()])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('D'),
                    'total' => (float) $item->total,
                    'count' => (int) $item->count,
                ];
            });
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.tenant_id', $tenantId)
            ->where('sales.status', 'completed')
            ->whereBetween('sales.created_at', [$startOfMonth, Carbon::now()])
            ->select(
                'products.name',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.subtotal) as total')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'quantity' => (int) $item->quantity,
                    'total' => (float) $item->total,
                ];
            });
        $salesChange = $lastMonthSales > 0 
            ? round((($monthSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
            : 0;
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
