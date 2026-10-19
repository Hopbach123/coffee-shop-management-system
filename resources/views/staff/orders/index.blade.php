@extends('layouts.internal')
@section('title', 'Đơn hàng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading">
            <div><p class="section-heading__eyebrow">Maison du Café · Nhân viên</p><h1 id="page-title">Đơn hàng</h1><p>Theo dõi các đơn đang được nhân viên tạo.</p></div>
            <x-public.button href="{{ route('staff.orders.create') }}">Tạo đơn hàng</x-public.button>
        </div>
        @if (session('success')) <p class="category-notice category-notice--success" role="status">{{ session('success') }}</p> @endif
        @if ($errors->any()) <div class="category-notice category-notice--error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
        <div class="category-panel">
            @if ($orders->isEmpty())
                <div class="category-empty"><h2>Chưa có đơn hàng</h2><p>Tạo đơn đầu tiên để bắt đầu phục vụ khách.</p></div>
            @else
                <div class="category-table-wrap product-table-wrap"><table class="category-table"><caption class="sr-only">Danh sách đơn hàng</caption>
                    <thead><tr><th scope="col">Mã đơn</th><th scope="col">Loại đơn</th><th scope="col">Bàn</th><th scope="col">Khách hàng</th><th scope="col">Trạng thái</th><th scope="col">Thời gian</th><th scope="col"></th></tr></thead>
                    <tbody>@foreach ($orders as $order)<tr>
                        <th scope="row" data-label="Mã đơn">{{ $order->order_code }}</th><td data-label="Loại đơn">{{ $order->order_type === 'dine_in' ? 'Tại chỗ' : 'Mang đi' }}</td>
                        <td data-label="Bàn">{{ $order->cafeTable?->table_code ?? '—' }}</td><td data-label="Khách hàng">{{ $order->customer?->name ?? 'Khách vãng lai' }}</td>
                        <td data-label="Trạng thái"><span class="category-status">{{ $order->status }}</span></td><td data-label="Thời gian">{{ $order->ordered_at?->format('d/m/Y H:i') }}</td>
                        <td data-label="Thao tác"><x-public.button href="{{ route('staff.orders.show', $order) }}" variant="outline">Xem</x-public.button></td>
                    </tr>@endforeach</tbody>
                </table></div>
                @if ($orders->hasPages())<div class="category-pagination">{{ $orders->links() }}</div>@endif
            @endif
        </div>
    </section>
@endsection
