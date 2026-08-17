<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ClassTransfer;
use App\Models\ClassRoom;
use App\Models\DuePayment;
use Carbon\Carbon;
use App\Models\DuePaymentTemplate;
use Illuminate\Support\Facades\Auth;

class ClassTransferController extends Controller
{
    /**
     * Transfer student to a new class
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'guardian_id' => 'nullable',
            'to_class_room_id' => 'required|exists:class_rooms,id',
            'reason' => 'nullable|string|max:255',
        ]);

        // Get student
        $student = Student::findOrFail($request->student_id);

        // Get student's current class
        $oldClass = $student->classRoom;

        // Student is not assigned to any class
        if (!$oldClass) {
            return response()->json([
                'success' => false,
                'message' => 'The student is not assigned to any class.'
            ], 422);
        }

        // Get the selected new class
        $newClass = ClassRoom::findOrFail($request->to_class_room_id);

        // =========================================================
        // Check if the student is already in the selected class
        // =========================================================
        if ((int) $oldClass->id === (int) $newClass->id) {
            return response()->json([
                'success' => false,
                'message' => 'The student is already assigned to this class. No changes were made.',
                'student_id' => $student->id,
                'current_class' => $oldClass->name,
                'current_class_id' => $oldClass->id,
            ], 422);
        }

        // =========================================================
        // Create transfer record
        // =========================================================
        ClassTransfer::create([
            'student_id' => $student->id,
            'from_class_room_id' => $oldClass->id,
            'to_class_room_id' => $newClass->id,
            'transferred_by' => auth()->id(),
            'reason' => $request->reason,
            'transfer_date' => now(),
        ]);

        // =========================================================
        // Update student's class
        // =========================================================
        $student->update([
            'class_room_id' => $newClass->id
        ]);

        // =========================================================
        // Delete old unpaid payments
        // =========================================================
        $student->duePayments()
            ->where('status', 'unpaid')
            ->delete();

        // =========================================================
        // Get grade level ID of the new class
        // =========================================================
        $newGradeId = $newClass->grade_id;

        // =========================================================
        // Get payment templates for the new grade level
        // =========================================================
        $templates = DuePaymentTemplate::where(
            'grade_level_id',
            $newGradeId
        )->get();

        // =========================================================
        // Create new payments
        // =========================================================
        foreach ($templates as $template) {
            DuePayment::create([
                'student_id' => $student->id,
                'guardian_id' => $student->guardian_id,
                'amount' => $template->amount,
                'description' => $template->description,
                'due_date' => now()->addDays(15),
                'penalty' => 0,
                'status' => 'unpaid',
            ]);
        }

        // =========================================================
        // Success response
        // =========================================================
        return response()->json([
            'success' => true,
            'message' => 'Student transferred successfully and class payments have been updated.',
            'data' => [
                'student_id' => $student->id,
                'from_class' => $oldClass->name,
                'from_class_id' => $oldClass->id,
                'to_class' => $newClass->name,
                'to_class_id' => $newClass->id,
            ]
        ], 200);
    }


    /**
     * Get transfer history for a specific student
     */
    public function history($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.'
            ], 404);
        }

        $transfers = ClassTransfer::where('student_id', $id)
            ->with([
                'fromClassRoom:id,name',
                'toClassRoom:id,name',
                'transferredBy:id,Full_name,email'
            ])
            ->orderByDesc('transfer_date')
            ->get();

        if ($transfers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No transfer records found for this student.',
                'student_id' => $id,
                'student_name' => $student->full_name,
                'transfers' => [],
            ], 200);
        }

        $result = $transfers->map(function ($transfer) {
            return [
                'from_class' => $transfer->fromClassRoom?->name,
                'to_class' => $transfer->toClassRoom?->name,

                'transferred_by' =>
                    $transfer->transferredBy?->Full_name ?? 'Unknown',

                'reason' => $transfer->reason,

                'transfer_date' => Carbon::parse(
                    $transfer->transfer_date
                )->format('Y-m-d H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer history retrieved successfully.',
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'transfers' => $result,
        ], 200);
    }


    /**
     * Get transfer history for guardian's children
     */
    public function guardianTransferHistory()
    {
        $guardian = Auth::guard('guardian')->user();

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian is not authenticated.'
            ], 401);
        }

        $children = $guardian->students()
            ->with([
                'classTransfers.fromClassRoom',
                'classTransfers.toClassRoom'
            ])
            ->get();

        $result = $children->map(function ($student) {
            return [
                'student_id' => $student->id,
                'student_name' => $student->full_name,

                'transfers' => $student->classTransfers->map(
                    function ($transfer) {
                        return [
                            'from_class' =>
                                $transfer->fromClassRoom?->name,

                            'to_class' =>
                                $transfer->toClassRoom?->name,

                            'transfer_date' => Carbon::parse(
                                $transfer->transfer_date
                            )->format('Y-m-d H:i'),

                            'reason' => $transfer->reason,
                        ];
                    }
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Transfer history for your children retrieved successfully.',
            'children' => $result
        ], 200);
    }
}
