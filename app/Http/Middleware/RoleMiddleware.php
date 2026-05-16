<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!\Illuminate\Support\Facades\Auth::check()) {
            return redirect('/login');
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        // anggota: level_user 0 atau 1
        $isAnggota = in_array($user->level_user, [0, 1]);
        // admin: level_user selain 0 dan 1
        $isAdmin = !$isAnggota;

        if ($role === 'admin' && $isAdmin) {
            return $next($request);
        }

        if ($role === 'anggota' && $isAnggota) {
            return $next($request);
        }

        // Redirect jika tidak memiliki akses yang sesuai
        if ($isAdmin) {
            return redirect('/admin');
        }

        return redirect('/anggota');
    }
}
