@extends('layouts.internal')
@section('title', 'Khuyến mãi')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div>
                <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
                <h1 id="page-title">Khuyến mãi</h1>
                <p>Quản lý mã giảm giá, thời gian hiệu lực và số lượt sử dụng.</p>
            </div>
            <x-public.button href="{{ route('admin.promotions.create') }}">Thêm khuyến mãi</x-public.button>
        </div>

        @if (session('success'))
            <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p>
        @endif

        <div class="category-panel">
            <form method="GET" action="{{ route('admin.promotions.index') }}" class="category-search" role="search">
                <label for="promotion-search">Tìm theo mã hoặc tên</label>
                <div class="category-search__controls">
                    <input id="promotion-search" type="search" name="search" value="{{ $search }}" maxlength="255" placeholder="Nhập mã hoặc tên">
                    <x-public.button type="submit">Tìm kiếm</x-public.button>
                    @if ($search !== '')
                        <x-public.button href="{{ route('admin.promotions.index') }}" variant="outline">Xóa lọc</x-public.button>
                    @endif
                </div>
            </form>

            @if ($promotions->isEmpty())
                <div class="category-empty">
                    <h2>{{ $search !== '' ? 'Không tìm thấy khuyến mãi' : 'Chưa có khuyến mãi' }}</h2>
                    <p>{{ $search !== '' ? 'Thử mã hoặc tên khác.' : 'Thêm khuyến mãi đầu tiên để bắt đầu.' }}</p>
                </div>
            @else
                <div class="category-table-wrap promotion-table-wrap" role="region" aria-label="Danh sách khuyến mãi" tabindex="0">
                    <table class="category-table">
                        <caption class="sr-only">Danh sách khuyến mãi</caption>
                        <thead>
                            <tr>
                                <th scope="col">Mã</th>
                                <th scope="col">Tên</th>
                                <th scope="col">Loại / Giá trị</th>
                                <th scope="col">Giảm tối đa</th>
                                <th scope="col">Đơn tối thiểu</th>
                                <th scope="col">Bắt đầu</th>
                                <th scope="col">Kết thúc</th>
                                <th scope="col">Lượt dùng</th>
                                <th scope="col">Trạng thái</th>
                                <th scope="col">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotions as $promotion)
                                <tr>
                                    <th scope="row" data-label="Mã">{{ $promotion->code }}</th>
                                    <td data-label="Tên">{{ $promotion->name }}</td>
                                    <td data-label="Loại / Giá trị">{{ $promotion->discount_type === 'percentage' ? 'Phần trăm' : 'Cố định' }} · {{ $promotion->discount_type === 'percentage' ? rtrim(rtrim($promotion->discount_value, '0'), '.') . '%' : number_format((float) $promotion->discount_value, 2, ',', '.') . ' ₫' }}</td>
                                    <td data-label="Giảm tối đa">{{ $promotion->max_discount === null ? 'Không áp dụng' : number_format((float) $promotion->max_discount, 2, ',', '.') . ' ₫' }}</td>
                                    <td data-label="Đơn tối thiểu">{{ number_format((float) $promotion->minimum_order, 2, ',', '.') }} ₫</td>
                                    <td data-label="Bắt đầu">{{ $promotion->start_at->format('d/m/Y H:i') }}</td>
                                    <td data-label="Kết thúc">{{ $promotion->end_at->format('d/m/Y H:i') }}</td>
                                    <td data-label="Lượt dùng">{{ $promotion->used_count }} / {{ $promotion->usage_limit ?? '∞' }}</td>
                                    <td data-label="Trạng thái"><span class="category-status category-status--{{ $promotion->status }}">{{ $promotion->status === 'active' ? 'Hoạt động' : 'Tạm dừng' }}</span></td>
                                    <td data-label="Thao tác">
                                        <div class="category-actions">
                                            <x-public.button href="{{ route('admin.promotions.edit', $promotion) }}" variant="outline">Sửa</x-public.button>
                                            <form method="POST" action="{{ route('admin.promotions.toggle-status', $promotion) }}">
                                                @csrf
                                                @method('PATCH')
                                                <x-public.button type="submit" variant="outline">{{ $promotion->status === 'active' ? 'Tạm dừng' : 'Kích hoạt' }}</x-public.button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('Bạn có chắc muốn xóa khuyến mãi này?');">
                                                @csrf
                                                @method('DELETE')
                                                <x-public.button type="submit" variant="outline" class="category-delete">Xóa</x-public.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($promotions->hasPages())
                    <div class="category-pagination">{{ $promotions->links() }}</div>
                @endif
            @endif
        </div>
    </section>
@endsection
