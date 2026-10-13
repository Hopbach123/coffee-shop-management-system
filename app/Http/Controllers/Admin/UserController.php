<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetUserStatusRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\UserManagementService;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function edit(User $user): View
    {
        $user = User::query()->select(['id', 'name', 'email', 'phone', 'role', 'status'])->findOrFail($user->getKey());

        return view('admin.users.edit', compact('user'));
    }

    public function update(UpdateUserRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        try {
            $service->update($request->user('web'), $user, $request->safe()->only(['name', 'email', 'phone', 'role', 'status']));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['account' => $exception->getMessage()]);
        } catch (UniqueConstraintViolationException $exception) {
            if (! User::withTrashed()->where('email', $request->validated('email'))->where('id', '!=', $user->getKey())->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['email' => 'Email này đã được sử dụng.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật người dùng.');
    }

    public function setStatus(SetUserStatusRequest $request, User $user, UserManagementService $service): RedirectResponse
    {
        try {
            $updated = $service->update($request->user('web'), $user, $request->safe()->only(['status']));
        } catch (DomainException $exception) {
            throw ValidationException::withMessages(['account' => $exception->getMessage()]);
        }

        return redirect()->route('admin.users.index')->with('success', $updated->status === 'active' ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'phone', 'password', 'role', 'status']);
        $user = new User;
        $user->fill(array_intersect_key($data, array_flip(['name', 'email', 'phone', 'password'])));
        $user->role = $data['role'];
        $user->status = $data['status'];

        try {
            $user->save();
        } catch (UniqueConstraintViolationException $exception) {
            // An email may become occupied after validation, including soft-deleted accounts.
            if (! User::withTrashed()->where('email', $data['email'])->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['email' => 'Email này đã được sử dụng.']);
        }

        return redirect()->route('admin.users.index')->with('success', 'Đã thêm người dùng.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';

        $users = User::query()
            ->select(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }
}
