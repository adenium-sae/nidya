<?php

namespace App\Services\ActivityLog;

use App\Models\ActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ActivityLogService
{
    /**
     * Create a new activity log entry.
     */
    public function log(array $data): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $data['user_id'] ?? null,
            'store_id' => $data['store_id'] ?? null,
            'type' => $data['type'],
            'event' => $data['event'],
            'description' => $data['description'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'level' => $data['level'] ?? ActivityLog::LEVEL_INFO,
        ]);
    }

    /**
     * List activity logs with filters and pagination.
     */
    public function list(array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $query = ActivityLog::query()
            ->with(['user.profile', 'store'])
            ->orderByDesc('created_at');

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }

        if (!empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', "%{$filters['search']}%")
                  ->orWhere('event', 'like', "%{$filters['search']}%");
            });
        }

        return $query->paginate($perPage);
    }
}
