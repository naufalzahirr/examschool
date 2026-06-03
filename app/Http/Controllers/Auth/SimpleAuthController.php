<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SimpleAuthController extends Controller
{
    public function showSetup()
    {
        if (User::query()->exists()) {
            return redirect()->route('login')->with('info', 'Setup sudah selesai. Silakan login.');
        }

        return view('auth.setup');
    }

    public function storeSetup(Request $request)
    {
        if (User::query()->exists()) {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'username' => 'admin',
            'password' => Hash::make($data['password']),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        Auth::login($user);
        AuditLog::record('setup.admin_created', $user);

        return redirect()->route('dashboard')->with('success', 'Admin pertama berhasil dibuat.');
    }

    public function showLogin()
    {
        if (!User::query()->exists()) {
            return redirect()->route('setup');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($data['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $login)->first();

        if (!$user || !$user->is_active || !Hash::check($data['password'], $user->password)) {
            return back()->withErrors(['login' => 'Akun atau password tidak sesuai / akun belum aktif.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record('auth.login', $user, ['role' => $user->role]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        AuditLog::record('auth.logout', auth()->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
