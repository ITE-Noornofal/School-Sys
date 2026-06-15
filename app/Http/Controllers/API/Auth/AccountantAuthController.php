<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Accountant;

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Accountant;


class AccountantAuthController extends Controller
{
    /**
     * تسجيل المحاسب (بعد إضافة إيميله من قبل المدير)
     */
    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:accountants,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $accountant = Accountant::where('email', $request->email)->first();

        if ($accountant->password) {
            return response()->json(['message' => 'This accountant is already registered.'], 409);
        }

        $accountant->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
        ]);

        // إعطاء دور المحاسب
        $accountant->assignRole('accountant');

        return response()->json([
            'message' => 'Registration successful.',
            'accountant' => $accountant,
        ]);
    }

    public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $accountant = Accountant::where('email', $request->email)->first();

    if (! $accountant || ! Hash::check($request->password, $accountant->password)) {
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    // تحقق إذا المحاسب لا يملك الدور "accountant" أعطه إياه
    if (! $accountant->hasRole('accountant')) {
        $accountant->assignRole('accountant');
    }

    $token = $accountant->createToken('accountant-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful.',
        'token' => $token,
        'accountant' => $accountant,
    ]);
}


  // 🔓 تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    // 🔑 تعيين أو تغيير كلمة المرور
    public function setpassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $accountant = $request->user(); // من التوكن

        $accountant->password = Hash::make($request->password);
        $accountant->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }


    // 👀 عرض الملف الشخصي
    public function profile(Request $request)
    {
        return response()->json(['accountant' => $request->user()]);
    }

 public function updateOrCreateProfile(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:20',
    ]);

    $accountant = $request->user();

    $accountant->update([
        'name' => $request->name,
        'phone' => $request->phone,
    ]);

    $message = ($accountant->wasChanged())
        ? 'Profile updated successfully.'
        : 'Profile already up to date.';

    return response()->json([
        'message' => $message,
        'accountant' => $accountant,
    ]);
}











}
