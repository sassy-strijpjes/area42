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
        $isAdmin = request()->routeIs('admin.*');

        session()->forget($isAdmin ? 'admin_id' : 'staff_id');

        return redirect()->route(
            $isAdmin ? 'admin.login' : 'staff.login'
        );
    }
}
