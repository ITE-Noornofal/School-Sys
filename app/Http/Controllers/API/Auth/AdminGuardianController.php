<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;

class AdminGuardianController extends Controller
{
    // 🟢 إضافة إيميل ولي أمر مسموح له بالتسجيل
    public function addEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:guardians,email',
        ]);

        $guardian = Guardian::create([
            'email' => $request->email,
            'name' => 'Pending Registration',
        ]);

        return response()->json([
            'message' => '✅ Guardian email added successfully.',
            'guardian' => $guardian,
        ]);
    }

 public function update(Request $request, $id)
{
    $guardian = Guardian::find($id);

    if (!$guardian) {
        return response()->json([
            'message' => '❌ Guardian not found.',
        ], 404);
    }

    $request->validate([
        'name' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
    ]);

    $guardian->update($request->only('name', 'phone'));

    return response()->json([
        'message' => '✅ Guardian updated successfully.',
        'guardian' => $guardian,
    ]);
}


    // 🔴 حذف ولي أمر
 public function destroy($id)
{
    $guardian = Guardian::find($id);

    if (! $guardian) {
        return response()->json([
            'message' => '❌ Guardian not found.'
        ], 404);
    }

    $guardian->delete();

    return response()->json([
        'message' => '🗑️ Guardian deleted successfully.'
    ]);
}

    // 📄 عرض قائمة أولياء الأمور (اختياري)
    public function index()
    {
        $guardians = Guardian::latest()->get();

        return response()->json([
            'guardians' => $guardians
        ]);
    }

public function assignStudent(Request $request)
{
    $request->validate([
        'student_id' => 'required|exists:students,id',
        'guardian_id' => 'required|exists:guardians,id',
    ]);

    $student = Student::findOrFail($request->student_id);

    // التحقق ما إذا كان الطالب مرتبطًا مسبقًا بولي أمر
    if (!is_null($student->guardian_id)) {
        return response()->json([
            'message' => '❌ This student is already assigned to a guardian and cannot be reassigned.',
        ], 403);
    }

    $student->guardian_id = $request->guardian_id;
    $student->save();

    return response()->json([
        'message' => '✅ Student assigned to guardian successfully.',
        'student' => $student
    ]);
}


public function unassignedStudents()
{
    $students = Student::whereNull('guardian_id')->get([
        'id',
        'full_name',
        'email',
        'class_room_id',
        'specialization_id',
    ]);

    return response()->json([
        'message' => '✅ Unassigned students fetched successfully.',
        'data' => $students
    ]);
}


public function rechargeBalance(Request $request, Guardian $guardian)
{
    $validated = $request->validate([
        'amount' => 'required|numeric|min:0.01',
    ]);

    $guardian->increment('balance', $validated['amount']);

    return response()->json([
        'message' => '✅ Balance recharged successfully.',
        'guardian_id' => $guardian->id,
        'new_balance' => $guardian->balance,
        'added_amount' => $validated['amount'],
    ]);
}

}
