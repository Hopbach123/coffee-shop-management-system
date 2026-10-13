@extends('layouts.internal')
@section('title', 'Khách hàng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Khách hàng</h1>
                <p>Danh sách thông tin khách hàng của quán.</p>
            </div>
            <x-public.button href="{{ route('admin.customers.create') }}">Thêm khách hàng</x-public.button>
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
            <form method="GET" action="{{ route('admin.customers.index') }}" class="category-search" role="search">
                <label for="customer-search">Tìm khách hàng theo tên hoặc số điện thoại</label>
                <div class="category-search__controls">
                    <input id="customer-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập tên hoặc số điện thoại" @error('search') aria-invalid="true" aria-describedby="customer-search-error" @enderror>
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.customers.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
                @error('search') <p class="category-field__error" id="customer-search-error" role="alert">{{ $message }}</p> @enderror
            </form>
            @if ($customers->isEmpty())
                <div class="category-empty">
                    @if ($search !== '')
                        <h2>Không tìm thấy khách hàng</h2>
                        <p>Thử tên hoặc số điện thoại khác, hoặc xóa bộ lọc.</p>
                        <x-public.button href="{{ route('admin.customers.index') }}" variant="outline">Xem tất cả</x-public.button>
                    @else
                        <h2>Chưa có khách hàng</h2>
                        <p>Thêm khách hàng để lưu thông tin liên hệ.</p>
                    @endif
                </div>
            @else
                <div class="category-table-wrap product-table-wrap">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách khách hàng</caption>
                        <thead><tr>
                            <th scope="col">ID</th><th scope="col">Họ tên</th><th scope="col">Số điện thoại</th>
                            <th scope="col">Email</th><th scope="col">Ngày sinh</th><th scope="col">Trạng thái</th><th scope="col">Ngày tạo</th>
                            <th scope="col">Thao tác</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                <tr>
                                    <td data-label="ID">{{ $customer->id }}</td>
                                    <th scope="row" data-label="Họ tên">{{ $customer->name ?? '—' }}</th>
                                    <td data-label="Số điện thoại">{{ $customer->phone ?? '—' }}</td>
                                    <td data-label="Email">{{ $customer->email ?? '—' }}</td>
                                    <td data-label="Ngày sinh">{{ $customer->birthday?->format('d/m/Y') ?? '—' }}</td>
                                    <td data-label="Trạng thái"><span class="category-status category-status--{{ $customer->status }}">{{ $customer->status === 'active' ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</span></td>
                                    <td data-label="Ngày tạo">{{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.customers.edit', $customer) }}" variant="outline" aria-label="Sửa hồ sơ khách hàng {{ $customer->id }}">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.customers.status', $customer) }}" onsubmit="return confirm('{{ $customer->status === 'active' ? 'Bạn có chắc muốn vô hiệu hóa hồ sơ khách hàng này?' : 'Bạn có chắc muốn kích hoạt hồ sơ khách hàng này?' }}');">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $customer->status === 'active' ? 'inactive' : 'active' }}">
                                                <x-public.button type="submit" variant="outline" aria-label="{{ $customer->status === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt' }} hồ sơ khách hàng {{ $customer->id }}">{{ $customer->status === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Bạn có chắc muốn xóa mềm hồ sơ khách hàng này? Lịch sử giao dịch được giữ nguyên.');">
                                                @csrf
                                                @method('DELETE')
                                                <x-public.button type="submit" variant="outline" class="category-delete" aria-label="Xóa mềm hồ sơ khách hàng {{ $customer->id }}">Xóa</x-public.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($customers->hasPages())
                    <div class="category-pagination">{{ $customers->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
