@extends('layouts.public')
@section('title','Đã nhận yêu cầu — Maison du Café')
@section('content')<section class="coffee-section commerce-page"><div class="coffee-container"><div class="commerce-confirm"><span class="commerce-confirm-icon">✓</span><p class="section-heading__eyebrow">THANK YOU!</p><h1>Đã nhận yêu cầu của bạn</h1><p>Mã yêu cầu: <strong>{{ $orderRequest->reference }}</strong></p><p>Cảm ơn {{ $orderRequest->customer_name }}! Dự kiến nhận món lúc <strong>{{ $orderRequest->pickup_at->format('H:i d/m/Y') }}</strong>.</p><p><strong>Lưu ý:</strong> Yêu cầu đang chờ nhân viên xác nhận, chưa phải đơn thanh toán hoặc cam kết đã chuẩn bị món.</p><x-public.button href="{{ route('home') }}">Trở về trang chủ →</x-public.button></div></div></section>
@endsection
