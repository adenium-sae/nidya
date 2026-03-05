<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLog\ActivityLogService;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    /**
     * Log an activity event to the database.
     *
     * @param string      $type        Event category (auth, inventory, sales, catalog, organization, system)
     * @param string      $event       Specific event name (e.g. product.created, stock.adjusted)
     * @param string|null $description Human-readable description
     * @param array|null  $metadata    Additional context data
     * @param string      $level       Log level (info, warning, error, critical)
     * @param string|null $userId      User who triggered the event
     * @param string|null $storeId     Store context
     */
    protected function logActivity(
        string $type,
        string $event,
        ?string $description = null,
        ?array $metadata = null,
        string $level = ActivityLog::LEVEL_INFO,
        ?string $userId = null,
        ?string $storeId = null,
    ): ActivityLog {
        /** @var ActivityLogService $service */
        $service = app(ActivityLogService::class);

        return $service->log([
            'type' => $type,
            'event' => $event,
            'description' => $description,
            'metadata' => $metadata,
            'level' => $level,
            'user_id' => $userId ?? (Auth::check() ? Auth::id() : null),
            'store_id' => $storeId,
        ]);
    }
}
