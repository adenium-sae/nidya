<?php

namespace App\Services\Sales;

use App\Actions\Sales\CancelSaleAction;
use App\Actions\Sales\CreateSaleAction;
use App\Models\Sale;

class SaleService
{
    public function __construct(
        protected CreateSaleAction $createSaleAction,
        protected CancelSaleAction $cancelSaleAction,
    ) {}

    // --- Queries ---

    public function list(array $filters, int $perPage)
    {
        $query = Sale::with(['user', 'customer', 'branch', 'items.product']);
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }
        return $query->latest()->paginate($perPage);
    }

    public function getDailySummary(string $branchId, string $date): array
    {
        $sales = Sale::where('branch_id', $branchId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();
        return [
            'date' => $date,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'by_payment_method' => $sales->groupBy('payment_method')->map(function($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            }),
        ];
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data, int $userId): Sale
    {
        return ($this->createSaleAction)($data, $userId);
    }

    public function cancel(Sale $sale, int $userId): Sale
    {
        return ($this->cancelSaleAction)($sale, $userId);
    }
}
