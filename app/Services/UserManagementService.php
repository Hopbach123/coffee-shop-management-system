<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class UserManagementService
{
    public function update(User $actor, User $target, array $attributes): User
    {
        return DB::transaction(function () use ($actor, $target, $attributes): User {
            // All writers in this workflow acquire the same ordered set of row locks.
            // Read current role/status under the locks, never from stale route/session models.
            $users = User::query()->orderBy('id')->lockForUpdate()
                ->get(['id', 'name', 'email', 'phone', 'role', 'status']);
            $currentActor = $users->firstWhere('id', $actor->getKey());
            if ($currentActor === null || $currentActor->role !== 'admin' || $currentActor->status !== 'active') {
                throw new AuthorizationException('Tài khoản không còn quyền quản trị đang hoạt động.');
            }
            $user = $users->firstWhere('id', $target->getKey());
            if ($user === null) {
                throw (new ModelNotFoundException)->setModel(User::class, [$target->getKey()]);
            }
            $data = array_intersect_key($attributes, array_flip(['name', 'email', 'phone', 'role', 'status']));
            $nextStatus = $data['status'] ?? $user->status;
            $nextRole = $data['role'] ?? $user->role;
            if ($user->id === $currentActor->id && $nextStatus === 'inactive') {
                throw new DomainException('Bạn không thể tự khóa tài khoản đang đăng nhập.');
            }
            if ($user->role === 'admin' && $user->status === 'active'
                && ($nextRole !== 'admin' || $nextStatus !== 'active')
                && $users->where('role', 'admin')->where('status', 'active')->count() <= 1) {
                throw new DomainException('Không thể khóa hoặc hạ quyền Admin đang hoạt động cuối cùng.');
            }
            $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone'])));
            $user->role = $nextRole;
            $user->status = $nextStatus;
            $user->save();

            return $user;
        }, 3);
    }
}
