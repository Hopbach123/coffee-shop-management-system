@extends('layouts.internal')
@section('title', 'Khách hàng')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Khách hàng</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Khách hàng</h1>
        <p class="cafe-page-subtitle">Danh sách thông tin khách hàng của quán.</p>
    </div>
    <a href="{{ route('admin.customers.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm khách hàng
    </a>
</div>

@if (session('success'))
    <div class="cafe-notice cafe-notice--success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="cafe-notice cafe-notice--error" role="alert">
        @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
    </div>
@endif

<div class="cafe-card">
    <form method="GET" action="{{ route('admin.customers.index') }}" role="search" class="v5-toolbar">
        <div style="flex:1;min-width:220px;">
            <label for="customer-search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tìm khách hàng theo tên hoặc số điện thoại</label>
            <input id="customer-search" class="cafe-input" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên hoặc số điện thoại…"
                @error('search') aria-invalid="true" aria-describedby="customer-search-error" @enderror>
            @error('search') <p class="cafe-field-error" id="customer-search-error" role="alert">{{ $message }}</p> @enderror
        </div>
        <div class="v5-toolbar-actions">
            <button type="submit" class="cafe-btn-secondary">
                <x-internal.icon name="search" size="15" /> Tìm kiếm
            </button>
            @if ($search !== '')
                <a href="{{ route('admin.customers.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            @endif
        </div>
    </form>

    @if ($customers->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="user" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">
                {{ $search !== '' ? 'Không tìm thấy khách hàng' : 'Chưa có khách hàng' }}
            </p>
            <p style="font-size:.875rem;">
                {{ $search !== '' ? 'Thử tên hoặc số điện thoại khác, hoặc xóa bộ lọc.' : 'Thêm khách hàng để lưu thông tin liên hệ.' }}
            </p>
            @if ($search !== '')
                <a href="{{ route('admin.customers.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
            @endif
        </div>
    @else
        <div class="cafe-table-wrap">
            <table class="cafe-table">
                <caption class="sr-only">Danh sách khách hàng</caption>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Họ tên</th>
                        <th scope="col">Số điện thoại</th>
                        <th scope="col">Email</th>
                        <th scope="col">Ngày sinh</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Ngày tạo</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td data-label="ID">{{ $customer->id }}</td>
                            <th scope="row" data-label="Họ tên">{{ $customer->name ?? '—' }}</th>
                            <td data-label="Số điện thoại">{{ $customer->phone ?? '—' }}</td>
                            <td data-label="Email">{{ $customer->email ?? '—' }}</td>
                            <td data-label="Ngày sinh">{{ $customer->birthday?->format('d/m/Y') ?? '—' }}</td>
                            <td data-label="Trạng thái">
                                <span class="cafe-badge {{ $customer->status === 'active' ? 'cafe-badge--success' : 'cafe-badge--warning' }}">
                                    {{ $customer->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                                </span>
                            </td>
                            <td data-label="Ngày tạo">{{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-label="Thao tác">
                                <div class="v5-actions">
                                    <a href="{{ route('admin.customers.edit', $customer) }}" class="cafe-btn-icon" aria-label="Sửa hồ sơ khách hàng {{ $customer->id }}">
                                        <x-internal.icon name="edit" size="15" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.customers.status', $customer) }}" onsubmit="return confirm('{{ $customer->status === 'active' ? 'Bạn có chắc muốn vô hiệu hóa hồ sơ khách hàng này?' : 'Bạn có chắc muốn kích hoạt hồ sơ khách hàng này?' }}');">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $customer->status === 'active' ? 'inactive' : 'active' }}">
                                        <button type="submit" class="cafe-btn-secondary">
                                            {{ $customer->status === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm hồ sơ khách hàng này? Lịch sử giao dịch được giữ nguyên.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" aria-label="Xóa mềm hồ sơ khách hàng {{ $customer->id }}">
                                            <x-internal.icon name="trash" size="15" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $customers->links() }}</div>
        @endif
    @endif
</div>
@endsection
