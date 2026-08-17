<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\User;

class AdminGuardianController extends Controller
{
    // =====================================================================
    // ADD GUARDIAN EMAIL
    // =====================================================================

    // الإدارة تضيف إيميل ولي الأمر المسموح له بالتسجيل
    public function addEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:guardians,email',
        ]);

        // التأكد أن الإيميل غير مستخدم في users
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            return response()->json([
                'status' => 'error',
                'message' => 'This email is already registered as a user.',
                'role' => $existingUser->role,
            ], 409);
        }

        // إنشاء سجل ولي الأمر
        $guardian = Guardian::create([
            'email' => $request->email,
            'name' => 'Pending Registration',
            'password' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Guardian email added successfully.',
            'guardian' => $guardian,
        ], 201);
    }


    // =====================================================================
    // UPDATE GUARDIAN
    // =====================================================================

    public function update(Request $request, $id)
    {
        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guardian not found.',
            ], 404);
        }

        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $guardian->update(
            $request->only('name', 'phone')
        );

        // تحديث الاسم في users أيضًا
        $user = User::where('email', $guardian->email)
            ->where('role', 'guardian')
            ->first();

        if ($user) {
            if ($request->filled('name')) {
                $user->Full_name = $request->name;
            }

            $user->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Guardian updated successfully.',
            'guardian' => $guardian,
        ]);
    }


    // =====================================================================
    // DELETE GUARDIAN
    // =====================================================================

    public function destroy($id)
    {
        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'status' => 'error',
                'message' => 'Guardian not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | حذف الحساب الموحد أيضًا
        |--------------------------------------------------------------------------
        */

        $user = User::where('email', $guardian->email)
            ->where('role', 'guardian')
            ->first();

        if ($user) {
            // حذف التوكنات
            $user->tokens()->delete();

            // حذف User
            $user->delete();
        }

        // حذف Guardian
        $guardian->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Guardian deleted successfully.',
        ]);
    }


    // =====================================================================
    // GET GUARDIANS
    // =====================================================================

    public function index()
    {
        $guardians = Guardian::latest()->get();

        return response()->json([
            'status' => 'success',
            'guardians' => $guardians,
        ]);
    }


    // =====================================================================
    // ASSIGN STUDENT TO GUARDIAN
    // =====================================================================

    public function assignStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'guardian_id' => 'required|exists:guardians,id',
        ]);

        $student = Student::findOrFail(
            $request->student_id
        );

        $guardian = Guardian::findOrFail(
            $request->guardian_id
        );

        // الطالب مرتبط مسبقًا
        if (!is_null($student->guardian_id)) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'This student is already assigned to a guardian and cannot be reassigned.',
            ], 403);
        }

        // ربط الطالب بولي الأمر
        $student->guardian_id = $guardian->id;
        $student->save();

        return response()->json([
            'status' => 'success',
            'message' =>
                'Student assigned to guardian successfully.',
            'student' => $student,
            'guardian' => $guardian,
        ]);
    }


    // =====================================================================
    // UNASSIGNED STUDENTS
    // =====================================================================

    public function unassignedStudents()
    {
        $students = Student::whereNull('guardian_id')
            ->get([
                'id',
                'full_name',
                'email',
                'class_room_id',
                'specialization_id',
            ]);

        return response()->json([
            'status' => 'success',
            'message' =>
                'Unassigned students fetched successfully.',
            'data' => $students,
        ]);
    }


    // =====================================================================
    // RECHARGE BALANCE
    // =====================================================================

    public function rechargeBalance(
        Request $request,
        Guardian $guardian
    ) {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $guardian->increment(
            'balance',
            $validated['amount']
        );

        // تحديث البيانات من قاعدة البيانات
        $guardian->refresh();

        return response()->json([
            'status' => 'success',
            'message' =>
                'Balance recharged successfully.',
            'guardian_id' =>
                $guardian->id,
            'new_balance' =>
                $guardian->balance,
            'added_amount' =>
                $validated['amount'],
        ]);
    }
}
