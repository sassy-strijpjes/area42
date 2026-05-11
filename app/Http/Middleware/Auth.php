<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class Auth
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('admin.*')) {
            $id = session('admin_id');

            $admin = $id
                ? DB::table('admins')->where('id', $id)->first()
                : null;

            if ($admin && $request->routeIs('admin.login')) {
                return redirect()->route('admin.dashboard');
            }

            if (!$admin && !$request->routeIs('admin.login')) {
                session()->forget('admin_id');

                return redirect()->route('admin.login');
            }
        }

        if ($request->routeIs('staff.*')) {
            $id = session('staff_id');

            $staff = $id
                ? DB::table('staff')->where('id', $id)->first()
                : null;

            if ($staff && $request->routeIs('staff.login')) {
                return redirect()->route('staff.dashboard');
            }

            if (!$staff && !$request->routeIs('staff.login')) {
                session()->forget('staff_id');

                return redirect()->route('staff.login');
            }
        }

        return $next($request);
    }
}
