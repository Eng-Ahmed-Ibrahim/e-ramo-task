<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Customer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() instanceof Customer) {
            return response()->json([
                'message' => 'Unauthorized.',
                'status' => false,
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}