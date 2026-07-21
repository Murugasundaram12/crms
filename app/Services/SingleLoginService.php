<?php

namespace App\Services;

use App\Models\MobileApiToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SingleLoginService
{
    public function invalidateOtherLogins(int $userId, ?string $exceptSessionId = null, ?int $exceptTokenId = null): void
    {
        $this->deleteWebSessions($userId, $exceptSessionId);
        $this->deleteApiTokens($userId, $exceptTokenId);
    }

    public function deleteWebSessions(int $userId, ?string $exceptSessionId = null): void
    {
        if (! Schema::hasTable('sessions') || ! Schema::hasColumn('sessions', 'user_id')) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', $userId)
            ->when($exceptSessionId, fn($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }

    public function deleteApiTokens(int $userId, ?int $exceptTokenId = null): void
    {
        if (! Schema::hasTable('mobile_api_tokens')) {
            return;
        }

        MobileApiToken::query()
            ->where('user_id', $userId)
            ->when($exceptTokenId, fn($query) => $query->whereKeyNot($exceptTokenId))
            ->delete();
    }
}
