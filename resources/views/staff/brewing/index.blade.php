@extends('layouts.internal')
@section('title', 'Pha chế')
@section('content')
<section aria-labelledby="page-title">
<div class="sneat-page-title"><div><p>Đơn hàng → Pha chế</p><h1 id="page-title">Hàng đợi pha chế</h1><p>Ưu tiên món chờ lâu; chỉ hiển thị món của đơn đang xử lý.</p></div><x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Đơn hàng</x-public.button></div>
<div class="kitchen-grid">
@forelse($items as $item)
<article class="sneat-card kitchen-item">
<div class="sneat-card-heading"><strong>{{ $item->order->order_code }}</strong><x-internal.workflow-status :value="$item->status" /></div>
<h2>{{ $item->product_name }}</h2><p>{{ $item->variant_name }} · {{ $item->quantity }} phần</p>
@if($item->note)<p>Ghi chú: {{ $item->note }}</p>@endif
<ul>@foreach($item->orderItemToppings as $topping)<li>{{ $topping->topping_name }} · {{ $topping->quantity }} phần topping</li>@endforeach</ul>
<small>Tạo lúc {{ $item->created_at?->format('H:i d/m') }} (UTC)</small>
@if(auth('web')->user()->role === 'staff')
<div class="category-actions">
<x-public.button href="{{ route('staff.orders.show', $item->order_id) }}" variant="outline">Chi tiết đơn</x-public.button>
@if($item->status !== 'ready')
<form method="POST" action="{{ route('staff.orders.items.status.update', [$item->order_id, $item->id]) }}">
@csrf @method('PATCH')
<input type="hidden" name="status" value="{{ $item->status === 'pending' ? 'preparing' : 'ready' }}">
<x-public.button type="submit">{{ $item->status === 'pending' ? 'Bắt đầu pha' : 'Đánh dấu sẵn sàng' }}</x-public.button>
</form>
@else<p>Sẵn sàng phục vụ. Kiểm tra tiền và nhận đồ tại chi tiết đơn.</p>@endif
</div>
@endif
</article>
@empty<div class="sneat-card sneat-empty">Chưa có món nào trong hàng đợi.</div>@endforelse
</div>
@if($items instanceof \Illuminate\Contracts\Pagination\Paginator)<div class="category-pagination">{{ $items->links() }}</div>@endif
<p class="sneat-hint">Bắt đầu pha sẽ kiểm tra và trừ nguyên liệu bằng dịch vụ kho. Thiếu tồn phải được xử lý trước khi tiếp tục. Sẵn sàng không đồng nghĩa đã thu tiền.</p>
</section>
@endsection
