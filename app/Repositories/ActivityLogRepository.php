<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogRepository
{
    public function latest(int $limit = 8): Collection
    {
        return ActivityLog::with('user')->latest()->limit($limit)->get();
    }

    public function create(?int $userId, string $action, string $description): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
