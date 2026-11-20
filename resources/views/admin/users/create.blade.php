@extends('layouts.internal')
@section('title', 'Thêm người dùng')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.users.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Người dùng</a>
            <span>Thêm mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Thêm người dùng</h1>
        <p class="cafe-page-subtitle">Tạo tài khoản nội bộ Admin hoặc Staff. Các trường có dấu * là bắt buộc.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        @if ($errors->any())
            <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra các trường được đánh dấu bên dưới.</div>
        @endif
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Họ tên *</label>
                <input id="name" class="cafe-input" name="name" type="text" value="{{ old('name') }}" maxlength="255" autocomplete="name" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror placeholder="Nhập họ tên…">
                @error('name') <p class="cafe-field-error" id="name-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Email *</label>
                <input id="email" class="cafe-input" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror placeholder="staff@example.com">
                @error('email') <p class="cafe-field-error" id="email-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="phone" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Số điện thoại</label>
                <input id="phone" class="cafe-input" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" aria-describedby="phone-help @error('phone') phone-error @enderror" @error('phone') aria-invalid="true" @enderror placeholder="0901234567">
                <p id="phone-help" style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Tối đa 20 ký tự: chữ số, dấu +, khoảng trắng, dấu ngoặc và dấu -.</p>
                @error('phone') <p class="cafe-field-error" id="phone-error">{{ $message }}</p> @enderror
            </div>
            <div class="v5-form-grid">
                <div>
                    <label for="password" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Mật khẩu *</label>
                    <input id="password" class="cafe-input" name="password" type="password" minlength="8" autocomplete="new-password" required aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                    <p id="password-help" style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Ít nhất 8 ký tự.</p>
                    @error('password') <p class="cafe-field-error" id="password-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="password_confirmation" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Xác nhận mật khẩu *</label>
                    <input id="password_confirmation" class="cafe-input" name="password_confirmation" type="password" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                </div>
            </div>
            <div class="v5-form-grid">
                <div>
                    <label for="role" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Vai trò *</label>
                    <select id="role" class="cafe-select" name="role" required @error('role') aria-invalid="true" aria-describedby="role-error" @enderror>
                        <option value="">Chọn vai trò</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin — Quản trị viên</option>
                        <option value="staff" @selected(old('role') === 'staff')>Staff — Nhân viên</option>
                    </select>
                    @error('role') <p class="cafe-field-error" id="role-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="status" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Trạng thái *</label>
                    <select id="status" class="cafe-select" name="status" required @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                        <option value="active" @selected(old('status', 'active') === 'active')>Đang hoạt động</option>
                        <option value="inactive" @selected(old('status') === 'inactive')>Ngừng hoạt động</option>
                    </select>
                    @error('status') <p class="cafe-field-error" id="status-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Thêm người dùng</button>
            <a href="{{ route('admin.users.index') }}" class="cafe-btn-secondary">Hủy</a>
        </div>
    </form>
</div>
@endsection
