@extends('layouts.internal')
@section('title', 'Người dùng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Người dùng</h1>
                <p>Danh sách tài khoản nội bộ Admin và Staff.</p>
            </div>
            <x-public.button href="{{ route('admin.users.create') }}">Thêm người dùng</x-public.button>
        </div>
        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <div class="category-notice category-notice--error" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        <div class="category-panel">
            <form method="GET" action="{{ route('admin.users.index') }}" class="category-search" role="search">
                <label for="user-search">Tìm người dùng theo tên hoặc email</label>
                <div class="category-search__controls">
                    <input id="user-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên hoặc email" @error('search') aria-invalid="true" aria-describedby="user-search-error" @enderror>
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.users.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
                @error('search')
                    <p id="user-search-error" class="category-field__error" role="alert">{{ $message }}</p>
                @enderror
            </form>
            @if ($users->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy người dùng</h2>
                        <p>Thử tên hoặc email khác, hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.users.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có người dùng</h2>
                        <p>Chưa có tài khoản nội bộ để hiển thị.</p>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách tài khoản nội bộ</caption>
                        <thead><tr>
                            <th scope="col">ID</th>
                            <th scope="col">Họ tên</th>
                            <th scope="col">Email</th>
                            <th scope="col">Số điện thoại</th>
                            <th scope="col">Vai trò</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Ngày tạo</th>
                            <th scope="col">Thao tác</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td data-label="ID">{{ $user->id }}</td>
                                    <th scope="row" data-label="Họ tên">{{ $user->name }}</th>
                                    <td data-label="Email">{{ $user->email }}</td>
                                    <td data-label="Số điện thoại">{{ $user->phone ?: '—' }}</td>
                                    <td data-label="Vai trò"><span class="category-status">{{ $user->role === 'admin' ? 'Quản trị viên' : 'Nhân viên' }}</span></td>
                                    <td data-label="Trạng thái"><span class="category-status category-status--{{ $user->status }}">{{ $user->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></td>
                                    <td data-label="Ngày tạo">{{ $user->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.users.edit', $user) }}" variant="outline" aria-label="Sửa tài khoản {{ $user->email }}">Sửa</x-public.button>
                                            @if ($user->id !== auth('web')->id())
                                                <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('{{ $user->status === 'active' ? 'Bạn có chắc muốn khóa tài khoản này?' : 'Bạn có chắc muốn mở khóa tài khoản này?' }}');">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'inactive' : 'active' }}">
                                                    <x-public.button type="submit" variant="outline" aria-label="{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }} tài khoản {{ $user->email }}">{{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}</x-public.button>
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
                    <div class="category-pagination">{{ $users->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
