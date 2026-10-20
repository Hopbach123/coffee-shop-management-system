<div class="shop-location">
    @if ($name !== '' && $address !== '')
        <p><strong>{{ $name }}</strong></p>
        <address>{{ $address }}</address>
        <p>Điểm đến khuôn viên HUIT, chưa phải điểm ghim riêng của quán. Khi đến nơi, vui lòng xác nhận vị trí quán trong khuôn viên.</p>
        @if ($embedUrl)
            <iframe src="{{ $embedUrl }}" title="Bản đồ điểm đến khuôn viên HUIT" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
        @else
            <p>Bản đồ nhúng chưa sẵn có.</p>
        @endif
        <p>Nếu bản đồ không hiển thị, bạn có thể dùng các liên kết bên dưới.</p>
        <div class="shop-location__links">
            <a href="{{ config('maps.view_url') }}" target="_blank" rel="noopener noreferrer">Xem trên Google Maps <span class="sr-only">(mở tab mới)</span></a>
            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer">Chỉ đường đến khuôn viên HUIT <span class="sr-only">(mở tab mới)</span></a>
        </div>
    @else
        <p>Liên hệ cửa hàng để xác nhận địa chỉ và thời gian hoạt động.</p>
    @endif
</div>
