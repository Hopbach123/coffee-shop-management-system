@extends('layouts.public')
@section('title','Yêu cầu đặt bàn — Maison du Café')
@section('content')<section class="coffee-section commerce-page"><div class="coffee-container"><div class="commerce-confirm"><span class="commerce-confirm-icon">✓</span><p class="section-heading__eyebrow">RESERVATION</p><h1>Đã nhận yêu cầu đặt bàn</h1><p>Cảm ơn {{ $reservation->customer_name }}! Ngày giờ: <strong>{{ $reservation->reservation_start_at->format('H:i d/m/Y') }}</strong>, {{ $reservation->guest_count }} khách.</p><p>Yêu cầu đang ở trạng thái <strong>chờ xác nhận</strong>. Nhân viên sẽ sắp xếp bàn phù hợp.</p><x-public.button href="{{ route('home') }}">Về trang chủ</x-public.button></div></div></section>
@endsection
