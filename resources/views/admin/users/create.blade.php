@extends('layouts.internal')
@section('title', 'Thêm người dùng')
@section('content')
    <section class="category-page category-page--form" aria-labelledby="page-title">
        <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
        <h1 id="page-title">Thêm người dùng</h1>
        <p>Tạo tài khoản nội bộ Admin hoặc Staff. Các trường có dấu * là bắt buộc.</p>
        <form method="POST" action="{{ route('admin.users.store') }}" class="category-panel category-form">
            @csrf
            @if ($errors->any())
                <div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
            @endif
            <div class="category-fields">
                <div class="category-field">
                    <label for="name">Họ tên *</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name') <p class="category-field__error" id="name-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="email">Email *</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email') <p class="category-field__error" id="email-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="phone">Số điện thoại</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" aria-describedby="phone-help @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror>
                    <p id="phone-help">Tối đa 20 ký tự: chữ số, dấu +, khoảng trắng, dấu ngoặc và dấu -.</p>
                    @error('phone') <p class="category-field__error" id="phone-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="password">Mật khẩu *</label>
                    <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" required aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                    <p id="password-help">Ít nhất 8 ký tự.</p>
                    @error('password') <p class="category-field__error" id="password-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="password_confirmation">Xác nhận mật khẩu *</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                </div>
                <div class="category-field">
                    <label for="role">Vai trò *</label>
                    <select id="role" name="role" required @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                        <option value="">Chọn vai trò</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin — Quản trị viên</option>
                        <option value="staff" @selected(old('role') === 'staff')>Staff — Nhân viên</option>
                    </select>
                    @error('role') <p class="category-field__error" id="role-error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="status">Trạng thái *</label>
                    <select id="status" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                        <option value="active" @selected(old('status', 'active') === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Ngừng hoạt động</option>
                    </select>
                    @error('status') <p class="category-field__error" id="status-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="category-form-actions">
                <x-public.button type="submit">Thêm người dùng</x-public.button>
                <x-public.button href="{{ route('admin.users.index') }}" variant="outline">Hủy</x-public.button>
            </div>
        </form>
    </section>
@endsection
