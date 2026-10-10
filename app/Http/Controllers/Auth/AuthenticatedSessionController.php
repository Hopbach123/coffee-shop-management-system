<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::guard('web')->attempt([...$request->validated(), 'status' => 'active'])) {
            return back()->withErrors([
                'authentication' => 'Không thể đăng nhập. Vui lòng kiểm tra thông tin hoặc liên hệ quản lý.',
            ])->withInput($request->only('email'));
        }

        $request->session()->regenerate();
        $request->user('web')->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($request->user('web')->role === 'admin' ? 'admin.home' : 'staff.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
