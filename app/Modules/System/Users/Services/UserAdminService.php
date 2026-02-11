<?php

namespace App\Modules\System\Users\Services;

use App\Models\User;
use App\Modules\System\Authorization\Services\AuthorizationService;
use App\Modules\System\Users\Validators\UserAdminValidator;
use App\Support\Exceptions\NotFoundApiException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UserAdminService
{
    public function __construct(
        private readonly AuthorizationService $authz
    ) {
    }

    public function list(int $perPage = 20): LengthAwarePaginator
    {
        $this->authz->requirePermission('manage_users');

        $paginator = User::query()
            ->with(['roles', 'roles.permissions', 'permissions'])
            ->orderBy('id')
            ->paginate($perPage);

        $paginator->getCollection()->transform(fn (User $user) => $this->userPayload($user));

        return $paginator;
    }

    public function show(int $id): array
    {
        $this->authz->requirePermission('manage_users');

        $user = User::query()
            ->with(['roles', 'roles.permissions', 'permissions'])
            ->find($id);
        if (!$user) {
            throw new NotFoundApiException('User not found');
        }

        return $this->userPayload($user);
    }

    public function create(array $input): User
    {
        $this->authz->requirePermission('manage_users');

        $validated = UserAdminValidator::validateCreate($input);

        DB::beginTransaction();
        try {
            $user = User::query()->create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $validated['password'],
            ]);

            if (!empty($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }

            DB::commit();
            Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);
            return $user->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User create failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(int $id, array $input): User
    {
        $this->authz->requirePermission('manage_users');

        $user = User::query()->find($id);
        if (!$user) {
            throw new NotFoundApiException('User not found');
        }

        $validated = UserAdminValidator::validateUpdate($input, $user);

        DB::beginTransaction();
        try {
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (isset($validated['password'])) {
                $user->password = $validated['password'];
            }
            $user->save();

            if (array_key_exists('roles', $validated)) {
                $user->syncRoles($validated['roles'] ?? []);
            }

            DB::commit();
            Log::info('User updated', ['user_id' => $user->id]);
            return $user->refresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User update failed', ['message' => $e->getMessage()]);
            throw $e;
        }
    }

    public function destroy(int $id): void
    {
        $this->authz->requirePermission('manage_users');

        $user = User::query()->find($id);
        if (!$user) {
            throw new NotFoundApiException('User not found');
        }

        $user->delete();
        Log::info('User deleted', ['user_id' => $id]);
    }

    public function listRoles(): array
    {
        $this->authz->requirePermission('manage_users');

        return \Spatie\Permission\Models\Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    private function userPayload(User $user): array
    {
        $roleNames = $user->relationLoaded('roles')
            ? $user->roles->pluck('name')->values()->all()
            : $user->getRoleNames()->values()->all();

        $permissionNames = $user->relationLoaded('roles') && $user->relationLoaded('permissions')
            ? $user->permissions->pluck('name')
                ->merge($user->roles->flatMap(fn ($r) => $r->permissions->pluck('name')))
                ->unique()
                ->values()
                ->all()
            : $user->getAllPermissions()->pluck('name')->values()->all();

        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'roles'       => $roleNames,
            'permissions' => $permissionNames,
            'created_at' => $user->created_at?->toDateTimeString(),
            'updated_at'  => $user->updated_at?->toDateTimeString(),
        ];
    }
}
