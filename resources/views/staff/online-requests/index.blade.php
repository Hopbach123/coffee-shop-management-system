@extends('layouts.internal')
@section('title', 'Online & hẹn đến lấy')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="sneat-page-title"><div><p>Tiếp nhận tại quán</p><h1 id="page-title">Online & hẹn đến lấy</h1><p>Phân biệt yêu cầu của khách với đơn hàng đã được quán tiếp nhận.</p></div><x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Đơn hàng</x-public.button></div>
    @if (! $available)
        <div class="category-panel" role="status"><h2>Chưa thể tải yêu cầu</h2><p>Dữ liệu đặt online và hẹn đến lấy đang được chuẩn bị. Vui lòng liên hệ quản trị viên; danh sách này chưa thể xác nhận là trống.</p></div>
    @else
        <form method="GET" class="category-panel category-form" action="{{ route('staff.online-requests.index') }}">
            <div class="category-fields">
                <div class="category-field"><label for="purchase_type">Cách mua</label><select id="purchase_type" name="purchase_type"><option value="">Tất cả</option><option value="online" @selected(($filters['purchase_type'] ?? '') === 'online')>Online · lấy sớm</option><option value="scheduled" @selected(($filters['purchase_type'] ?? '') === 'scheduled')>Đặt hẹn · có giờ nhận</option></select></div>
                <div class="category-field"><label for="status">Yêu cầu</label><select id="status" name="status"><option value="">Tất cả</option>@foreach (['pending'=>'Đang chờ','accepted'=>'Đã nhận','rejected'=>'Từ chối','cancelled'=>'Đã hủy'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
            </div><x-public.button type="submit">Lọc yêu cầu</x-public.button>
        </form>
        <div class="category-panel">
            @if ($requests->isEmpty())<div class="category-empty"><h2>Không có yêu cầu phù hợp</h2><p>Thử xem tất cả trạng thái hoặc kiểm tra lại sau.</p></div>
            @else
            <div class="category-table-wrap" role="region" aria-label="Yêu cầu online và hẹn" tabindex="0"><table class="category-table"><caption class="sr-only">Yêu cầu nhận đồ uống tại quán</caption><thead><tr><th scope="col">Mã / khách</th><th scope="col">Cách mua</th><th scope="col">Giờ hẹn (UTC)</th><th scope="col">Trạng thái</th><th scope="col">Thao tác</th></tr></thead><tbody>
                @foreach ($requests as $record)<tr><th scope="row" data-label="Mã / khách">{{ $record->request_code }}<p>{{ $record->contact_name }}</p></th><td data-label="Cách mua">{{ $record->purchase_type === 'scheduled' ? 'Đặt hẹn' : 'Online lấy sớm' }}</td><td data-label="Giờ hẹn (UTC)">{{ $record->pickup_at ?? 'Lấy sớm sau xác nhận' }}</td><td data-label="Trạng thái"><x-internal.workflow-status :value="$record->status" /></td><td data-label="Thao tác"><x-public.button href="{{ route('staff.online-requests.show', $record->id) }}" variant="outline">Xem yêu cầu</x-public.button></td></tr>@endforeach
            </tbody></table></div>
            <div class="category-pagination">{{ $requests->links() }}</div>
            @endif
        </div>
        <p>Hiện hỗ trợ xem yêu cầu và đơn liên kết. Chức năng nhận hoặc từ chối yêu cầu chưa mở; không tạo lại đơn đã liên kết từ POS.</p>
    @endif
</section>
@endsection
