<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() instanceof User) {
            return response()->json([
                'message' => 'Unauthorized.',
                'status' => false,
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}