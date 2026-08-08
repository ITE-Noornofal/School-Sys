<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\StudentGrade;  // ← تأكد من الاسم الصحيح
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth.teacher', 'role:teacher', 'permission:manage grades']);
    }

    public function index()
    {
        $grades = StudentGrade::where('teacher_id', Auth::id())
                              ->with('student')
                              ->get();

        return response()->json($grades);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject'    => 'required|string|max:255',
            'grade'      => 'required|numeric|min:0|max:100',
            'semester'   => 'nullable|string|max:50',
            'note'       => 'nullable|string',
        ]);

        $grade = StudentGrade::create([  // ← استخدام الاسم الصحيح
            'student_id' => $request->student_id,
            'teacher_id' => Auth::id(),
            'subject'    => $request->subject,
            'grade'      => $request->grade,
            'semester'   => $request->semester,
            'note'       => $request->note,
        ]);

        return response()->json([
            'message' => 'Grade added successfully',
            'grade'   => $grade
        ], 201);
    }

    // ... باقي الدوال


    public function update(Request $request, $id)
    {
        $grade = StudentGrade::findOrFail($id);

        if ($grade->teacher_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'subject' => 'sometimes|string',
            'grade' => 'sometimes|numeric|min:0|max:100',
        ]);

        $grade->update($request->only(['subject', 'grade']));

        return response()->json(['message' => 'Grade updated successfully', 'grade' => $grade]);
    }

    public function destroy($id)
    {
        $grade = StudentGrade::findOrFail($id);

        if ($grade->teacher_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $grade->delete();

        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
