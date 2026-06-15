<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ClassTransfer;
use App\Models\ClassRoom;
use App\Models\DuePayment;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\DuePaymentTemplate;
use Illuminate\Support\Facades\Auth;


class ClassTransferController extends Controller
{
    /**
     * نقل الطالب إلى صف جديد
     */
public function store(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'guardian_id'=>'nullable',
        'to_class_room_id' => 'required|exists:class_rooms,id',
        'reason' => 'nullable|string|max:255',

    ]);

    // جلب بيانات الطالب والشعب القديمة والجديدة
    $student = Student::findOrFail($request->student_id);
    $oldClass = $student->classRoom;
    $newClass = ClassRoom::findOrFail($request->to_class_room_id);

    // تحقق لو الطالب بالفعل في نفس الشعبة
    if ($oldClass->id === $newClass->id) {
        return response()->json([
            'message' => '🚫 Student is already in this class.'
        ], 422);
    }

    // سجل عملية النقل
    ClassTransfer::create([
        'student_id' => $student->id,
        'from_class_room_id' => $oldClass->id,
        'to_class_room_id' => $newClass->id,
        'transferred_by' => auth()->id(),
        'reason' => $request->reason,
        'transfer_date' => now(),
    ]);

    // حدث الشعبة للطالب
    $student->update(['class_room_id' => $newClass->id]);

    // حذف الدفعات غير المدفوعة القديمة
    $student->duePayments()->where('status', 'unpaid')->delete();

    // إنشاء دفعات جديدة حسب الصف الجديد
    $newGradeId = $newClass->grade_id;
    $templates = DuePaymentTemplate::where('grade_id', $newGradeId)->get();

    foreach ($templates as $template) {
        DuePayment::create([
            'student_id' => $student->id,
            'guardian_id' => $student->guardian_id,  // هنا إضافة guardian_id
            'amount' => $template->amount,
            'description' => $template->description,
            'due_date' => now()->addDays(15), // عدل حسب سياستك
            'penalty' => 0,
            'status' => 'unpaid',
        ]);
    }

    return response()->json([
        'message' => '✅ Student transferred successfully, and due payments updated.',
    ]);
}

 /**
 * عرض سجل النقل لطالب معين
 */


public function history($id)
{
    $student = Student::find($id);

    if (!$student) {
        return response()->json([
            'message' => '❌ Student not found.'
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
            'message' => 'ℹ️ No transfer records found for this student.',
            'student_id' => $id,
            'student_name' => $student->full_name,
            'transfers' => [],
        ], 200);
    }

    $result = $transfers->map(function ($transfer) {
        return [
            'from_class' => $transfer->fromClassRoom?->name,
            'to_class' => $transfer->toClassRoom?->name,
            'transferred_by' => $transfer->transferredBy?->Full_name ?? 'Unknown',
            'reason' => $transfer->reason,
            'transfer_date' => Carbon::parse($transfer->transfer_date)->format('Y-m-d H:i'),
        ];
    });

    return response()->json([
        'message' => '✅ Transfer history retrieved successfully.',
        'student_id' => $student->id,
        'student_name' => $student->full_name,
        'transfers' => $result,
    ]);
}



public function guardianTransferHistory()
{
    $guardian = Auth::guard('guardian')->user();

    $children = $guardian->students()->with(['classTransfers.fromClassRoom', 'classTransfers.toClassRoom'])->get();

    $result = $children->map(function ($student) {
        return [
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'transfers' => $student->classTransfers->map(function ($transfer) {
                return [
                    'from_class' => $transfer->fromClassRoom?->name,
                    'to_class' => $transfer->toClassRoom?->name,
                    'transfer_date' => Carbon::parse($transfer->transfer_date)->format('Y-m-d H:i'),
                    'reason' => $transfer->reason,
                ];
            }),
        ];
    });

    return response()->json([
        'message' => '✅ Transfer history for your children.',
        'children' => $result
    ]);
}


}
