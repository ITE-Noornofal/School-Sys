<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\SchoolTrip;
use App\Models\Bus;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
class SchoolTripController extends Controller
{
    // فقط للمدير

public function store(Request $request)
{
    $request->validate([
        'title'         => 'required|string|max:255',
        'description'   => 'nullable|string',
        'trip_date'     => 'required|date|after_or_equal:today',
        'location'      => 'required|string|max:255',
        'class_room_id' => 'required|exists:class_rooms,id',
    ]);

    // جلب المشرف من التوكن (جلسة المشرف)
    $supervisor = auth('supervisor')->user();

    // تحقق من تسجيل الدخول كمشرف
    if (!$supervisor) {
        return response()->json([
            'message' => '❌ Unauthorized: You must be logged in as supervisor.',
        ], 401);
    }

    // تحقق من أن المشرف مسؤول عن الصف المطلوب
    $isAuthorized = $supervisor->classRooms()->where('id', $request->class_room_id)->exists();

    if (!$isAuthorized) {
        return response()->json([
            'message' => '❌ Unauthorized: You are not assigned to this class.',
        ], 403);
    }

    // تحقق من عدم وجود رحلة بنفس الصف ونفس التاريخ
    $existingTrip = SchoolTrip::where('class_room_id', $request->class_room_id)
        ->where('trip_date', $request->trip_date)
        ->first();

    if ($existingTrip) {
        return response()->json([
            'message' => '❌ A trip is already scheduled for this class on the same date.',
        ], 422);
    }

    // إنشاء الرحلة وربطها بالمشرف (من الجلسة)
    $trip = SchoolTrip::create([
        'title'         => $request->title,
        'description'   => $request->description,
        'trip_date'     => $request->trip_date,
        'location'      => $request->location,
        'class_room_id' => $request->class_room_id,
        'supervisor_id' => $supervisor->id,
    ]);

    return response()->json([
        'message' => '✅ School trip created successfully.',
        'trip' => $trip,
    ], 201);
}





    // فقط للطالب
    public function studentTrips()
    {
        $student = Auth::guard('student')->user();
        $trips = SchoolTrip::where('target_group_id', $student->target_group_id)->get();

        if ($trips->isEmpty()) {
            return response()->json(['message' => 'لا توجد رحلات مضافة حالياً']);
        }

        return response()->json($trips);
    }

    public function studentsByBus($busId)
{
    $bus = Bus::with('classRooms.students')->find($busId);

    if (!$bus) {
        return response()->json([
            'message' => 'Bus not found'
        ], 404);
    }

    $result = [];

    foreach ($bus->classRooms as $classRoom) {
        $result[] = [
            'class_room_id' => $classRoom->id,
            'class_room_name' => $classRoom->name,
            'students' => $classRoom->students()->select('id', 'full_name', 'email')->get(),
        ];
    }

    return response()->json([
        'bus' => $bus->name,
        'classes_and_students' => $result
    ]);
}

public function index(Request $request)
{
    $supervisor = auth('supervisor')->user();

    if (!$supervisor) {
        return response()->json([
            'message' => '❌ Unauthorized: You must be logged in as supervisor.',
        ], 401);
    }

    // جلب الرحلات المرتبطة بالشعب التي يشرف عليها هذا المشرف
    $trips = SchoolTrip::whereIn('class_room_id', function ($query) use ($supervisor) {
            $query->select('id')
                ->from('class_rooms')
                ->where('supervisor_id', $supervisor->id);
        })
        ->with('classRoom:id,name') // تحميل بيانات الصف المرتبط
        ->orderByDesc('trip_date')
        ->get();

    return response()->json([
        'message' => '✅ Trips fetched successfully.',
        'trips' => $trips,
    ]);
}




}
