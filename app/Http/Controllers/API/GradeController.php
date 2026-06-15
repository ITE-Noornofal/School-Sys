<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller//اضافة  علامة  للطالب  عبر  الاستاذ
{
public function __construct()
{
    $this->middleware(['auth.teacher', 'role:teacher', 'permission:manage grades']);
}


    public function index()
    {
        $grades = Grade::where('teacher_id', Auth::id())->with('student')->get();
        return response()->json($grades);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string',
            'grade' => 'required|numeric|min:0|max:100',
        ]);

        $grade = Grade::create([
            'student_id' => $request->student_id,
            'teacher_id' => Auth::id(),
            'subject' => $request->subject,
            'grade' => $request->grade,
        ]);

        return response()->json(['message' => 'Grade added successfully', 'grade' => $grade], 201);
    }

    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

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
        $grade = Grade::findOrFail($id);

        if ($grade->teacher_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $grade->delete();

        return response()->json(['message' => 'Grade deleted successfully']);
    }
}
