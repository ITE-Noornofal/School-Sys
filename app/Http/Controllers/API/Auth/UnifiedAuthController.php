<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Guardian;

class UnifiedAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER موحد
    |--------------------------------------------------------------------------
    |
    | المستخدم يدخل EMAIL فقط.
    |
    | النظام يبحث عن الإيميل في:
    |
    | students
    | teachers
    | guardians
    |
    | إذا وجد الإيميل:
    | يتم إنشاء User في users
    | ويتم تحديد role تلقائياً.
    |
    */


    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));


        /*
        |--------------------------------------------------------------------------
        | 1. التأكد من عدم وجود User مسبقاً
        |--------------------------------------------------------------------------
        */

        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {

            return response()->json([
                'status' => 'error',
                'message' => 'An account already exists for this email.',
                'role' => $existingUser->role,
            ], 409);
        }


        /*
        |--------------------------------------------------------------------------
        | 2. البحث عن الطالب
        |--------------------------------------------------------------------------
        */

        $student = Student::where('email', $email)->first();

        if ($student) {

            $user = User::create([
                'Full_name' => $student->Full_name,
                'email' => $email,
                'password' => null,
                'role' => 'student',
            ]);
            $user->assignRole('student');


            /*
            | ربط الطالب بالـ User
            */

            $student->user_id = $user->id;
            $student->save();


            /*
            | Token مؤقت لإكمال التسجيل
            */

            $token = $user
                ->createToken('register_token')
                ->plainTextToken;


            return response()->json([

                'status' => 'success',

                'message' =>
                    'Student email verified successfully. Please set your password.',

                'data' => [

                    'token' => $token,

                    'role' => 'student',

                    'user' => [

                        'id' => $user->id,

                        'name' => $user->Full_name,

                        'email' => $user->email,

                        'role' => $user->role,

                    ],
                ],

            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | 3. البحث عن المعلم
        |--------------------------------------------------------------------------
        */

        $teacher = Teacher::where('email', $email)->first();

        if ($teacher) {

            $user = User::create([
                'Full_name' => $teacher->name,
                'email' => $email,
                'password' => null,
                'role' => 'teacher',
            ]);
            $user->assignRole('teacher');


            /*
            | Token مؤقت
            */

            $token = $user
                ->createToken('register_token')
                ->plainTextToken;


            return response()->json([

                'status' => 'success',

                'message' =>
                    'Teacher email verified successfully. Please set your password.',

                'data' => [

                    'token' => $token,

                    'role' => 'teacher',

                    'user' => [

                        'id' => $user->id,

                        'name' => $user->Full_name,

                        'email' => $user->email,

                        'role' => $user->role,

                    ],
                ],

            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | 4. البحث عن ولي الأمر
        |--------------------------------------------------------------------------
        */

        $guardian = Guardian::where('email', $email)->first();

        if ($guardian) {

            $user = User::create([
                'Full_name' => $guardian->name,
                'email' => $email,
                'password' => null,
                'role' => 'guardian',
            ]);
               $user->assignRole('guardian');

            /*
            | Token مؤقت
            */

            $token = $user
                ->createToken('register_token')
                ->plainTextToken;


            return response()->json([

                'status' => 'success',

                'message' =>
                    'Guardian email verified successfully. Please set your password.',

                'data' => [

                    'token' => $token,

                    'role' => 'guardian',

                    'user' => [

                        'id' => $user->id,

                        'name' => $user->Full_name,

                        'email' => $user->email,

                        'role' => $user->role,

                    ],
                ],

            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | 5. الإيميل غير موجود
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => 'error',

            'message' =>
                'This email is not registered in the system. Please contact the administrator.',

        ], 403);
    }


    /*
    |--------------------------------------------------------------------------
    | SET PASSWORD موحد
    |--------------------------------------------------------------------------
    |
    | Student + Teacher + Guardian
    |
    */


  public function setPassword(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized.'
        ], 401);
    }

    if (!in_array($user->role, [
        'student',
        'teacher',
        'guardian'
    ])) {
        return response()->json([
            'status' => 'error',
            'message' => 'This account cannot set a password.'
        ], 403);
    }

    if (!is_null($user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Password has already been set.'
        ], 409);
    }

    $request->validate([
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user->password = Hash::make($request->password);

    $user->save();

    // التأكد من وجود Role في Spatie
    if (!$user->hasRole($user->role)) {
        $user->assignRole($user->role);
    }

    // حذف Token التسجيل القديم
    if ($user->currentAccessToken()) {
        $user->currentAccessToken()->delete();
    }

    // إنشاء Token جديد
    $token = $user
        ->createToken('auth_token')
        ->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'Password set successfully.',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->Full_name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]
    ], 200);
}
    /*
    |--------------------------------------------------------------------------
    | LOGIN موحد
    |--------------------------------------------------------------------------
    |
    | Student + Teacher + Guardian
    |
    | البحث يتم فقط في users
    |
    */


   public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $email = strtolower(trim($request->email));

    // البحث في users فقط
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid login credentials.',
        ], 401);
    }

    // كلمة المرور غير موجودة
    if (!$user->password) {
        return response()->json([
            'status' => 'error',
            'message' => 'Password has not been set yet.',
        ], 403);
    }

    // فحص كلمة المرور
    if (!Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid login credentials.',
        ], 401);
    }

    // التأكد من وجود role في users
    if (!$user->role) {
        return response()->json([
            'status' => 'error',
            'message' => 'User role is not assigned.',
        ], 403);
    }

    // =====================================================
    // مزامنة users.role مع Spatie
    // =====================================================

    if (!$user->hasRole($user->role)) {
        $user->assignRole($user->role);
    }

    // =====================================================
    // حذف التوكنات القديمة
    // =====================================================

    $user->tokens()->delete();

    // =====================================================
    // إنشاء Token جديد
    // =====================================================

    $token = $user
        ->createToken('auth_token')
        ->plainTextToken;

    // =====================================================
    // بيانات المستخدم
    // =====================================================

    $data = [
        'id' => $user->id,
        'name' => $user->Full_name,
        'email' => $user->email,
        'role' => $user->role,
    ];

    // =====================================================
    // Student
    // =====================================================

    if ($user->role === 'student') {

        $data['student'] = Student::where(
            'email',
            $user->email
        )->first();
    }

    // =====================================================
    // Teacher
    // =====================================================

    elseif ($user->role === 'teacher') {

        $data['teacher'] = Teacher::where(
            'email',
            $user->email
        )->first();
    }

    // =====================================================
    // Guardian
    // =====================================================

    elseif ($user->role === 'guardian') {

        $data['guardian'] = Guardian::where(
            'email',
            $user->email
        )->first();
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Logged in successfully.',

        'data' => [
            'token' => $token,
            'user' => $data,
        ],
    ], 200);
}


    /*
    |--------------------------------------------------------------------------
    | ME
    |--------------------------------------------------------------------------
    */

    public function me(Request $request)
    {
        $user = $request->user();


        if (!$user) {

            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized.',
            ], 401);
        }


        $data = [

            'id' => $user->id,

            'name' => $user->Full_name,

            'email' => $user->email,

            'role' => $user->role,
        ];


        /*
        |--------------------------------------------------------------------------
        | Student
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'student') {

            $data['student'] =
                Student::where(
                    'email',
                    $user->email
                )->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Teacher
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'teacher') {

            $data['teacher'] =
                Teacher::where(
                    'email',
                    $user->email
                )->first();
        }


        /*
        |--------------------------------------------------------------------------
        | Guardian
        |--------------------------------------------------------------------------
        */

        elseif ($user->role === 'guardian') {

            $data['guardian'] =
                Guardian::where(
                    'email',
                    $user->email
                )->first();
        }


        return response()->json([

            'status' => 'success',

            'user' => $data,

        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $user = $request->user();


        if ($user && $user->currentAccessToken()) {

            $user
                ->currentAccessToken()
                ->delete();
        }


        return response()->json([

            'status' => 'success',

            'message' =>
                'Logged out successfully.',

        ]);
    }
}
