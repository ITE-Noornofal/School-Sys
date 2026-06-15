<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


use Spatie\Permission\Models\Role;

use Illuminate\Validation\ValidationException;
use App\Models\Supervisor;
use App\Models\SchoolTrip;
use App\Models\Student;
use App\Models\ClassTransfer;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;

class SupervisorAuthController extends Controller
{
     public function register(Request $request)
{
    $request->validate([
        'name'     => 'required|string',
        'email'    => 'required|email|exists:supervisors,email',
        'password' => 'required|min:6|confirmed',
    ]);

    $supervisor = Supervisor::where('email', $request->email)->first();

    if ($supervisor->password) {
        return response()->json([
            'status' => false,
            'message' => 'This email is already registered.',
        ], 422);
    }

    $supervisor->update([
        'name' => $request->name,
        'password' => Hash::make($request->password),
    ]);

    // ✅ إعطاء الدور "supervisor"
    $supervisor->assignRole('supervisor');

    return response()->json([
        'status' => true,
        'message' => 'Supervisor registered successfully.',
        'data' => [
            'id' => $supervisor->id,
            'name' => $supervisor->name,
            'email' => $supervisor->email,
            'roles' => $supervisor->getRoleNames(),
            'created_at' => $supervisor->created_at,
            'updated_at' => $supervisor->updated_at,
        ],
    ], 201);
}
   // ✅ تسجيل الدخول
 public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $supervisor = Supervisor::where('email', $request->email)->first();

    if (!$supervisor || !Hash::check($request->password, $supervisor->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // إنشاء التوكن
$token = $supervisor->createToken('supervisor-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $supervisor,
    ]);
}
    // ✅ تسجيل الخروج
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully.'
        ]);
    }

    // ✅ تعيين كلمة مرور جديدة
    public function setPassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);

        $supervisor = $request->user();
        $supervisor->password = Hash::make($request->password);
        $supervisor->save();

        return response()->json([
            'status' => true,
            'message' => 'Password set successfully.'
        ]);
    }

    // ✅ حفظ البيانات الشخصية (إنشاء بروفايل)
    public function saveProfile(Request $request)
    {
        $request->validate([
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ]);

        $supervisor = $request->user();

        $supervisor->update([
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Profile saved successfully.',
            'data' => $supervisor
        ]);
    }

    // ✅ عرض الملف الشخصي
    public function profile(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $request->user()
        ]);
    }

    // ✅ تعديل الملف الشخصي
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:supervisors,email,' . $request->user()->id,
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ]);

        $supervisor = $request->user();

        $supervisor->update($request->only('name', 'email', 'phone', 'address'));

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => $supervisor
        ]);
    }


   public function confirmedStudents($tripId)
{
    // جلب الرحلة مع الطلاب، الصف، الباص، والمشرف
    $trip = SchoolTrip::with(['students.classRoom', 'bus', 'supervisor'])->find($tripId);

    if (!$trip) {
        return response()->json(['message' => '❌ Trip not found.'], 404);
    }

    // جلب المشرف الحالي من الجلسة (guard supervisor)
    $currentSupervisor = auth('supervisor')->user();

    // التحقق من تسجيل دخول المشرف وصلاحيته
    if (!$currentSupervisor) {
        return response()->json(['message' => '❌ You must be logged in as supervisor.'], 401);
    }

    // التحقق أن المشرف الحالي هو المشرف المسؤول عن الرحلة
    if (intval($trip->supervisor_id) !== intval($currentSupervisor->id)) {
        return response()->json(['message' => '❌ Access denied. You are not the supervisor of this trip.'], 403);
    }

    // جلب الطلاب المثبتين في الرحلة
    $students = $trip->students->map(function ($student) {
        return [
            'id'         => $student->id,
            'full_name'  => $student->full_name,
            'email'      => $student->email,
            'class_name' => $student->classRoom?->name,
        ];
    });

    return response()->json([
        'message' => '✅ Confirmed students fetched successfully.',
        'trip' => [
            'id'        => $trip->id,
            'trip_date' => $trip->trip_date,
            'bus'       => $trip->bus ? [
                'id'            => $trip->bus->id,
                'name'          => $trip->bus->name,
                'license_plate' => $trip->bus->license_plate,
            ] : null,
        ],
        'confirmed_students_count' => $students->count(),
        'students' => $students,
    ]);
}



public function history($id)
{
    $supervisor = auth('supervisor')->user();

    if (!$supervisor) {
        return response()->json([
            'message' => '❌ Unauthorized: You must be logged in as supervisor.',
        ], 401);
    }

    // التحقق إذا الطالب يتبع لشعبة يشرف عليها هذا المشرف
    $student = Student::where('id', $id)
        ->whereHas('classRoom', function ($query) use ($supervisor) {
            $query->where('supervisor_id', $supervisor->id);
        })
        ->first();

    if (!$student) {
        return response()->json([
            'message' => '🚫 You are not authorized to view this student\'s transfer history.',
        ], 403);
    }

    // جلب سجل النقل
    $transfers = ClassTransfer::where('student_id', $id)
        ->with([
            'fromClassRoom:id,name',
            'toClassRoom:id,name',
            'transferredBy:id,Full_name,email'
        ])
        ->orderByDesc('transfer_date')
        ->get();

    $result = $transfers->map(function ($transfer) {
        return [
            'from_class'     => $transfer->fromClassRoom?->name,
            'to_class'       => $transfer->toClassRoom?->name,
            'transferred_by' => $transfer->transferredBy?->Full_name ?? 'Unknown',
            'reason'         => $transfer->reason,
            'transfer_date'  => Carbon::parse($transfer->transfer_date)->format('Y-m-d H:i'),
        ];
    });

    return response()->json([
        'message' => '✅ Transfer history retrieved successfully.',
        'student_id' => $student->id,
        'student_name' => $student->full_name,
        'transfers' => $result,
    ]);
}


}



