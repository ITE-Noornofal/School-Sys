<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Attendance;
use App\Models\Student;

class AttendanceController extends Controller
{
    // 🟢 تسجيل الحضور
    public function takeAttendance(Request $request)
    {
        $supervisor = auth('supervisor')->user();

        if (!$supervisor->can('take attendance')) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::findOrFail($request->student_id);
        $grade = $student->classRoom?->grade;

        if (!$grade || $grade->supervisor_id !== $supervisor->id) {
            return response()->json([
                'message' => '⛔️ You are not authorized to take attendance for this student\'s grade.',
            ], 403);
        }

        $existing = Attendance::where('student_id', $student->id)
            ->where('date', $request->date)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => '⚠️ Attendance already recorded for this student on this date.',
            ], 409);
        }

        $attendance = Attendance::create([
            'student_id' => $student->id,
            'date' => $request->date,
            'status' => $request->status,
            'taken_by_supervisor_id' => $supervisor->id,
        ]);

        return response()->json([
            'message' => '✅ Attendance recorded successfully.',
            'data' => $attendance,
        ], 201);
    }

    // 🟡 إلغاء الحضور
    public function cancelAttendance(Request $request)
    {
        $supervisor = auth('supervisor')->user();

        if (!$supervisor->can('take attendance')) {
            return response()->json(['message' => 'Unauthorized access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = Student::findOrFail($request->student_id);
        $grade = $student->classRoom?->grade;

        if (!$grade || $grade->supervisor_id !== $supervisor->id) {
            return response()->json([
                'message' => '⛔️ You are not authorized to cancel attendance for this student\'s grade.',
            ], 403);
        }

        $attendance = Attendance::where('student_id', $student->id)
            ->where('date', $request->date)
            ->where('taken_by_supervisor_id', $supervisor->id)
            ->first();

        if (!$attendance) {
            return response()->json([
                'message' => '❌ No attendance record found for this student on this date by you.',
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'message' => '✅ Attendance record deleted successfully.',
        ]);
    }

    // 🔵 عرض التقارير
public function viewReport(Request $request)
{
    $supervisor = auth('supervisor')->user();

    if (!$supervisor->can('view attendance')) {
        return response()->json(['message' => 'Unauthorized access.'], 403);
    }

    $attendanceRecords = Attendance::whereHas('student.classRoom.grade', function ($query) use ($supervisor) {
        $query->where('supervisor_id', $supervisor->id);
    })->with('student')->orderBy('date', 'desc')->get();

    $transformedData = $attendanceRecords->map(function ($record) {
        return [
            'attendance_id' => $record->id,
            'date' => $record->date,
            'status' => $record->status,
            'student' => [
                'id' => $record->student->id,
                'name' => $record->student->Full_name,
                'email' => $record->student->email,
                'class_room_id' => $record->student->class_room_id,
            ],
        ];
    });

    return response()->json([
        'message' => '✅ Attendance report fetched successfully.',
        'data' => $transformedData,
    ]);
}

}
