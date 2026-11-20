@php
    $statusClass = $table->display_status ?? 'unknown';
    $statusIcon = \App\Services\TableStatusReadService::ICONS[$statusClass] ?? 'table';
@endphp
<article class="sneat-card floor-table-card floor-table-card--{{ $statusClass }}" aria-labelledby="floor-table-{{ $table->id }}">
    <div class="floor-table-card-top"><span class="floor-table-symbol"><x-internal.icon :name="$statusIcon" size="30" /></span><span class="floor-table-status">{{ $statuses[$statusClass] ?? 'Chưa xác định' }}</span></div>
    <h3 id="floor-table-{{ $table->id }}">{{ $table->table_code }}</h3>
    <p class="floor-table-name">{{ $table->table_name ?? '—' }}</p>
    <dl class="floor-table-details">
        <div><dt>Khu vực</dt><dd>{{ $table->area?->name ?? 'Không tìm thấy khu vực' }}</dd></div>
        <div><dt>Sức chứa</dt><dd>{{ $table->capacity ?? 'Chưa xác định' }}{{ $table->capacity !== null ? ' khách' : '' }}</dd></div>
    </dl>
    <p>Giá trị lưu: {{ $table->status ?? 'Chưa xác định' }} · Đơn đang phục vụ: {{ $table->pending_order_count }}</p>
    <p>Lịch hiện tại: {{ $table->current_reservation_count }} · Lịch tương lai: {{ $table->future_reservation_count }}</p>
    @if ($statusClass === 'conflict')<p role="status">Dữ liệu cần kiểm tra; không tự nhận khách hoặc giải phóng bàn.</p>@endif
    @if ($table->pending_payment_count || $table->paid_payment_count)<p>Bản ghi Payment trên đơn pending: pending {{ $table->pending_payment_count }}, paid {{ $table->paid_payment_count }}. Chưa xác minh kết quả thanh toán.</p>@endif
    @if ($table->area?->status !== 'active')<p class="floor-table-context">Khu vực ngừng hoạt động</p>@endif
    @if ($table->status === 'maintenance')<p class="floor-table-context">Bàn đang bảo trì.</p>@endif
    @if ($showDetailLink ?? true)
        <x-public.button :href="route('admin.tables.show', $table->id)" variant="outline" aria-label="Xem chi tiết bàn {{ $table->table_code }}">Xem chi tiết</x-public.button>
    @endif
</article>
