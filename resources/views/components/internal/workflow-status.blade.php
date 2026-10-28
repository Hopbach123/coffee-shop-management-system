@props(['value'])
<span class="category-status">{{ [
    'pending' => 'Đang chờ', 'accepted' => 'Đã nhận yêu cầu', 'rejected' => 'Đã từ chối', 'cancelled' => 'Đã hủy',
    'preparing' => 'Đang pha', 'ready' => 'Sẵn sàng', 'served' => 'Đã phục vụ / nhận', 'completed' => 'Hoàn tất',
    'paid' => 'Đã thu tiền', 'failed' => 'Thất bại', 'refunded' => 'Đã hoàn tiền',
][$value] ?? $value }}</span>
