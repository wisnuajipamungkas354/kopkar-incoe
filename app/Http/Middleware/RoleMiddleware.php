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

        // anggota: jika user isMember() dan status member aktif serta di-approve
        $isAnggota = false;
        if ($user->isMember()) {
            $member = $user->userable?->koperasiMember;
            if ($member && $member->status === 'active' && $member->is_approved) {
                $isAnggota = true;
            }
        }

        // admin: jika user active KoperasiStaff atau active KoperasiManagement
        $isAdmin = false;
        if ($user->isKoperasiStaff()) {
            if ($user->userable && $user->userable->employment_status === 'active') {
                $isAdmin = true;
            }
        } elseif ($user->isManagement()) {
            if ($user->userable && $user->userable->koperasiManagements()->where('status', 'active')->exists()) {
                $isAdmin = true;
            }
        }

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

        if ($isAnggota) {
            return redirect('/anggota');
        }

        // Jika tidak memiliki peran apa pun yang aktif, paksa logout dan arahkan ke login
        \Illuminate\Support\Facades\Auth::logout();
        return redirect('/login')->withErrors(['username' => 'Akun anda tidak memiliki akses aktif.']);
    }
}
