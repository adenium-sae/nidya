<?php

namespace App\Services\Dashboard;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function summary(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'total_sales'      => (float) Sale::where('status', 'completed')->where('created_at', '>=', $startOfMonth)->sum('total'),
            'today_sales'      => (float) Sale::where('status', 'completed')->whereDate('created_at', Carbon::today())->sum('total'),
            'total_products'   => Product::where('is_active', true)->count(),
            'total_customers'  => Customer::count(),
            'total_categories' => Category::where('is_active', true)->count(),
            'low_stock_count'  => $this->lowStockCount(),
        ];
    }

    public function salesByDay(string $period): array
    {
        [$salesByDay, $dateKeys] = $this->buildSalesSeries($period);

        return compact('salesByDay', 'dateKeys');
    }

    public function salesByStore(array $dateKeys): array
    {
        if (empty($dateKeys)) {
            return [];
        }

        $periodStart = $dateKeys[0][0];
        $periodEnd   = end($dateKeys)[1];

        $storeIds = Sale::where('status', 'completed')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->pluck('store_id')
            ->unique()
            ->toArray();

        $result = [];
        foreach (Store::whereIn('id', $storeIds)->get() as $store) {
            $series = [];
            foreach ($dateKeys as [$start, $end]) {
                $series[] = (float) Sale::where('status', 'completed')
                    ->where('store_id', $store->id)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('total');
            }
            $result[] = [
                'store'  => ['id' => $store->id, 'name' => $store->name],
                'series' => $series,
            ];
        }

        return $result;
    }

    public function topProducts(int $days = 30, int $limit = 5): Collection
    {
        return DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', 'completed')
            ->where('sales.created_at', '>=', Carbon::now()->subDays($days))
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.total) as total_revenue'),
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get();
    }

    public function recentActivity(int $limit = 10): Collection
    {
        return ActivityLog::with(['user.profile', 'store'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id'          => $log->id,
                'type'        => $log->type,
                'event'       => $log->event,
                'description' => $log->description,
                'user'        => $log->user ? $log->user->fullName() : 'Sistema',
                'store'       => $log->store ? ['id' => $log->store->id, 'name' => $log->store->name] : null,
                'created_at'  => $log->created_at,
            ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function lowStockCount(): int
    {
        return Product::where('is_active', true)
            ->where('track_inventory', true)
            ->withSum('stock', 'quantity')
            ->withSum('stock', 'reserved')
            ->get()
            ->filter(function ($p) {
                $available = ($p->stock_sum_quantity ?? 0) - ($p->stock_sum_reserved ?? 0);
                return $available <= $p->min_stock;
            })
            ->count();
    }

    private function buildSalesSeries(string $period): array
    {
        $salesByDay = [];
        $dateKeys   = [];

        if ($period === 'today') {
            for ($i = 0; $i < 24; $i++) {
                $start = Carbon::today()->addHours($i);
                $end   = Carbon::today()->addHours($i + 1);
                $salesByDay[] = [
                    'date'  => $start->format('H:00'),
                    'total' => (float) Sale::where('status', 'completed')->whereBetween('created_at', [$start, $end])->sum('total'),
                    'count' => Sale::where('status', 'completed')->whereBetween('created_at', [$start, $end])->count(),
                ];
                $dateKeys[] = [$start, $end];
            }
        } elseif ($period === '30d') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $salesByDay[] = [
                    'date'  => $date->format('M d'),
                    'total' => (float) Sale::where('status', 'completed')->whereDate('created_at', $date)->sum('total'),
                    'count' => Sale::where('status', 'completed')->whereDate('created_at', $date)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            }
        } elseif ($period === 'year') {
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->startOfMonth()->subMonths($i);
                $salesByDay[] = [
                    'date'  => $date->format('M'),
                    'total' => (float) Sale::where('status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('total'),
                    'count' => Sale::where('status', 'completed')->whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()];
            }
        } else {
            // default: 7d
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $salesByDay[] = [
                    'date'  => $date->format('M d'),
                    'total' => (float) Sale::where('status', 'completed')->whereDate('created_at', $date)->sum('total'),
                    'count' => Sale::where('status', 'completed')->whereDate('created_at', $date)->count(),
                ];
                $dateKeys[] = [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
            }
        }

        return [$salesByDay, $dateKeys];
    }
}
