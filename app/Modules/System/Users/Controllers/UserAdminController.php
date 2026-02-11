<?php

namespace App\Modules\System\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\System\Users\Services\UserAdminService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

final class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserAdminService $service
    ) {
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $perPage = (int) ($request->query('per_page', 20));
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 20;
        }

        $result = $this->service->list($perPage);

        return ApiResponse::success($result, 'Users');
    }

    public function show(int $id): \Illuminate\Http\JsonResponse
    {
        $user = $this->service->show($id);
        return ApiResponse::success($user, 'User');
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->service->create($request->all());
        $payload = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'created_at'  => $user->created_at?->toDateTimeString(),
            'updated_at'  => $user->updated_at?->toDateTimeString(),
        ];
        return ApiResponse::created($payload, 'User created');
    }

    public function update(int $id, Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $this->service->update($id, $request->all());
        $payload = [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $user->getRoleNames()->values()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'created_at'  => $user->created_at?->toDateTimeString(),
            'updated_at'  => $user->updated_at?->toDateTimeString(),
        ];
        return ApiResponse::success($payload, 'User updated');
    }

    public function destroy(int $id): \Illuminate\Http\JsonResponse
    {
        $this->service->destroy($id);
        return ApiResponse::success(null, 'User deleted');
    }

    public function roles(): \Illuminate\Http\JsonResponse
    {
        $roles = $this->service->listRoles();
        return ApiResponse::success(['roles' => $roles], 'Roles');
    }
}
