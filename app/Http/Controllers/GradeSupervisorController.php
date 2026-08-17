<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supervisor;
use App\Models\GradeLevel;
use Illuminate\Support\Facades\DB;

class GradeSupervisorController extends Controller
{
    /**
     * Assign supervisor to one or multiple grade levels
     */
    public function assign(Request $request)
    {
        $request->validate([
            'supervisor_id' => 'required|exists:supervisors,id',
            'grade_ids' => 'required|array|min:1',
            'grade_ids.*' => 'required|exists:grade_levels,id',
        ]);

        $supervisorId = (int) $request->supervisor_id;
        $gradeIds = $request->grade_ids;

        DB::transaction(function () use ($supervisorId, $gradeIds) {

            foreach ($gradeIds as $gradeId) {

                $gradeLevel = GradeLevel::findOrFail($gradeId);

                // نفس المشرف معين مسبقًا
                if ((int) $gradeLevel->supervisor_id === $supervisorId) {
                    abort(response()->json([
                        'success' => false,
                        'message' => "Supervisor is already assigned to grade level ID {$gradeId}."
                    ], 409));
                }

                // يوجد مشرف آخر معين
                if (
                    !is_null($gradeLevel->supervisor_id) &&
                    (int) $gradeLevel->supervisor_id !== $supervisorId
                ) {
                    abort(response()->json([
                        'success' => false,
                        'message' => "Grade level ID {$gradeId} already has another supervisor assigned."
                    ], 409));
                }

                // تعيين المشرف للـ GradeLevel
                $gradeLevel->supervisor_id = $supervisorId;
                $gradeLevel->save();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Supervisor successfully assigned to the selected grade levels.'
        ], 200);
    }


    /**
     * Remove supervisor from a grade level
     */
    public function unassign(Request $request)
    {
        $request->validate([
            'grade_id' => 'required|exists:grade_levels,id',
        ]);

        $gradeLevel = GradeLevel::findOrFail($request->grade_id);

        // لا يوجد مشرف حاليًا
        if (is_null($gradeLevel->supervisor_id)) {
            return response()->json([
                'success' => false,
                'message' => 'This grade level does not have a supervisor assigned.'
            ], 409);
        }

        // إزالة المشرف
        $gradeLevel->supervisor_id = null;
        $gradeLevel->save();

        return response()->json([
            'success' => true,
            'message' => 'Supervisor removed successfully from the grade level.'
        ], 200);
    }


    /**
     * Change supervisor of a grade level
     */
    public function change(Request $request)
    {
        $request->validate([
            'grade_id' => 'required|exists:grade_levels,id',
            'supervisor_id' => 'required|exists:supervisors,id',
        ]);

        $gradeLevel = GradeLevel::findOrFail($request->grade_id);

        $newSupervisorId = (int) $request->supervisor_id;

        // نفس المشرف
        if ((int) $gradeLevel->supervisor_id === $newSupervisorId) {
            return response()->json([
                'success' => false,
                'message' => 'This supervisor is already assigned to the grade level.'
            ], 409);
        }

        // تغيير المشرف
        $gradeLevel->supervisor_id = $newSupervisorId;
        $gradeLevel->save();

        return response()->json([
            'success' => true,
            'message' => 'Supervisor changed successfully for the grade level.'
        ], 200);
    }


    /**
     * Move supervisor from one grade level to another
     */
    public function move(Request $request)
    {
        $request->validate([
            'supervisor_id' => 'required|exists:supervisors,id',

            'from_grade_id' => [
                'required',
                'exists:grade_levels,id'
            ],

            'to_grade_id' => [
                'required',
                'exists:grade_levels,id',
                'different:from_grade_id'
            ],
        ]);

        $supervisorId = (int) $request->supervisor_id;

        $fromGradeLevel = GradeLevel::findOrFail(
            $request->from_grade_id
        );

        $toGradeLevel = GradeLevel::findOrFail(
            $request->to_grade_id
        );

        // التأكد أن المشرف موجود على الصف القديم
        if ((int) $fromGradeLevel->supervisor_id !== $supervisorId) {
            return response()->json([
                'success' => false,
                'message' => 'This supervisor is not assigned to the source grade level.'
            ], 403);
        }

        // التأكد أن الصف الجديد لا يملك مشرفًا
        if (!is_null($toGradeLevel->supervisor_id)) {
            return response()->json([
                'success' => false,
                'message' => 'The target grade level already has a supervisor assigned.'
            ], 409);
        }

        DB::transaction(function () use (
            $fromGradeLevel,
            $toGradeLevel,
            $supervisorId
        ) {

            // إزالة المشرف من الصف القديم
            $fromGradeLevel->supervisor_id = null;
            $fromGradeLevel->save();

            // تعيين المشرف للصف الجديد
            $toGradeLevel->supervisor_id = $supervisorId;
            $toGradeLevel->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Supervisor moved successfully from the old grade level to the new grade level.'
        ], 200);
    }


    /**
     * Get all grade levels with their supervisors
     */
    public function index()
    {
        $gradeLevels = GradeLevel::with('supervisor')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gradeLevels
        ], 200);
    }


    /**
     * Get all supervisors with their grade levels
     */
    public function supervisors()
    {
        $supervisors = Supervisor::with('gradeLevels')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $supervisors
        ], 200);
    }


    /**
     * Get one supervisor with all assigned grade levels
     */
    public function showSupervisor($supervisorId)
    {
        $supervisor = Supervisor::with('gradeLevels')
            ->find($supervisorId);

        if (!$supervisor) {
            return response()->json([
                'success' => false,
                'message' => 'Supervisor not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supervisor
        ], 200);
    }


    /**
     * Get one grade level with its supervisor
     */
    public function showGrade($gradeId)
    {
        $gradeLevel = GradeLevel::with('supervisor')
            ->find($gradeId);

        if (!$gradeLevel) {
            return response()->json([
                'success' => false,
                'message' => 'Grade level not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $gradeLevel
        ], 200);
    }
}
