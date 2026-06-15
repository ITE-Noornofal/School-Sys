<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;

use App\Models\Student;
use App\Models\Payment;
use App\Models\SchoolTrip;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\DuePayment;

class GuardianController extends Controller
{
    // عرض بيانات ولي الأمر (الذي سجل الدخول)
    public function profile(Request $request)
    {
        $guardian = $request->user(); // auth()->user() أيضا
        return response()->json($guardian);
    }

    // تحديث بيانات ولي الأمر (مثلاً الاسم والهاتف)
    public function updateProfile(Request $request)
    {
        $guardian = $request->user();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:6|confirmed',
        ]);

        $guardian->name = $request->name ?? $guardian->name;
        $guardian->phone = $request->phone ?? $guardian->phone;

        if ($request->filled('password')) {
            $guardian->password = Hash::make($request->password);
        }

        $guardian->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'guardian' => $guardian,
        ]);
    }

    // عرض الأبناء المرتبطين بولي الأمر
    public function children(Request $request)
    {
        $guardian = $request->user();

        $children = $guardian->students()->get();

        return response()->json($children);
    }


     // 🟢 دالة التسجيل
public function register(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:guardians,email',
        'name' => 'required|string|max:255',
        'password' => 'required|string|min:6|confirmed',
        'phone' => 'nullable|string|max:20',
    ]);

    // جلب ولي الأمر الذي أضافه المدير
    $guardian = Guardian::where('email', $request->email)->first();

    if ($guardian->password) {
        return response()->json([
            'message' => '❌ This guardian is already registered.'
        ], 409);
    }

    // تحديث معلوماته وإكمال التسجيل
    $guardian->update([
        'name'     => $request->name,
        'password' => Hash::make($request->password),
        'phone'    => $request->phone,
    ]);

    $guardian->assignRole('guardian');

    $token = $guardian->createToken('guardian-token')->plainTextToken;

    return response()->json([
        'message' => '✅ Guardian registered successfully.',
        'token'   => $token,
        'guardian' => $guardian,
    ]);
}
    // 🟡 دالة تسجيل الدخول
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $guardian = Guardian::where('email', $request->email)->first();

        if (! $guardian || ! Hash::check($request->password, $guardian->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 🔁 تأكد من وجود الدور
        if (! $guardian->hasRole('guardian')) {
            $guardian->assignRole('guardian');
        }

        $token = $guardian->createToken('guardian-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'guardian' => $guardian,
        ]);
    }



public function dashboard()
{
    $guardian = auth()->user();

    // جلب الأبناء مع الصف والمشرف
    $children = $guardian->students()
        ->with([
            'classRoom:id,name,supervisor_id',
            'classRoom.supervisor:id,name,email',
            'schoolTrips' => function ($query) {
                $query->wherePivot('confirmation_status', 'confirmed');
            }
        ])
        ->get(['id', 'full_name', 'class_room_id']);

    // مجموع المدفوعات المدفوعة من ولي الأمر
    $totalPaid = $guardian->payments()->where('status', 'paid')->sum('amount');

    // مجموع الدفعات المستحقة (غير المدفوعة)
    $totalDue = $guardian->duePayments()->where('status', 'unpaid')->sum('amount');

    // عدد الدفعات المدفوعة
    $paymentsCount = $guardian->payments()->where('status', 'paid')->count();

    // عدد الدفعات غير المدفوعة
    $duePaymentsCount = $guardian->duePayments()->where('status', 'unpaid')->count();

    // بناء بيانات الأبناء
    $childrenData = $children->map(function ($student) {
        $tripCount = $student->schoolTrips->count();
        $tripFees = $tripCount * 25; // رسوم الرحلة الواحدة

        return [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'class_room' => $student->classRoom?->name,
            'supervisor' => $student->classRoom?->supervisor
                ? [
                    'name' => $student->classRoom->supervisor->name,
                    'email' => $student->classRoom->supervisor->email,
                ]
                : null,
            'confirmed_trips_count' => $tripCount,
            'confirmed_trips_fees' => $tripFees,
        ];
    });

    return response()->json([
        'guardian' => [
            'id' => $guardian->id,
            'name' => $guardian->name,
            'email' => $guardian->email,
        ],
        'children_count' => $children->count(),
        'children' => $childrenData,
        'total_paid' => $totalPaid,
        'payments_count' => $paymentsCount,
        'total_due' => $totalDue,
        'due_payments_count' => $duePaymentsCount,
        'message' => '✅ Guardian dashboard loaded with updated payment and trip info.'
    ]);
}


public function pay(Request $request, $id)
{
    $guardian = Auth::user();

    $duePayment = DuePayment::where('id', $id)
        ->where('guardian_id', $guardian->id)
        ->where('status', 'unpaid')
        ->first();

    if (!$duePayment) {
        return response()->json([
            'message' => '❌ Payment not found or already paid.'
        ], 404);
    }

    // 🟠 تحقق من التأخير
    if ($duePayment->due_date && now()->gt($duePayment->due_date)) {
        $daysLate = now()->diffInDays($duePayment->due_date);
        $penalty = $daysLate * 5;
    } else {
        $penalty = 0;
    }

    $totalAmount = $duePayment->amount + $penalty;

    // ✅ تحقق من الرصيد
    if ($guardian->balance < $totalAmount) {
        return response()->json([
            'message' => '❌ Insufficient balance.',
            'current_balance' => $guardian->balance,
            'required_amount' => $totalAmount,
            'penalty' => $penalty
        ], 422);
    }

    // 💰 خصم الرصيد
    $guardian->balance -= $totalAmount;
    $guardian->save();

    // 💾 تحديث حالة الدفعة
    $duePayment->update([
        'status' => 'paid',
        'penalty' => $penalty,
    ]);

    // 🔄 تسجيل الدفعة في جدول payments
    Payment::create([
        'guardian_id'   => $guardian->id,
        'accountant_id' => null,
        'amount'        => $totalAmount,
        'payment_date'  => now(),
        'method'        => 'balance',
        'note'          => 'Auto payment for due_payment ID: ' . $duePayment->id,
    ]);

    return response()->json([
        'message' => '✅ Payment completed successfully (with penalty if late).',
        'paid_payment' => $duePayment,
        'penalty' => $penalty,
        'paid_amount' => $totalAmount,
        'remaining_balance' => $guardian->balance,
    ]);
}



