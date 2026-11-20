@extends('layouts.internal')
@section('title', 'Chi tiết bàn')
@section('content')
<section class="category-page floor-plan" aria-labelledby="page-title">
    <header class="category-heading">
        <div><p class="section-heading__eyebrow">Maison du Café · Không gian quán</p><h1 id="page-title">Chi tiết bàn</h1><p>Thông tin hiện tại của bàn được chọn.</p></div>
        <div class="floor-plan-actions">
            <x-public.button :href="route('admin.tables.index', ['view' => 'floor', 'area_id' => $table?->area_id])" variant="outline">Quay lại sơ đồ</x-public.button>
            <x-public.button :href="route('admin.tables.show', $tableId)" variant="outline">Làm mới</x-public.button>
        </div>
    </header>
    @if ($table === null)
        <div class="sneat-card category-empty" role="alert"><h2>Chưa thể tải thông tin bàn</h2><p>Dữ liệu hiện chưa khả dụng. Vui lòng thử làm mới sau.</p></div>
    @else
        @include('admin.tables.components.status-freshness')
        <div class="floor-detail-grid">
            @include('admin.tables.components.table-card', ['showDetailLink' => false])
            <section class="sneat-card" aria-labelledby="table-order-heading">
                <h2 id="table-order-heading">Đơn hàng tại bàn</h2>
                @if ($candidates === null)
                    <p>Thông tin đơn hàng chưa khả dụng tại màn hình này.</p>
                @elseif ($candidates->count() > 1)
                    <p role="alert">Bàn có nhiều đơn đang xử lý. Cần kiểm tra trước khi gọi món.</p>
                @elseif ($candidates->isEmpty())
                    <p>Chưa có đơn tại chỗ đang xử lý. Chưa hỗ trợ tạo đơn mới từ sơ đồ.</p>
                @elseif ($table->display_status !== 'occupied')
                    <p>Bàn hoặc khu vực không cho phép tiếp tục gọi món.</p>
                @else
                    <p>Đơn đang phục vụ: {{ $candidates->first()->order_code }}</p>
                    <x-public.button :href="route('staff.table-order.index', ['table' => $table->id, 'order' => $candidates->first()->id])">Tiếp tục gọi món</x-public.button>
                @endif
                <p>Trạng thái bàn không xác nhận bàn có thể nhận đơn mới. Xem chi tiết không giữ chỗ hoặc thay đổi bàn.</p>
            </section>
        </div>
    @endif
</section>
@endsection
