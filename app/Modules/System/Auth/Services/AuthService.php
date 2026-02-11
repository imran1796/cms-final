<?php

namespace App\Modules\System\Auth\Services;

use App\Models\User;
use App\Modules\System\Auth\Services\Interfaces\AuthServiceInterface;
use App\Modules\System\Auth\Validators\LoginValidator;
use App\Support\Exceptions\ForbiddenApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

final class AuthService implements AuthServiceInterface
{
    public function login(Request $request): array
    {
        $data = LoginValidator::validate($request->all());

        try {
            $user = User::query()
                ->where('email', $data['email'])
                ->first();

            if (!$user || !Hash::check($data['password'], $user->password)) {
                Log::warning('Login failed', ['email' => $data['email']]);
                throw new ForbiddenApiException('Invalid credentials');
            }

            $deviceName = $data['device_name'] ?? 'api';
            $token = $user->createToken($deviceName)->plainTextToken;

            Log::info('Login success', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return [
                'user'  => $this->userPayload($user),
                'token' => $token,
            ];
        } catch (\Throwable $e) {
            Log::error('Login exception', [
                'email'     => $data['email'] ?? null,
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function logout(Request $request): void
    {
        try {
            $user = $request->user();
            $token = $user?->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            Log::info('Logout success', [
                'user_id' => $user?->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Logout exception', [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function me(Request $request): array
    {
        $user = $request->user();

        return [
            'user'        => $this->userPayload($user),
            'roles'       => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        if (!empty($data)) {
            $user->update(array_filter($data));
            $user->refresh();
        }

        return [
            'user'        => $this->userPayload($user),
            'roles'       => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
        ];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            Log::warning('Change password failed: current password invalid', ['user_id' => $user->id]);
            throw new ForbiddenApiException('Current password is incorrect');
        }

        $user->update(['password' => $newPassword]);
        Log::info('Password changed', ['user_id' => $user->id]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ];
    }
}
