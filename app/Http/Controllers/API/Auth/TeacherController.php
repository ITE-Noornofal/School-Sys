<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TeacherController extends Controller
{
    // =========================================================
    // REGISTER
    // =========================================================

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'subject' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // البحث عن المعلم الذي أضافه المدير
        $teacher = Teacher::where('email', $validated['email'])->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Email not allowed. Contact admin.'
            ], 403);
        }

        // منع التسجيل مرة ثانية
        if ($teacher->password !== null) {
            return response()->json([
                'message' => 'Teacher already registered.'
            ], 409);
        }

        // =====================================================
        // PROFILE IMAGE
        // =====================================================

        $profileImagePath = null;

        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request
                ->file('profile_image')
                ->store('teachers', 'public');
        }

        // =====================================================
        // UPDATE TEACHER
        // =====================================================

        $updateData = [];

        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }

        if (isset($validated['password'])) {
            $updateData['password'] = bcrypt($validated['password']);
        }

        if (isset($validated['subject'])) {
            $updateData['subject'] = $validated['subject'];
        }

        if (isset($validated['address'])) {
            $updateData['address'] = $validated['address'];
        }

        if (isset($validated['phone'])) {
            $updateData['phone'] = $validated['phone'];
        }

        if ($profileImagePath !== null) {
            $updateData['profile_image'] = $profileImagePath;
        }

        $teacher->update($updateData);

        // =====================================================
        // CREATE / UPDATE USER
        // =====================================================

        /*
        |--------------------------------------------------------------------------
        | users هو جدول الحسابات الموحد
        |--------------------------------------------------------------------------
        */

        $user = User::updateOrCreate(
            [
                'email' => $teacher->email
            ],
            [
                'Full_name' => $teacher->name,
                'password' => $teacher->password,

                // الدور في النظام الموحد
                'role' => 'teacher',

                'phone_number' => $teacher->phone,
                'address' => $teacher->address,
            ]
        );

        // =====================================================
        // ربط teacher بـ user
        // =====================================================

        if (Schema::hasColumn('teachers', 'user_id')) {
            $teacher->user_id = $user->id;
            $teacher->save();
        }

        // =====================================================
        // ملاحظة مهمة
        // =====================================================
        // لا نستخدم:
        //
        // $user->assignRole('teacher');
        //
        // لأن User يستخدم guard = web
        // بينما Role teacher في Seeder يستخدم guard = teacher.
        //
        // النظام الموحد يعتمد على:
        //
        // $user->role = 'teacher';
        //
        // لذلك لا نحتاج assignRole هنا.

        // =====================================================
        // TOKEN
        // =====================================================

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        // =====================================================
        // RESPONSE
        // =========================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Teacher registered successfully.',

            'data' => [
                'token' => $token,

                'role' => 'teacher',

                'user' => [
                    'id' => $user->id,
                    'name' => $user->Full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                ]
            ]
        ], 201);
    }


    // =========================================================
    // LOGIN
    // =========================================================

    /*
    |--------------------------------------------------------------------------
    | ملاحظة:
    | هذا Login موجود للحفاظ على الـ API القديم.
    | لكن في النظام الجديد يفضل استخدام:
    |
    | POST /api/login
    |
    | من UnifiedAuthController
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // البحث في users
        $user = User::where('email', $request->email)
            ->where('role', 'teacher')
            ->first();

        if (
            !$user ||
            !$user->password ||
            !Hash::check($request->password, $user->password)
        ) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // =====================================================
        // جلب المعلم
        // =====================================================

        $teacher = Teacher::where('email', $user->email)->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Teacher record not found.'
            ], 404);
        }

        // =====================================================
        // TOKEN FROM USER
        // =====================================================

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;

        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Logged in successfully.',

            'data' => [
                'token' => $token,

                'role' => 'teacher',

                'user' => [
                    'id' => $user->id,
                    'name' => $user->Full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],

                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'subject' => $teacher->subject,
                    'address' => $teacher->address,
                    'phone' => $teacher->phone,

                    'profile_image' => $teacher->profile_image
                        ? asset('storage/' . $teacher->profile_image)
                        : null,
                ]
            ]
        ], 200);
    }


    // =========================================================
    // LOGOUT
    // =========================================================

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully goodbye'
        ]);
    }


    // =========================================================
    // PROFILE
    // =========================================================

    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        if ($user->role !== 'teacher') {
            return response()->json([
                'message' => 'This account is not a teacher account.'
            ], 403);
        }

        // جلب بيانات المعلم
        $teacher = Teacher::where('email', $user->email)->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Teacher record not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User profile retrieved successfully.',

            'data' => [

                'id' => $user->id,

                'name' => $teacher->name,

                'email' => $user->email,

                'phone' => $teacher->phone,

                'address' => $teacher->address,

                'subject' => $teacher->subject,

                'profile_image' => $teacher->profile_image
                    ? asset('storage/' . $teacher->profile_image)
                    : null,

                'role' => $user->role,
            ]
        ], 200);
    }


    // =========================================================
    // UPDATE PROFILE
    // =========================================================

    public function updateProfile(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | مهم:
        | لم نعد نستخدم auth('teacher')
        |
        | لأن التوكن أصبح صادرًا من users.
        |--------------------------------------------------------------------------
        */

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        // التأكد أن الحساب معلم
        if ($user->role !== 'teacher') {
            return response()->json([
                'message' => 'This account is not a teacher account.'
            ], 403);
        }

        // =====================================================
        // جلب المعلم المرتبط بالإيميل
        // =====================================================

        $teacher = Teacher::where('email', $user->email)->first();

        if (!$teacher) {
            return response()->json([
                'message' => 'Teacher record not found.'
            ], 404);
        }

        // =====================================================
        // VALIDATION
        // =====================================================

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'subject' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // =====================================================
        // NAME
        // =====================================================

        if (isset($validated['name'])) {
            $teacher->name = $validated['name'];
        }

        // =====================================================
        // PASSWORD
        // =====================================================

        if (isset($validated['password'])) {
            $teacher->password = bcrypt(
                $validated['password']
            );
        }

        // =====================================================
        // OTHER DATA
        // =====================================================

        if (array_key_exists('subject', $validated)) {
            $teacher->subject = $validated['subject'];
        }

        if (array_key_exists('address', $validated)) {
            $teacher->address = $validated['address'];
        }

        if (array_key_exists('phone', $validated)) {
            $teacher->phone = $validated['phone'];
        }

        // =====================================================
        // PROFILE IMAGE
        // =====================================================

        if ($request->hasFile('profile_image')) {

            $profileImagePath = $request
                ->file('profile_image')
                ->store('teachers', 'public');

            $teacher->profile_image = $profileImagePath;
        }

        // =====================================================
        // SAVE TEACHER
        // =====================================================

        $teacher->save();

        // =====================================================
        // UPDATE USER
        // =====================================================

        $user->Full_name = $teacher->name;

        $user->phone_number = $teacher->phone;

        $user->address = $teacher->address;

        // إذا تم تغيير كلمة المرور
        if (isset($validated['password'])) {
            $user->password = $teacher->password;
        }

        // تأكيد Role
        $user->role = 'teacher';

        $user->save();

        // =====================================================
        // RESPONSE
        // =====================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',

            'data' => [

                'user' => [
                    'id' => $user->id,
                    'name' => $user->Full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone' => $user->phone_number,
                    'address' => $user->address,
                ],

                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'subject' => $teacher->subject,
                    'address' => $teacher->address,
                    'phone' => $teacher->phone,

                    'profile_image' => $teacher->profile_image
                        ? asset('storage/' . $teacher->profile_image)
                        : null,
                ]
            ]
        ], 200);
    }
}
