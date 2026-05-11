<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function type()
    {
        // Get first part of url
        $segment = request()->segment(1);

        return view('login', ['type' => $segment]);
    }

    public function logout()
    {
        auth()->logout();

        return redirect()->back();
    }
}
