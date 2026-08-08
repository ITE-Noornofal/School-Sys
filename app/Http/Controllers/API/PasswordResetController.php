<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    // ─── 1. إرسال كود التحقق ───
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'guard' => 'required|string|in:student,teacher,admin,supervisor,accountant,guardian',
        ]);

        $email = $request->email;
        $guard = $request->guard;

        // التحقق من وجود المستخدم
        $user = $this->getUserByGuard($email, $guard);

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        // حذف الأكواد القديمة
        PasswordReset::where('email', $email)->delete();

        // إنشاء كود عشوائي 6 أرقام
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // حفظ في DB
        PasswordReset::create([
            'email' => $email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
            'used' => false,
        ]);

        // إرسال الإيميل
        Mail::to($email)->send(new PasswordResetCode($code, $user->name));

        return response()->json([
            'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني',
            'email' => $this->maskEmail($email),
        ]);
    }

    // ─── 2. التحقق من الكود ───
    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $reset = PasswordReset::where('email', $request->email)
                              ->where('used', false)
                              ->where('expires_at', '>', now())
                              ->latest()
                              ->first();

        if (!$reset || !Hash::check($request->code, $reset->code)) {
            return response()->json([
                'message' => 'الكود غير صحيح أو منتهي الصلاحية'
            ], 400);
        }

        // إنشاء token مؤقت
        $token = Str::random(64);

        $reset->update([
            'token' => Hash::make($token),
            'verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'الكود صحيح',
            'reset_token' => $token,
        ]);
    }

    // ─── 3. إعادة تعيين كلمة المرور ───
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $reset = PasswordReset::where('email', $request->email)
                              ->where('used', false)
                              ->whereNotNull('verified_at')
                              ->latest()
                              ->first();

        if (!$reset || !Hash::check($request->reset_token, $reset->token)) {
            return response()->json([
                'message' => 'طلب غير صالح'
            ], 400);
        }

        // تحديث كلمة المرور
        $user = $this->findUserByEmail($request->email);

        if (!$user) {
            return response()->json([
                'message' => 'المستخدم غير موجود'
            ], 404);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // تعطيل الكود
        $reset->update(['used' => true]);

        return response()->json([
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
        ]);
    }

    // ─── Helpers ───
    private function getUserByGuard(string $email, string $guard)
    {
        $models = [
            'student' => \App\Models\Student::class,
            'teacher' => \App\Models\Teacher::class,
            'admin' => \App\Models\Admin::class,
            'supervisor' => \App\Models\Supervisor::class,
            'accountant' => \App\Models\Accountant::class,
            'guardian' => \App\Models\Guardian::class,
        ];

        $model = $models[$guard] ?? null;
        return $model ? $model::where('email', $email)->first() : null;
    }

    private function findUserByEmail(string $email)
    {
        $models = [
            \App\Models\Student::class,
            \App\Models\Teacher::class,
            \App\Models\Admin::class,
            \App\Models\Supervisor::class,
            \App\Models\Accountant::class,
            \App\Models\Guardian::class,
        ];

        foreach ($models as $model) {
            $user = $model::where('email', $email)->first();
            if ($user) return $user;
        }
        return null;
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1];

        $masked = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 2));
        return $masked . '@' . $domain;
    }
}
