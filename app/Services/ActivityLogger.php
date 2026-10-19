<?php

namespace App\Services;

use App\Repositories\ActivityLogRepository;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public function __construct(private ActivityLogRepository $activities)
    {
    }

    public function log(string $action, string $description): void
    {
        $this->activities->create(Auth::id(), $action, $description);
    }
}
