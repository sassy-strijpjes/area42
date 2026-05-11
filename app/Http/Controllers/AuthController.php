<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function type()
    {
        // Get first part of url
        $segment = request()->segment(1);

        return view('auth.login', ['type' => $segment == 'admin' ? 'admin' : 'staff']);
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
