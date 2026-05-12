<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function type()
    {
        $type = request()->routeIs('admin.*') ? 'admin' : 'staff';

        return view('auth.login', ['type' => $type]);
    }

    public function forgotPassword()
    {
        $type = request()->routeIs('admin.*') ? 'admin' : 'staff';

        return view('auth.forgot-password', ['type' => $type]);
    }

    public function resetPassword()
    {
        $type = request()->routeIs('admin.*') ? 'admin' : 'staff';

        return view('auth.reset-password', [
            'type' => $type,
            'token' => request()->route('token'),
            'email' => request()->query('email', ''),
        ]);
    }

    public function logout()
    {
        $isAdmin = (bool)session('admin_id');
        $routeName = $isAdmin ? 'admin.login' : 'staff.login';

        auth()->logout();

        session()->forget($isAdmin ? 'admin_id' : 'staff_id');

        return redirect()->route($routeName);
    }
}
