<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
        if (!session()->has('admin_id') && $request->is('admin/*')) {
            return redirect()->route('admin.login');
        }

        if (!session()->has('staff_id') && $request->is('staff/*')) {
            return redirect()->route('staff.login');
        }

        return $next($request);
    }
}
