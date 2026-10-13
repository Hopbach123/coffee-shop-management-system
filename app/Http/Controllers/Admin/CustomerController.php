<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetCustomerStatusRequest;
use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Http\Requests\Admin\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            // Existing SoftDeletes preserves the row and all historical references.
            $deleted = $customer->delete();
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.customers.index')->withErrors(['delete' => 'Không thể xóa hồ sơ khách hàng. Vui lòng thử lại hoặc liên hệ quản lý.']);
        }

        if (! $deleted) {
            return redirect()->route('admin.customers.index')->withErrors(['delete' => 'Không thể xóa hồ sơ khách hàng.']);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Đã xóa mềm hồ sơ khách hàng. Lịch sử giao dịch được giữ nguyên.');
    }

    public function edit(Customer $customer): View
    {
        return view('admin.customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'phone', 'email', 'birthday']);
        try {
            $customer->fill($data)->save();
        } catch (UniqueConstraintViolationException $exception) {
            if (($data['phone'] ?? null) === null || ! Customer::withTrashed()->where('phone', $data['phone'])->where('id', '!=', $customer->getKey())->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['phone' => 'Số điện thoại này đã được sử dụng.']);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Đã cập nhật khách hàng.');
    }

    public function setStatus(SetCustomerStatusRequest $request, Customer $customer): RedirectResponse
    {
        $customer->status = $request->validated('status');
        $customer->save();

        return redirect()->route('admin.customers.index')->with('success', $customer->status === 'active' ? 'Đã kích hoạt hồ sơ khách hàng.' : 'Đã vô hiệu hóa hồ sơ khách hàng.');
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';
        $customers = Customer::query()
            ->select(['id', 'name', 'phone', 'email', 'birthday', 'status', 'created_at'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('phone', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('admin.customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'phone', 'email', 'birthday']);
        try {
            Customer::create($data);
        } catch (UniqueConstraintViolationException $exception) {
            if (($data['phone'] ?? null) === null || ! Customer::withTrashed()->where('phone', $data['phone'])->exists()) {
                throw $exception;
            }

            throw ValidationException::withMessages(['phone' => 'Số điện thoại này đã được sử dụng.']);
        }

        return redirect()->route('admin.customers.index')->with('success', 'Đã thêm khách hàng.');
    }
}
