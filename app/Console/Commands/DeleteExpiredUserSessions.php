<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSession;
use Carbon\Carbon;

class DeleteExpiredUserSessions extends Command
{
    protected $signature = 'session:clear-expired';
    protected $description = 'Delete expired user sessions';

    public function handle()
    {
        $expirationTime = Carbon::now()->subMinutes(config('sanctum.expiration', 60));
        $deletedSessions = UserSession::where('created_at', '<', $expirationTime)->delete();

        $this->info("Deleted $deletedSessions expired user sessions.");
    }
}
