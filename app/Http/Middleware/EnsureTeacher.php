<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTeacher
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user() instanceof \App\Models\Teacher) {
            return response()->json(['message' => 'Unauthorized (teacher only)'], 403);
        }

        return $next($request);
    }
}
