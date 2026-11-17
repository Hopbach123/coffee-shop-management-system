@if ($order->orderItems->isEmpty())
    <p>Đơn chưa có món đã lưu.</p>
@else
    <div class="estimate-table-wrap"><table class="estimate-items">
        <caption>Món đã lưu trong đơn {{ $order->order_code }} · Tiền tệ: VNĐ</caption>
        <thead><tr><th scope="col">Món</th><th scope="col">Số lượng</th><th scope="col">Đơn giá</th><th scope="col">Topping</th><th scope="col">Thành tiền</th></tr></thead>
        <tbody>@foreach ($order->orderItems as $item)<tr>
            <th scope="row">{{ $item->product_name }} @if ($item->variant_name)<span>{{ $item->variant_name }}</span>@endif
                <small>Trạng thái món: {{ $item->status }}</small>
                @if ($item->note)<small>Ghi chú: {{ $item->note }}</small>@endif
                @foreach ($item->orderItemToppings as $topping)<small>{{ $topping->topping_name }} × {{ $topping->quantity }} · {{ $topping->price }} VNĐ/phần</small>@endforeach
            </th>
            <td data-label="Số lượng">{{ $item->quantity }}</td><td data-label="Đơn giá">{{ $item->unit_price }}</td><td data-label="Topping">{{ $item->topping_amount }}</td><td data-label="Thành tiền">{{ $item->subtotal }}</td>
        </tr>@endforeach</tbody>
    </table></div>
@endif
<dl class="estimate-amounts">
    <div><dt>Tạm tính theo Order</dt><dd>{{ $order->subtotal }} VNĐ</dd></div>
    <div><dt>Giảm giá đã lưu</dt><dd>{{ $order->discount_amount }} VNĐ</dd></div>
    <div><dt>Thuế đã lưu</dt><dd>{{ $order->tax_amount }} VNĐ</dd></div>
    <div class="estimate-total"><dt>Tổng giá trị đơn theo hệ thống</dt><dd>{{ $order->total_amount }} VNĐ</dd></div>
</dl>
