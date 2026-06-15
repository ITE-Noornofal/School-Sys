<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;

class TeacherController extends Controller
{
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

    $teacher = Teacher::where('email', $validated['email'])->first();

    if (!$teacher) {
        return response()->json(['message' => 'Email not allowed. Contact admin.'], 403);
    }

    if ($teacher->password !== null) {
        return response()->json(['message' => 'Teacher already registered.'], 409);
    }

    $profileImagePath = null;
    if ($request->hasFile('profile_image')) {
        $profileImagePath = $request->file('profile_image')->store('teachers', 'public');
    }

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

if (isset($profileImagePath)) {
    $updateData['profile_image'] = $profileImagePath;
}

$teacher->update($updateData);

    $token = $teacher->createToken('teacher_token')->plainTextToken;
    $teacher->assignRole('teacher');

    // إرجاع فقط رسالة ورمز الحالة والتوكن
   return response()->json([
    'status'  => 'success',
    'message' => 'Teacher registered successfully.',
    'data'    => [
        'token' => $token,
        'role'  => 'teacher'
    ]
], 201);

}

    // Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:teachers,email',
            'password' => 'required',
        ]);

        $teacher = Teacher::where('email', $request->email)->first();

        if (!$teacher || !Hash::check($request->password, $teacher->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $teacher->createToken('teacher_token')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Logged in successfully.',
        'data'    => [
            'token'   => $token,
            'teacher' => [
                'name'           => $teacher->name,
                'email'          => $teacher->email,
                'subject'        => $teacher->subject,
                'address'        => $teacher->address,
                'phone'          => $teacher->phone,
                'profile_image'  => $teacher->profile_image
                    ? asset('storage/' . $teacher->profile_image)
                    : null,
            ]
        ]
], 200);

    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully goodbye']);
    }

    // Profile
 public function profile(Request $request)
{
    return response()->json([
        'status'  => 'success',
        'message' => 'User profile retrieved successfully.',
        'data'    => [
            'id'            => $request->user()->id,
            'name'          => $request->user()->name,
            'email'         => $request->user()->email,
            'phone'         => $request->user()->phone ?? null,
            'address'       => $request->user()->address ?? null,
            'profile_image' => $request->user()->profile_image ? asset('storage/' . $request->user()->profile_image) : null,
            // أضف الحقول التي تريدها هنا
        ]
    ], 200);
}



  public function updateProfile(Request $request)
{
    $teacher = auth('teacher')->user();

    if (!$teacher) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'password' => 'nullable|string|min:6|confirmed',
        'subject' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    if (isset($validated['name'])) {
        $teacher->name = $validated['name'];
    }

    if (isset($validated['password'])) {
        $teacher->password = bcrypt($validated['password']);
    }

    $teacher->subject = $validated['subject'] ?? $teacher->subject;
    $teacher->address = $validated['address'] ?? $teacher->address;
    $teacher->phone = $validated['phone'] ?? $teacher->phone;

    if ($request->hasFile('profile_image')) {
        $profileImagePath = $request->file('profile_image')->store('teachers', 'public');
        $teacher->profile_image = $profileImagePath;
    }

    // تأكد أن $teacher هو نموذج صحيح قبل الحفظ
    if (method_exists($teacher, 'save')) {
        $teacher->save();
    } else {
        return response()->json(['message' => 'Internal Server Error: Unable to save teacher'], 500);
    }

   return response()->json([
    'status' => 'success',
    'message' => 'Profile updated successfully',
    'data' => [
        'teacher' => [
            'name' => $teacher->name,
            'email' => $teacher->email,
            'subject' => $teacher->subject,
            'address' => $teacher->address,
            'phone' => $teacher->phone,
            'profile_image' => $teacher->profile_image ? asset('storage/' . $teacher->profile_image) : null,
        ]
    ]
], 200);










}}
