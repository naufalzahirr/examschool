<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->must_change_password && ! $request->routeIs('profile.password*') && ! $request->routeIs('logout')) {
            return redirect()->route('profile.password.edit')->with('info', 'Silakan ganti password awal terlebih dahulu sebelum memakai sistem.');
        }

        return $next($request);
    }
}
