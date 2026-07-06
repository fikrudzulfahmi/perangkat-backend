<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleSanctum
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // Jika user belum login, atau user tidak memiliki role yang sesuai (bisa multi-role dipisah dengan '|')
        if (!$user || !$user->hasAnyRole(explode('|', $role))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format hak akses ditolak. Anda tidak memiliki role yang sesuai.'
            ], 403);
        }

        return $next($request);
    }
}
