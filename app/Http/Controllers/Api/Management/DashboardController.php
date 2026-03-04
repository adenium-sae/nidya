<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ActivityLog;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        
        $totalSales = Sale::where('status', 'completed')
            ->where('created_at', '>=', $startOfMonth)
            ->sum('total');
            
        $todaySales = Sale::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total');

        $totalProducts = Product::where('is_active', true)->count();
        $totalCustomers = Customer::count();
        $totalCategories = Category::where('is_active', true)->count();
        
        $lowStockProducts = Product::where('is_active', true)
            ->where('track_inventory', true)
            ->withSum('stock', 'quantity')
            ->withSum('stock', 'reserved')
            ->get()
            ->filter(function ($p) {
                $available = ($p->stock_sum_quantity ?? 0) - ($p->stock_sum_reserved ?? 0);
                return $available <= $p->min_stock;
            })->count();

        $period = $request->query('period', '7d');
        $salesByDay = [];

        $dateKeys = [];
        if ($period === 'today') {
            for ($i = 0; $i < 24; $i++) {
                $start = Carbon::today()->addHours($i);
                $end = Carbon::today()->addHours($i + 1);
                
                $hourTotal = Sale::where('status', 'completed')
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total');
                    
                $salesByDay[] = [
                    'date' => $start->format('H:00'),
                    'total' => (float)$hourTotal,
                    'count' => Sale::where('status', 'completed')->whereBetween('created_at', [$start, $end])->count(),
                ];
                $dateKeys[] = [$start, $end];
            }
        } elseif ($period === '30d') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayTotal = Sale::where('status', 'completed')->whereDate('created_at', $date)->sum('total');
                $salesByDay[] = [
                    'date' => $date->format('M d'),
                    'total' => (float)$dayTotal,
                    'count' => Sale::where('status', 'completed')->whereDate('created_at', $date)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            }
        } elseif ($period === 'year') {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->startOfMonth()->subMonths($i);
                $monthTotal = Sale::where('status', 'completed')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
                $salesByDay[] = [
                    'date' => $date->format('M'),
                    'total' => (float)$monthTotal,
                    'count' => Sale::where('status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
            }
        } else { // Default 7d
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $dayTotal = Sale::where('status', 'completed')->whereDate('created_at', $date)->sum('total');
                $salesByDay[] = [
                    'date' => $date->format('M d'),
                    'total' => (float)$dayTotal,
                    'count' => Sale::where('status', 'completed')->whereDate('created_at', $date)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            }
        }

        // Build sales_by_store aligned with sales_by_day dates
        $salesByStore = [];
        // find stores that have sales in the broader period window
        if (!empty($dateKeys)) {
            $periodStart = $dateKeys[0][0];
            $periodEnd = end($dateKeys)[1];
            $storeIds = Sale::where('status', 'completed')
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->pluck('store_id')
                ->unique()
                ->toArray();

            $stores = Store::whereIn('id', $storeIds)->get();
            foreach ($stores as $storeItem) {
                $series = [];
                foreach ($dateKeys as $dk) {
                    $start = $dk[0];
                    $end = $dk[1];
                    $val = Sale::where('status', 'completed')
                        ->where('store_id', $storeItem->id)
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('total');
                    $series[] = (float)$val;
                }
                $salesByStore[] = [
                    'store' => ['id' => $storeItem->id, 'name' => $storeItem->name],
                    'series' => $series,
                ];
            }
        }

        $topProducts = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', Carbon::now()->subDays(30))
            ->select('products.id', 'products.name', DB::raw('SUM(sale_items.quantity) as total_sold'), DB::raw('SUM(sale_items.total) as total_revenue'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        $recentActivity = ActivityLog::with(['user.profile', 'store'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'type' => $log->type,
                    'event' => $log->event,
                    'description' => $log->description,
                    'user' => $log->user ? $log->user->fullName() : 'Sistema',
                    'store' => $log->store ? ['id' => $log->store->id, 'name' => $log->store->name] : null,
                    'created_at' => $log->created_at,
                ];
            });

        return response()->json([
            'data' => [
                'total_sales' => (float)$totalSales,
                'today_sales' => (float)$todaySales,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'total_categories' => $totalCategories,
                'low_stock_count' => $lowStockProducts,
                'sales_by_day' => $salesByDay,
                'sales_by_store' => $salesByStore,
                'top_products' => $topProducts,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }
}