public function unpaidDuePayments()
{
    $guardian = auth('guardian')->user();

    $children = $guardian->students()->with([
        'classRoom.grade:id,name',
        'duePayments' => function ($query) {
            $query->where('status', 'unpaid');
        }
    ])->get(['id', 'full_name', 'class_room_id']);

    $result = $children->map(function ($child) {
        return [
            'student_id' => $child->id,
            'student_name' => $child->full_name,
            'grade' => optional($child->classRoom?->grade)->name,
            'due_payments' => $child->duePayments->map(function ($payment) {
                $dueDate = \Carbon\Carbon::parse($payment->due_date);
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'penalty' => $payment->penalty,
                    'total_due' => $payment->amount + $payment->penalty,
                    'description' => $payment->description,
                    'due_date' => $dueDate->toDateString(),
                    'is_overdue' => $dueDate->isPast(),
                ];
            }),
        ];
    });

    return response()->json([
        'message' => '✅ Your children and their unpaid dues.',
        'children' => $result,
    ]);
}


 public function payAndConfirmAttendance(Request $request, $tripId)
{
    $guardian = Auth::guard('guardian')->user();

    $request->validate([
        'student_id' => 'required|exists:students,id',
    ]);

    // جلب الطالب والتأكد من أنه يتبع لهذا الأب
    $student = Student::where('id', $request->student_id)
        ->where('guardian_id', $guardian->id)
        ->first();

    if (!$student) {
        return response()->json(['message' => '❌ Unauthorized access to student.'], 403);
    }

    // جلب الرحلة والتأكد من وجودها
    $trip = SchoolTrip::find($tripId);
    if (!$trip) {
        return response()->json(['message' => '❌ Trip not found.'], 404);
    }

    // التحقق أن الطالب من نفس الصف الخاص بالرحلة
    if ($student->class_room_id !== $trip->class_room_id) {
        return response()->json(['message' => '❌ This trip is not for your child\'s class.'], 403);
    }

    // التحقق مما إذا تم تأكيد الحضور مسبقًا
    $pivot = $trip->students()->where('student_id', $student->id)->first();

    if ($pivot && $pivot->pivot->confirmation_status === 'confirmed') {
        return response()->json([
            'message' => 'ℹ️ Attendance has already been confirmed for this trip.',
            'confirmation_status' => $pivot->pivot->confirmation_status,
            'trip_details' => [
                'title' => $trip->title,
                'description' => $trip->description,
                'trip_date' => $trip->trip_date,
                'class_room_id' => $trip->class_room_id,
                'supervisor_id' => $trip->supervisor_id,
            ],
        ]);
    }

    // التحقق من الرصيد قبل الخصم
    if ($guardian->balance < 25) {
        return response()->json(['message' => '❌ Insufficient balance. Please recharge your account.'], 400);
    }

    // خصم الرصيد
    $guardian->balance -= 25;
    $guardian->save();

    // تأكيد الحضور
    if ($pivot) {
        $trip->students()->updateExistingPivot($student->id, ['confirmation_status' => 'confirmed']);
    } else {
        $trip->students()->attach($student->id, ['confirmation_status' => 'confirmed']);
    }

    // استرجاع الحالة بعد التحديث
    $status = $trip->students()->where('student_id', $student->id)->first()->pivot->confirmation_status;

    return response()->json([
        'message' => '✅ Payment successful and attendance confirmed.',
        'note' => '💰 25 units have been deducted as a trip fee.',
        'confirmation_status' => $status,
        'trip_details' => [
            'title' => $trip->title,
            'description' => $trip->description,
            'trip_date' => $trip->trip_date,
            'class_room_id' => $trip->class_room_id,
            'supervisor_id' => $trip->supervisor_id,
        ],
    ]);
}


public function guardianTrips()
{
    // جلب ولي الأمر الحالي من guard المخصص له
    $guardian = auth('guardian')->user();

    if (!$guardian) {
        return response()->json([
            'message' => '❌ Unauthorized access.',
        ], 401);
    }

    $students = $guardian->students;

    if ($students->isEmpty()) {
        return response()->json([
            'message' => 'ℹ️ No students linked to your account.',
        ], 200);
    }

    $data = [];

    foreach ($students as $student) {
        $trips = $student->schoolTrips()
            ->withPivot('confirmation_status')
            ->get()
            ->map(function ($trip) use ($student) {
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->Full_name,
                    'trip_id' => $trip->id,
                    'title' => $trip->title,
                    'trip_date' => $trip->trip_date,
                    'location' => $trip->location,
                    'supervisor_id' => $trip->supervisor_id,
                    'confirmation_status' => $trip->pivot->confirmation_status,
                ];
            });

        $data = array_merge($data, $trips->toArray());
    }

    return response()->json([
        'message' => '✅ Trips for your children.',
        'trips' => $data,
    ], 200);
}




}
