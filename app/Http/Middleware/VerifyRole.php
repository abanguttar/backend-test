<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerifyRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (Auth::user()->role === 'superadmin') {
            return $next($request);
        } else if (Auth::user()->role === 'manager' && $role === 'employee') {
            return $next($request);
        } else if (Auth::user()->role !== $role) {
            throw new HttpResponseException(response([
                'success' => false,
                'message' => 'Tidak memiliki akses'
            ], 403));
        }
        return $next($request);
    }
}
