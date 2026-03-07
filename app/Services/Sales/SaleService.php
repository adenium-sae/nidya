<?php

namespace App\Services\Sales;

use App\Actions\Sales\CancelSaleAction;
use App\Actions\Sales\CreateSaleAction;
use App\Exceptions\Access\Auth\AccessDeniedException;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;

class SaleService
{
    public function __construct(
        protected CreateSaleAction $createSaleAction,
        protected CancelSaleAction $cancelSaleAction,
    ) {}

    public function list(array $filters, int $perPage)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('sales.view');
        $query = Sale::with(['user', 'customer', 'branch', 'items.product'])
            ->whereIn('store_id', $accessibleStoreIds);
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

    public function getById(string $id): Sale
    {
        $sale = Sale::with(['customer', 'items.product', 'store', 'payments', 'branch'])->findOrFail($id);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('sales.view');

        if (!in_array($sale->store_id, $accessibleStoreIds)) {
            throw new AccessDeniedException();
        }

        return $sale;
    }

    public function getDailySummary(string $branchId, string $date): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $accessibleStoreIds = $user->getAccessibleStoreIds('sales.view');
        $sales = Sale::where('branch_id', $branchId)
            ->whereIn('store_id', $accessibleStoreIds)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();
        return [
            'date' => $date,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total'),
            'by_payment_method' => $sales->groupBy('payment_method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total'),
                ];
            }),
        ];
    }

    // --- Mutations (delegated to Actions) ---

    public function create(array $data): Sale
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return ($this->createSaleAction)($data, $user);
    }

    public function cancel(string $id, array $data = []): Sale
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return ($this->cancelSaleAction)($id, $data, $user);
    }
}
