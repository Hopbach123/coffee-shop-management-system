<!DOCTYPE html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Phiếu tạm tính — Maison du Café</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="estimate-print">
    <main class="estimate-paper">
        <div class="estimate-controls">
            <a class="coffee-button coffee-button--outline" href="{{ route('staff.table-checkout.show', [$tableId, $orderId]) }}">Quay lại tạm tính</a>
            @if ($order !== null && $order->orderItems->isNotEmpty())<button type="button" class="coffee-button" data-estimate-print>In phiếu tạm tính</button>@endif
            <p role="status" aria-live="polite" data-estimate-print-status>Chỉ mở hộp thoại in của trình duyệt; hệ thống không xác nhận máy in đã in.</p>
            <noscript>Dùng chức năng In của trình duyệt (Ctrl+P).</noscript>
        </div>
        <h1>Phiếu tạm tính</h1><p>Maison du Café</p>
        <p class="estimate-disclaimer">Không phải biên nhận thanh toán hoặc hóa đơn điện tử. Phiếu này không chứng minh đã thanh toán.</p>
        @if ($order === null)
            <p role="alert">Chưa thể tải dữ liệu phiếu tạm tính. Không có số liệu để in.</p>
        @else
            <h2>Đơn {{ $order->order_code }} (#{{ $order->id }})</h2><p>Bàn {{ $table->table_code }} · {{ $table->area?->name ?? 'Khu vực không còn khả dụng' }}</p>
            <p>Trạng thái Order: {{ $order->status }} · Tạo lúc {{ $order->ordered_at?->format('d/m/Y H:i') ?? 'Chưa xác định' }}</p>
            @if ($order->status === 'cancelled')<p>Đơn đã hủy — chỉ tham chiếu, không phải yêu cầu thanh toán.</p>@endif
            @include('staff.orders.components.estimate-items')
            <p>Số liệu tại thời điểm tải trang; tải lại để xem thay đổi mới. Không gồm món chưa lưu.</p>
        @endif
    </main>
</body></html>
