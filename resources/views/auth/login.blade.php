@extends('layouts.auth')

@section('content')
<div class="auth-glass-container" data-auth-container>
    <!-- Card Header / Title -->
    <div class="auth-card-header">
        <div class="auth-pill-badge">
            <span class="auth-pill-dot"></span>
            <span>Maison du Café</span>
        </div>
        <h1 class="auth-title" id="auth-title-text">{{ ($initialTab ?? '') === 'register' ? 'REGISTER' : 'LOGIN' }}</h1>
        <p class="auth-subtitle" id="auth-subtitle-text">
            {{ ($initialTab ?? '') === 'register' ? 'Tạo tài khoản mới để trải nghiệm dịch vụ tuyệt hảo' : 'Đăng nhập vào hệ thống quản lý & trải nghiệm dịch vụ' }}
        </p>

        <!-- Segmented Tab Switcher -->
        <div class="auth-segmented-switch" role="tablist">
            <button type="button" class="auth-segment-btn {{ ($initialTab ?? '') !== 'register' ? 'is-active' : '' }}" data-tab-target="login" role="tab" aria-selected="{{ ($initialTab ?? '') !== 'register' ? 'true' : 'false' }}">
                <span>Đăng nhập</span>
            </button>
            <button type="button" class="auth-segment-btn {{ ($initialTab ?? '') === 'register' ? 'is-active' : '' }}" data-tab-target="register" role="tab" aria-selected="{{ ($initialTab ?? '') === 'register' ? 'true' : 'false' }}">
                <span>Đăng ký</span>
            </button>
        </div>
    </div>

    <!-- Alert / Notifications -->
    @if (session('status'))
        <div class="auth-alert auth-alert--success" role="alert">
            <svg class="auth-alert__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @error('authentication')
        <div class="auth-alert auth-alert--error" role="alert">
            <svg class="auth-alert__icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div>
                <strong>Đăng nhập không thành công</strong>
                <p>{{ $message }}</p>
            </div>
        </div>
    @enderror

    <!-- ==================== FORM 1: LOGIN ==================== -->
    <div class="auth-panel-body {{ ($initialTab ?? '') === 'register' ? 'is-hidden' : '' }}" id="panel-login">
        <form class="auth-glass-form" method="POST" action="{{ route('login.store') }}">
            @csrf

            <!-- Email Field -->
            <div class="auth-input-group">
                <label for="login-email" class="auth-label">Email</label>
                <div class="auth-input-wrapper">
                    <input id="login-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required placeholder="name@example.com"
                        class="auth-input @error('email') is-invalid @enderror"
                        @error('email') aria-invalid="true" aria-describedby="login-email-error" @enderror>
                    <span class="auth-input-icon" aria-hidden="true">
                        <!-- Envelope SVG -->
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                        </svg>
                    </span>
                </div>
                @error('email')
                    <p class="auth-error-hint" id="login-email-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div class="auth-input-group">
                <label for="login-password" class="auth-label">Password</label>
                <div class="auth-input-wrapper">
                    <input id="login-password" name="password" type="password" autocomplete="current-password" required placeholder="••••••••"
                        class="auth-input @error('password') is-invalid @enderror"
                        @error('password') aria-invalid="true" aria-describedby="login-password-error" @enderror>
                    <button type="button" class="auth-input-icon auth-password-toggle" aria-label="Hiện hoặc ẩn mật khẩu" data-target="login-password">
                        <!-- Eye SVG -->
                        <svg class="icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off is-hidden" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                            <line x1="2" y1="2" x2="22" y2="22"></line>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="auth-error-hint" id="login-password-error">{{ $message }}</p>
                @enderror
            </div>

            <!-- Options Row (Remember Me & Forgot Password) -->
            <div class="auth-options-row">
                <label class="auth-checkbox-label">
                    <input type="checkbox" name="remember" class="auth-custom-checkbox" id="remember-me">
                    <span class="auth-checkbox-box"></span>
                    <span class="auth-checkbox-text">Remember Me</span>
                </label>
                <a href="#forgot" class="auth-forgot-link" onclick="alert('Vui lòng liên hệ Quản trị viên (admin@maison.com) để được cấp lại mật khẩu.'); return false;">
                    Forgot Password?
                </a>
            </div>

            <!-- Submit Button (Golden pill CTA) -->
            <button type="submit" class="auth-cta-btn">
                <span>Login</span>
                <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Switcher Footer -->
            <div class="auth-footer-switch">
                <span>Don't have an Account?</span>
                <a href="#register" class="auth-switch-link" data-switch-to="register">Register</a>
            </div>
        </form>

        <!-- Quick Demo Credentials Helper -->
        <div class="auth-demo-chips">
            <span class="auth-demo-label">Tài khoản mẫu:</span>
            <button type="button" class="auth-chip" data-autofill="admin@maison.com" title="Điền nhanh Admin">
                Admin
            </button>
            <button type="button" class="auth-chip" data-autofill="staff@maison.com" title="Điền nhanh Staff">
                Staff
            </button>
        </div>
    </div>

    <!-- ==================== FORM 2: REGISTER ==================== -->
    <div class="auth-panel-body {{ ($initialTab ?? '') !== 'register' ? 'is-hidden' : '' }}" id="panel-register">
        <form class="auth-glass-form" method="POST" action="{{ route('register.store') }}">
            @csrf

            <!-- Name Field -->
            <div class="auth-input-group">
                <label for="reg-name" class="auth-label">Họ và tên</label>
                <div class="auth-input-wrapper">
                    <input id="reg-name" name="name" type="text" value="{{ old('name') }}" required placeholder="Nguyễn Văn A"
                        class="auth-input @error('name') is-invalid @enderror">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                </div>
                @error('name')
                    <p class="auth-error-hint">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Field -->
            <div class="auth-input-group">
                <label for="reg-email" class="auth-label">Email</label>
                <div class="auth-input-wrapper">
                    <input id="reg-email" name="email" type="email" value="{{ old('email') }}" required placeholder="email@example.com"
                        class="auth-input @error('email') is-invalid @enderror">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                        </svg>
                    </span>
                </div>
                @error('email')
                    <p class="auth-error-hint">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone Field -->
            <div class="auth-input-group">
                <label for="reg-phone" class="auth-label">Số điện thoại</label>
                <div class="auth-input-wrapper">
                    <input id="reg-phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="0901234567"
                        class="auth-input @error('phone') is-invalid @enderror">
                    <span class="auth-input-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </span>
                </div>
                @error('phone')
                    <p class="auth-error-hint">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Field -->
            <div class="auth-input-group">
                <label for="reg-password" class="auth-label">Mật khẩu (ít nhất 6 ký tự)</label>
                <div class="auth-input-wrapper">
                    <input id="reg-password" name="password" type="password" required placeholder="••••••••"
                        class="auth-input @error('password') is-invalid @enderror">
                    <button type="button" class="auth-input-icon auth-password-toggle" aria-label="Hiện hoặc ẩn mật khẩu" data-target="reg-password">
                        <svg class="icon-eye" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg class="icon-eye-off is-hidden" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                            <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                            <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                            <line x1="2" y1="2" x2="22" y2="22"></line>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="auth-error-hint">{{ $message }}</p>
                @enderror
            </div>

            <!-- Agreement Checkbox -->
            <div class="auth-options-row">
                <label class="auth-checkbox-label">
                    <input type="checkbox" required class="auth-custom-checkbox" id="reg-terms">
                    <span class="auth-checkbox-box"></span>
                    <span class="auth-checkbox-text">Đồng ý với điều khoản & chính sách</span>
                </label>
            </div>

            <!-- Submit Button (Register CTA) -->
            <button type="submit" class="auth-cta-btn">
                <span>Register</span>
                <svg viewBox="0 0 20 20" width="18" height="18" fill="currentColor">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <!-- Switcher Footer -->
            <div class="auth-footer-switch">
                <span>Already have an Account?</span>
                <a href="#login" class="auth-switch-link" data-switch-to="login">Login</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleText = document.getElementById('auth-title-text');
    const subtitleText = document.getElementById('auth-subtitle-text');
    const panelLogin = document.getElementById('panel-login');
    const panelRegister = document.getElementById('panel-register');
    const segmentButtons = document.querySelectorAll('.auth-segment-btn');
    const switchLinks = document.querySelectorAll('.auth-switch-link');

    function switchAuthTab(targetTab) {
        if (targetTab === 'register') {
            panelLogin.classList.add('is-hidden');
            panelRegister.classList.remove('is-hidden');
            titleText.textContent = 'REGISTER';
            subtitleText.textContent = 'Tạo tài khoản mới để trải nghiệm dịch vụ tuyệt hảo';
            document.title = 'Đăng ký tài khoản — Maison du Café';
        } else {
            panelRegister.classList.add('is-hidden');
            panelLogin.classList.remove('is-hidden');
            titleText.textContent = 'LOGIN';
            subtitleText.textContent = 'Đăng nhập vào hệ thống quản lý & trải nghiệm dịch vụ';
            document.title = 'Đăng nhập — Maison du Café';
        }

        segmentButtons.forEach(btn => {
            const isActive = btn.getAttribute('data-tab-target') === targetTab;
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
    }

    // Tab segment buttons
    segmentButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            switchAuthTab(btn.getAttribute('data-tab-target'));
        });
    });

    // In-form link switches
    switchLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            switchAuthTab(link.getAttribute('data-switch-to'));
        });
    });

    // Check URL Hash (#register or #login)
    if (window.location.hash === '#register' || new URLSearchParams(window.location.search).get('tab') === 'register') {
        switchAuthTab('register');
    }

    // Password visibility togglers
    document.querySelectorAll('.auth-password-toggle').forEach(toggleBtn => {
        toggleBtn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const eye = this.querySelector('.icon-eye');
            const eyeOff = this.querySelector('.icon-eye-off');

            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.add('is-hidden');
                eyeOff.classList.remove('is-hidden');
            } else {
                input.type = 'password';
                eyeOff.classList.add('is-hidden');
                eye.classList.remove('is-hidden');
            }
        });
    });

    // Quick demo autofill chips
    document.querySelectorAll('[data-autofill]').forEach(chip => {
        chip.addEventListener('click', function () {
            const email = this.getAttribute('data-autofill');
            const emailInput = document.getElementById('login-email');
            const passInput = document.getElementById('login-password');
            if (emailInput && passInput) {
                emailInput.value = email;
                passInput.value = 'password';
                emailInput.focus();
            }
        });
    });
});
</script>
@endsection