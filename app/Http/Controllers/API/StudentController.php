<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Models\Complaint;
use Illuminate\Support\Facades\Crypt;

use App\Models\SchoolTrip;

class StudentController extends Controller
{
public function registerByEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $student = Student::where('email', $request->email)->first();

    if (! $student) {
        return response()->json([
            'status' => 'error',
            'message' => 'The email is not registered as a student.'
        ], 403);
    }

    if ($student->user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Account already exists for this email.'
        ], 409);
    }

    // Create user without password temporarily
    $user = User::create([
        'Full_name' => $student->Full_name,
        'email' => $student->email,
        'password' => null,
    ]);

    $user->assignRole('student');
    $student->user_id = $user->id;
    $student->save();

    $token = $user->createToken('student-token')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Email registered successfully. Please complete setting your password.',
        'data'    => [
            'token' => $token,
            'role'  => 'student'
        ]
    ], 201);
}

public function setPassword(Request $request)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'status' => 'error',
            'message' => 'Unauthorized access.'
        ], 401);
    }

    // منع تغيير كلمة المرور إذا كانت موجودة مسبقًا
    if ($user->password !== null) {
        return response()->json([
            'status' => 'error',
            'message' => 'Password has already been set.'
        ], 409);
    }

    $request->validate([
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user->password = bcrypt($request->password);
    $user->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Password has been set successfully.'
    ], 200);
}


public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid login credentials.'
        ], 401);
    }

    if (! $user->student) {
        return response()->json([
            'status' => 'error',
            'message' => 'This account is not associated with a student.'
        ], 403);
    }

    $token = $user->createToken('student-token')->plainTextToken;

    return response()->json([
        'status' => 'success',
        'message' => 'Logged in successfully.',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->Full_name,
                'email' => $user->email,
                // أضف حقول أخرى إذا أردت
            ],
            'student' => $user->student, // تأكد أن علاقة student محملة بشكل صحيح
            'token' => $token,
        ]
    ], 200);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'you logged out sucssesfully goodbye']);
    }





    public function profile(Request $request)
{
    return response()->json($request->user());
}



 public function saveProfile(Request $request)
{
    $user = Auth::user();

    if ($request->has('email')) {
        return response()->json([
            'message' => 'You can\'t edit the email, contact the admin'
        ], 403);
    }

    $allowedFields = [
        'Full_name',
        'father_name',
        'mother_name',
        'phone_number',
        'gender',
        'birth_date',
        'address',
        'academic_year',
        'password'
    ];

    $updated = false;

    foreach ($allowedFields as $field) {
        if ($request->filled($field)) {
            if ($field === 'password') {
                $user->password = Hash::make($request->password);
            } else {
                $user->$field = $request->$field;
            }
            $updated = true;
        }
    }

    if (!$updated) {
        return response()->json([
            'message' => 'No valid data was provided for update'
        ], 422);
    }

    $user->save();

    // تحديث اسم الطالب في جدول students إذا تم تعديل Full_name
    if ($request->filled('Full_name')) {
        $student = $user->student;
        if ($student) {
            $student->Full_name = $request->Full_name;
            $student->save();
        }
    }

    return response()->json([
        'message' => 'The profile information has been successfully saved',
        'student' => $user
    ]);
}








public function availableTrips()
{
    $user = auth()->user();

    if (!$user) {
        return response()->json([
            'message' => '❌ Unauthorized access.',
        ], 401);
    }

    $student = $user->student;

    if (!$student) {
        return response()->json([
            'message' => 'ℹ️ No student linked to this user.',
        ], 404);
    }

    // جلب الرحلات المؤكدة للطالب من جدول pivot
    $confirmedTrips = $student->schoolTrips()
        ->wherePivot('confirmation_status', 'confirmed')
        ->get();

    if ($confirmedTrips->isNotEmpty()) {
        $confirmed = $confirmedTrips->first();

        return response()->json([
            'message' => 'ℹ️ You already have a confirmed trip.',
            'confirmed_trip' => [
                'id' => $confirmed->id,
                'title' => $confirmed->title,
                'trip_date' => $confirmed->trip_date,
                'location' => $confirmed->location,
            ],
        ], 200);
    }

    // جلب الرحلات المتاحة حسب صف الطالب
    $trips = SchoolTrip::where('class_room_id', $student->class_room_id)
        ->orderBy('trip_date', 'asc')
        ->get();

    if ($trips->isEmpty()) {
        return response()->json([
            'message' => 'ℹ️ No available trips for your class at the moment.',
        ], 200);
    }

    $availableTrips = $trips->map(function ($trip) {
        return [
            'id' => $trip->id,
            'title' => $trip->title,
            'trip_date' => $trip->trip_date,
            'location' => $trip->location,
            'supervisor_id' => $trip->supervisor_id,
        ];
    });

    return response()->json([
        'message' => '✅ Available trips for your class.',
        'trips' => $availableTrips,
    ], 200);
}




public function storeByStudent(Request $request)
{
    $user = auth()->user();
    $student = $user->student;

    if (!$student) {
        return response()->json(['message' => '❌ You are not linked to a student record.'], 403);
    }

    $isAnonymous = $request->input('is_anonymous', false);
    $studentIdHash = hash('sha256', $student->id);

    if ($isAnonymous) {
        $anonymousCount = Complaint::where('student_id_hash', $studentIdHash)
            ->where('is_anonymous', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($anonymousCount >= 3) {
            return response()->json(['message' => '❌ You have exceeded the allowed number of anonymous complaints this month.'], 403);
        }
    }

    $complaintData = [
        'class_room_id' => $request->class_room_id,
        'content' => $request->content,
        'is_anonymous' => $isAnonymous,
        'status' => 'pending',
    ];

    if ($isAnonymous) {
        $complaintData['student_id'] = null;
        $complaintData['student_id_enc'] = Crypt::encryptString($student->id);
        $complaintData['student_id_hash'] = $studentIdHash;
    } else {
        $complaintData['student_id'] = $student->id;
        $complaintData['student_id_enc'] = null;
        $complaintData['student_id_hash'] = null;
    }

    $complaint = Complaint::create($complaintData);

    return response()->json(['message' => '✅ Complaint submitted successfully.', 'complaint' => $complaint]);
}



public function index()
{
    $user = auth()->user();
    $student = $user->student;

    if (!$student) {
        return response()->json([
            'message' => '❌ You are not linked to a student record.',
        ], 403);
    }

    $studentIdHash = hash('sha256', $student->id);

    $complaints = Complaint::with(['classRoom:id,name', 'student:id,Full_name'])
        ->where(function ($query) use ($student, $studentIdHash) {
            $query->where('student_id', $student->id)
                  ->orWhere('student_id_hash', $studentIdHash);
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($complaint) {
            return [
                'id'           => $complaint->id,
                'content'      => $complaint->content,
                'status'       => $complaint->status,
                'is_anonymous' => $complaint->is_anonymous,
                'created_at'   => $complaint->created_at->toDateTimeString(),
                'class_room'   => $complaint->classRoom?->name,
                'student'      => $complaint->is_anonymous ? null : (
                    $complaint->student ? [
                        'id'   => $complaint->student->id,
                        'name' => $complaint->student->Full_name,
                    ] : null
                ),
            ];
        });

    return response()->json([
        'message' => '✅ Complaints fetched successfully.',
        'complaints' => $complaints
    ]);
}




}










