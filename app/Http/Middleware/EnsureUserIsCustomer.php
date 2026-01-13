<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsCustomer
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'customer') {
            // If admin tries to access customer routes, redirect to admin dashboard
            if (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('login');
        }

        return $next($request);
    }
}
