<section aria-label="Thống kê bàn theo bộ lọc">
    <p class="floor-scope">Thống kê trạng thái hiển thị theo tất cả bộ lọc, trên mọi trang. Chỉ tính bàn và khu vực chưa xóa; khu vực ngừng hoạt động được xếp Chưa xác định. Số bàn trống không phải cam kết khả dụng đặt bàn.</p>
    <dl class="floor-summary">
        <div class="sneat-card"><dt>Tổng số bàn</dt><dd>{{ $summary->total }}</dd></div>
        @foreach ($statuses as $value => $label)
            <div class="sneat-card floor-summary--{{ $value }}"><dt>{{ $label }}</dt><dd>{{ $summary->$value }}</dd></div>
        @endforeach
    </dl>
</section>
