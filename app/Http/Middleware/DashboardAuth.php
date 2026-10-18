<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = config('services.dashboard.user');
        $password = config('services.dashboard.password');

        // F-02: Gagal-aman bila password kosong
        if (empty($password)) {
            return response('Akses ditolak (konfigurasi tidak lengkap)', 403);
        }

        // F-01, F-03: HTTP Basic Auth dengan pembanding waktu-konstan
        if (!$request->getUser() || !hash_equals($user, $request->getUser()) || !hash_equals($password, $request->getPassword() ?? '')) {
            return response('Unauthorized', 401)
                ->header('WWW-Authenticate', 'Basic realm="mcx9route"');
        }

        return $next($request);
    }
}
