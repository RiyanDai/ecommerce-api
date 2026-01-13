<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (auth()->user()->role !== 'admin') {
            // If customer tries to access admin routes, redirect to home
            if (auth()->user()->role === 'customer') {
                return redirect()->route('home');
            }
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
