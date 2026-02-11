<?php

namespace App\Modules\Content\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

final class PreviewTokenService
{
    public function generate(int $spaceId, string $collectionHandle, int $entryId, int $ttlSeconds = 900): string
    {
        $exp = Carbon::now()->addSeconds($ttlSeconds)->timestamp;

        $payload = [
            'space_id' => $spaceId,
            'handle' => $collectionHandle,
            'entry_id' => $entryId,
            'exp' => $exp,
        ];

        return Crypt::encryptString(json_encode($payload));
    }

    public function validate(string $token, int $spaceId, string $collectionHandle, int $entryId): bool
    {
        try {
            $raw = Crypt::decryptString($token);
            $payload = json_decode($raw, true);

            if (!is_array($payload)) return false;

            if ((int)($payload['space_id'] ?? 0) !== $spaceId) return false;
            if ((string)($payload['handle'] ?? '') !== $collectionHandle) return false;
            if ((int)($payload['entry_id'] ?? 0) !== $entryId) return false;

            $exp = (int)($payload['exp'] ?? 0);
            if ($exp <= 0) return false;

            return Carbon::now()->timestamp <= $exp;
        } catch (\Throwable) {
            return false;
        }
    }
}
