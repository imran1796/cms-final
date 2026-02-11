<?php

namespace App\Modules\System\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Auth\Services\Interfaces\AuthServiceInterface;
use App\Modules\System\Auth\Validators\ChangePasswordValidator;
use App\Modules\System\Auth\Validators\ForgotPasswordValidator;
use App\Modules\System\Auth\Validators\ResetPasswordValidator;
use App\Modules\System\Auth\Validators\UpdateProfileValidator;
use App\Support\ApiResponse;
use App\Support\Exceptions\ValidationApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

final class AuthController extends Controller
{
    public function __construct(private readonly AuthServiceInterface $authService)
    {
    }

    public function login(Request $request)
    {
        try {
            $result = $this->authService->login($request);
            return ApiResponse::success($result, 'Logged in');
        } catch (\Throwable $e) {
            Log::error('AuthController@login failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function logout(Request $request)
    {
        try {
            $this->authService->logout($request);
            return ApiResponse::success(null, 'Logged out');
        } catch (\Throwable $e) {
            Log::error('AuthController@logout failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function me(Request $request)
    {
        $result = $this->authService->me($request);
        return ApiResponse::success($result, 'Me');
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();
        $validated = UpdateProfileValidator::validate($request->all(), $user);
        $result = $this->authService->updateProfile($user, $validated);
        return ApiResponse::success($result, 'Profile updated');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        $validated = ChangePasswordValidator::validate($request->all());
        $this->authService->changePassword(
            $user,
            $validated['current_password'],
            $validated['password']
        );
        return ApiResponse::success(null, 'Password changed');
    }

    public function forgotPassword(Request $request)
    {
        $validated = ForgotPasswordValidator::validate($request->all());
        $status = Password::sendResetLink($validated);

        if ($status !== Password::RESET_LINK_SENT) {
            if ($status === Password::INVALID_USER) {
                return ApiResponse::success(['message' => 'If that email exists, a reset link has been sent.'], 'OK');
            }
            if ($status === Password::RESET_THROTTLED) {
                throw new ValidationApiException('Please wait before requesting another reset link.', ['email' => ['Please wait before requesting another reset link.']]);
            }
            throw new ValidationApiException('Unable to send reset link.', ['email' => ['Unable to send reset link.']]);
        }

        return ApiResponse::success(['message' => 'If that email exists, a reset link has been sent.'], 'OK');
    }

    public function resetPassword(Request $request)
    {
        $validated = ResetPasswordValidator::validate($request->all());
        $status = Password::reset(
            $validated,
            function ($user, $password) {
                $user->forceFill(['password' => $password])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            if ($status === Password::INVALID_TOKEN) {
                throw new ValidationApiException('This reset token is invalid or has expired.', ['token' => ['This reset token is invalid or has expired.']]);
            }
            if ($status === Password::INVALID_USER) {
                throw new ValidationApiException('We could not find a user with that email.', ['email' => ['We could not find a user with that email.']]);
            }
            throw new ValidationApiException('Unable to reset password.', ['email' => ['Unable to reset password.']]);
        }

        return ApiResponse::success(['message' => 'Password has been reset.'], 'OK');
    }
}
