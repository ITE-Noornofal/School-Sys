<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateTeacher
{
    public function handle(Request $request, Closure $next)
    {
        // تحقق من تسجيل دخول المعلم باستخدام guard teacher
        if (!Auth::guard('teacher')->check()) {
            return response()->json(['message' => 'Unauthorized - Teacher only'], 401);
        }

        return $next($request);
    }
}
