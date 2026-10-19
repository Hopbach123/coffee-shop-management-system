@extends('layouts.internal')
@section('title', 'Đặt bàn')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="category-heading"><div>
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Đặt bàn</h1>
        <p>Tra cứu yêu cầu đặt bàn. Thời gian hiển thị theo UTC.</p>
    </div>
        @if (app()->environment(['local', 'testing']))<x-public.button href="{{ route('admin.reservations.create') }}">Tạo yêu cầu đặt bàn</x-public.button>@endif
    </div>
    <div class="category-panel">
        @if ($errors->any())
            <div class="category-notice category-notice--error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <form method="GET" action="{{ route('admin.reservations.index') }}" class="category-search" role="search">
            <div class="category-fields">
                <div class="category-field"><label for="search">Tên hoặc số điện thoại khách</label><input id="search" name="search" type="search" maxlength="255" value="{{ $filters['search'] ?? '' }}"></div>
                <div class="category-field"><label for="status">Trạng thái</label><select id="status" name="status"><option value="">Tất cả trạng thái</option>@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="category-field"><label for="date">Ngày bắt đầu (UTC)</label><input id="date" name="date" type="date" value="{{ $filters['date'] ?? '' }}"></div>
                <div class="category-field"><label for="area_id">Khu vực</label><select id="area_id" name="area_id"><option value="">Tất cả khu vực</option>@foreach ($areas as $area)<option value="{{ $area->id }}" @selected((string) ($filters['area_id'] ?? '') === (string) $area->id)>{{ $area->name }}{{ $area->deleted_at ? ' (đã xóa)' : '' }}</option>@endforeach</select></div>
            </div>
            <div class="category-search__controls"><x-public.button type="submit">Lọc</x-public.button><x-public.button href="{{ route('admin.reservations.index') }}" variant="outline">Xóa lọc</x-public.button></div>
        </form>
        @if ($reservations->isEmpty())
            <div class="category-empty"><h2>{{ count(array_filter($filters, fn ($value) => $value !== null && $value !== '')) ? 'Không tìm thấy đặt bàn' : 'Chưa có đặt bàn' }}</h2><p>Thử thay đổi bộ lọc hoặc quay lại sau.</p></div>
        @else
            <div class="category-table-wrap product-table-wrap"><table class="category-table">
                <caption class="sr-only">Danh sách đặt bàn, thời gian UTC</caption>
                <thead><tr>@foreach (['Mã', 'Khách hàng', 'Điện thoại', 'Bàn / khu vực', 'Số khách', 'Bắt đầu (UTC)', 'Kết thúc (UTC)', 'Trạng thái', 'Chi tiết'] as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>@foreach ($reservations as $reservation)<tr>
                    <th scope="row" data-label="Mã">#{{ $reservation->id }}</th>
                    <td data-label="Khách hàng">{{ $reservation->customer_name }}</td>
                    <td data-label="Điện thoại">{{ $reservation->customer_phone }}</td>
                    <td data-label="Bàn / khu vực">{{ $reservation->cafeTable?->table_code ?? 'Chưa gán bàn' }} / {{ $reservation->cafeTable?->area?->name ?? '—' }}</td>
                    <td data-label="Số khách">{{ $reservation->guest_count }}</td>
                    <td data-label="Bắt đầu (UTC)">{{ $reservation->reservation_start_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td data-label="Kết thúc (UTC)">{{ $reservation->reservation_end_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td data-label="Trạng thái"><span class="category-status">{{ $statuses[$reservation->status] ?? $reservation->status }}</span></td>
                    <td data-label="Chi tiết"><div class="category-actions"><x-public.button href="{{ route('admin.reservations.show', $reservation) }}" variant="outline" aria-label="Chi tiết đặt bàn {{ $reservation->id }}">Xem chi tiết</x-public.button>@include('admin.reservations._approve')@include('admin.reservations._cancel')</div></td>
                </tr>@endforeach</tbody>
            </table></div>
            @if ($reservations->hasPages())<div class="category-pagination">{{ $reservations->links() }}</div>@endif
        @endif
    </div>
</section>
@endsection
