<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSupervisor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // تأكد أن المستخدم لديه الدور الصحيح (supervisor)
        if (! $user || ! $user->hasRole('supervisor')) {
            return response()->json([
                'message' => 'Unauthorized: Not a supervisor.'
            ], 403);
        }

        return $next($request);
    }
}
