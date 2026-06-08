<?php

namespace App\Listeners;

use App\Models\UserLog;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogUserLogout
{
    public function __construct(
        protected Request $request,
    ) {}

    public function handle(Logout $event): void
    {
        $userLog = UserLog::where('user_id', $event->user?->id)
            ->where('action', 'login')
            ->latest()
            ->first();

        $duration = $userLog
            ? $userLog->created_at->diffForHumans(now(), true)
            : null;

        UserLog::create([
            'user_id' => $event->user?->id,
            'action' => 'logout',
            'description' => $duration
                ? "User logged out after {$duration}"
                : 'User logged out',
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
        ]);
    }
}
