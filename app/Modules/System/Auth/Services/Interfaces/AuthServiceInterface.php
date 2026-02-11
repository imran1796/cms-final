<?php

namespace App\Modules\System\Auth\Services\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface AuthServiceInterface
{
    public function login(Request $request): array;

    public function logout(Request $request): void;

    public function me(Request $request): array;

    public function updateProfile(User $user, array $data): array;

    public function changePassword(User $user, string $currentPassword, string $newPassword): void;
}
