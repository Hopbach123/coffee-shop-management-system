@extends('layouts.internal')
@section('title', 'Người dùng')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng"><span>Quản trị</span><span>Người dùng</span></nav>
        <p class="v5-eyebrow">MAISON DU CAFÉ · QUẢN TRỊ</p>
        <h1 class="cafe-page-title">Người dùng</h1>
        <p class="cafe-page-subtitle">Danh sách tài khoản nội bộ Admin và Staff.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="cafe-btn-primary">
        <x-internal.icon name="plus" size="16" /> Thêm người dùng
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
    <form method="GET" action="{{ route('admin.users.index') }}" role="search" class="v5-toolbar">
        <div style="flex:1;min-width:220px;">
            <label for="user-search" style="display:block;font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tìm người dùng theo tên hoặc email</label>
            <input id="user-search" class="cafe-input" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên hoặc email…"
                @error('search') aria-invalid="true" aria-describedby="user-search-error" @enderror>
            @error('search')
                <p id="user-search-error" style="font-size:.8rem;color:var(--cafe-danger);margin-top:.25rem;" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <div class="v5-toolbar-actions">
            <button type="submit" class="cafe-btn-secondary">
                <x-internal.icon name="search" size="15" /> Tìm kiếm
            </button>
            @if ($search !== '')
                <a href="{{ route('admin.users.index') }}" class="cafe-btn-secondary">Xóa lọc</a>
            @endif
        </div>
    </form>

    @if ($users->isEmpty())
        <div style="padding:3rem;text-align:center;color:var(--cafe-text-secondary);">
            <x-internal.icon name="users" size="40" style="opacity:.3;margin-bottom:.75rem;" />
            <p style="font-weight:600;margin-bottom:.25rem;">
                {{ $search !== '' ? 'Không tìm thấy người dùng' : 'Chưa có người dùng' }}
            </p>
            <p style="font-size:.875rem;">
                {{ $search !== '' ? 'Thử tên hoặc email khác, hoặc xóa bộ lọc.' : 'Chưa có tài khoản nội bộ để hiển thị.' }}
            </p>
            @if ($search !== '')
                <a href="{{ route('admin.users.index') }}" class="cafe-btn-secondary">Xem tất cả</a>
            @endif
        </div>
    @else
        <div class="cafe-table-wrap">
            <table class="cafe-table">
                <caption class="sr-only">Danh sách tài khoản nội bộ</caption>
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Họ tên</th>
                        <th scope="col">Email</th>
                        <th scope="col">Số điện thoại</th>
                        <th scope="col">Vai trò</th>
                        <th scope="col">Trạng thái</th>
                        <th scope="col">Ngày tạo</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td data-label="ID">{{ $user->id }}</td>
                            <th scope="row" data-label="Họ tên">{{ $user->name }}</th>
                            <td data-label="Email">{{ $user->email }}</td>
                            <td data-label="Số điện thoại">{{ $user->phone ?: '—' }}</td>
                            <td data-label="Vai trò">
                                <span class="cafe-badge cafe-badge--info">
                                    {{ $user->role === 'admin' ? 'Quản trị viên' : 'Nhân viên' }}
                                </span>
                            </td>
                            <td data-label="Trạng thái">
                                <span class="cafe-badge {{ $user->status === 'active' ? 'cafe-badge--success' : 'cafe-badge--warning' }}">
                                    {{ $user->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}
                                </span>
                            </td>
                            <td data-label="Ngày tạo">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-label="Thao tác">
                                <div class="v5-actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="cafe-btn-icon" aria-label="Sửa tài khoản {{ $user->email }}">
                                        <x-internal.icon name="edit" size="15" />
                                    </a>
                                    @if ($user->id !== auth('web')->id())
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('{{ $user->status === 'active' ? 'Bạn có chắc muốn khóa tài khoản này?' : 'Bạn có chắc muốn mở khóa tài khoản này?' }}');">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                                            <button type="submit" class="cafe-btn-secondary" aria-label="{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }} tài khoản {{ $user->email }}">
                                                {{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div style="padding:.75rem 1.25rem;border-top:1px solid var(--cafe-border);">{{ $users->links() }}</div>
        @endif
    @endif
</div>
@endsection
