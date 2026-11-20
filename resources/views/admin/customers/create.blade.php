@extends('layouts.internal')
@section('title', 'Thêm khách hàng')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.customers.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Khách hàng</a>
            <span>Thêm mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Thêm khách hàng</h1>
        <p class="cafe-page-subtitle">Nhập ít nhất họ tên, số điện thoại hoặc email. Ngày sinh là tùy chọn.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.customers.store') }}">
        @csrf
        @if ($errors->any())
            <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
        @endif
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Họ tên</label>
                <input id="name" class="cafe-input" name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror placeholder="Nguyễn Văn A">
                @error('name') <p class="cafe-field-error" id="name-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Số điện thoại</label>
                <input id="phone" class="cafe-input" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" aria-describedby="phone-help @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror placeholder="0901234567">
                <p id="phone-help" style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Tối đa 20 ký tự: chữ số, dấu +, khoảng trắng, dấu ngoặc và dấu -.</p>
                @error('phone') <p class="cafe-field-error" id="phone-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Email</label>
                <input id="email" class="cafe-input" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror placeholder="example@domain.com">
                @error('email') <p class="cafe-field-error" id="email-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="birthday" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Ngày sinh</label>
                <input id="birthday" class="cafe-input" name="birthday" type="date" value="{{ old('birthday') }}" autocomplete="bday" @error('birthday') aria-invalid="true" aria-describedby="birthday-error" @enderror>
                @error('birthday') <p class="cafe-field-error" id="birthday-error">{{ $message }}</p> @enderror
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Thêm khách hàng</button>
            <a href="{{ route('admin.customers.index') }}" class="cafe-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
