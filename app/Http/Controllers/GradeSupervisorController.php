<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
 use App\Models\Supervisor;
use App\Models\Grade;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\DB;



class GradeSupervisorController extends Controller//كونترولر يجعل الادمن يضيف  مشرفين  والتعديل  عليه
{
public function assign(Request $request)
{
    $request->validate([
        'supervisor_id' => 'required|exists:supervisors,id',
        'grade_ids' => 'required|array',
        'grade_ids.*' => 'exists:grades,id',
    ]);

    $supervisorId = $request->supervisor_id;
    $gradeIds = $request->grade_ids;

    foreach ($gradeIds as $gradeId) {
        $grade = Grade::find($gradeId);

        if (!$grade) {
            continue; // skip if grade not found
        }

        if ($grade->supervisor_id === $supervisorId) {
            return response()->json([
                'message' => "The supervisor is already assigned to grade ID {$gradeId}."
            ], 409);
        }

        if ($grade->supervisor_id !== null && $grade->supervisor_id !== $supervisorId) {
            return response()->json([
                'message' => "Grade ID {$gradeId} already has a different supervisor assigned."
            ], 409);
        }

        // Assign supervisor to grade
        $grade->supervisor_id = $supervisorId;
        $grade->save();

        // Assign supervisor to related class rooms
        ClassRoom::where('grade_id', $gradeId)
            ->update(['supervisor_id' => $supervisorId]);
    }

    return response()->json(['message' => '✅ Supervisor successfully assigned to the selected grades and their classrooms.']);
}



public function unassign(Request $request)
{
    $request->validate([
        'grade_id' => 'required|exists:grades,id',
        // إذا تريد فقط إزالة المشرف بدون تعيين آخر، اجعل supervisor_id اختياري
        'supervisor_id' => 'nullable|exists:supervisors,id',
    ]);

    $grade = Grade::find($request->grade_id);

    if (!$grade) {
        return response()->json(['message' => 'Grade not found.'], 404);
    }

    // تحقق إذا كان هناك مشرف معين حاليًا
    if ($grade->supervisor_id === null) {
        return response()->json(['message' => 'This grade does not have a supervisor assigned.'], 409);
    }

    // إذا تم إرسال supervisor_id و مختلف عن المشرف الحالي، يمكن تعديل المشرف
    if ($request->filled('supervisor_id')) {
        if ($grade->supervisor_id === $request->supervisor_id) {
            return response()->json(['message' => 'This supervisor is already assigned to the grade.'], 409);
        }

        // تحديث المشرف
        $grade->supervisor_id = $request->supervisor_id;
        $grade->save();

        // تحديث شعب الصف
        ClassRoom::where('grade_id', $grade->id)
            ->update(['supervisor_id' => $request->supervisor_id]);

        return response()->json(['message' => 'Supervisor changed successfully for the grade and its classrooms.']);
    }

    // إذا لم يتم إرسال supervisor_id، نزيل المشرف الحالي (إلغاء التعيين)
    $grade->supervisor_id = null;
    $grade->save();

    // إزالة المشرف من شعب الصف
    ClassRoom::where('grade_id', $grade->id)
        ->update(['supervisor_id' => null]);

    return response()->json(['message' => 'Supervisor removed successfully from the grade and its classrooms.']);
}

















public function move(Request $request)
{
    $request->validate([
        'supervisor_id' => 'required|exists:supervisors,id',
        'from_grade_id' => 'required|exists:grades,id',
        'to_grade_id' => 'required|exists:grades,id|different:from_grade_id',
    ]);

    $supervisorId = $request->supervisor_id;
    $fromGrade = Grade::findOrFail($request->from_grade_id);
    $toGrade = Grade::findOrFail($request->to_grade_id);

    if ($fromGrade->supervisor_id !== $supervisorId) {
        return response()->json([
            'message' => '❌ This supervisor is not assigned to the source grade.',
        ], 403);
    }

    if (!is_null($toGrade->supervisor_id)) {
        return response()->json([
            'message' => '⚠️ The target grade already has a supervisor.',
        ], 409);
    }

    // تجريد المشرف من الصف القديم
    $fromGrade->update(['supervisor_id' => null]);

    // تعيين المشرف على الصف الجديد
    $toGrade->update(['supervisor_id' => $supervisorId]);

    // تحديث الشعب المرتبطة بالصف القديم لإزالة المشرف
    DB::table('class_rooms')
        ->where('grade_id', $fromGrade->id)
        ->where('supervisor_id', $supervisorId)
        ->update(['supervisor_id' => null]);

    // تحديث الشعب المرتبطة بالصف الجديد لإضافة المشرف
    DB::table('class_rooms')
        ->where('grade_id', $toGrade->id)
        ->update(['supervisor_id' => $supervisorId]);

    return response()->json([
        'message' => '✅ Supervisor moved successfully and class rooms updated.',
    ]);
}

}

