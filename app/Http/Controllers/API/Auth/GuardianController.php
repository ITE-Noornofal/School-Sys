<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Student;
use App\Models\Payment;
use App\Models\SchoolTrip;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\DuePayment;

class GuardianController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | الحصول على Guardian المرتبط بالمستخدم الحالي
    |--------------------------------------------------------------------------
    |
    | الـ token أصبح تابعاً لجدول users.
    | لذلك نبحث عن Guardian عن طريق email.
    |
    */

    private function getGuardianFromUser(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Guardian::where('email', $user->email)->first();
    }


    // =====================================================================
    // PROFILE
    // =====================================================================

    public function profile(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Guardian not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'guardian' => $guardian,
        ]);
    }


    // =====================================================================
    // UPDATE PROFILE
    // =====================================================================

    public function updateProfile(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Guardian not found.'
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:6|confirmed',
        ]);

        // تحديث Guardian
        if ($request->has('name')) {
            $guardian->name = $request->name;
        }

        if ($request->has('phone')) {
            $guardian->phone = $request->phone;
        }

        if ($request->filled('password')) {
            $guardian->password = Hash::make($request->password);
        }

        $guardian->save();


        // =================================================================
        // تحديث User
        // =================================================================

        $user = User::where('email', $guardian->email)->first();

        if ($user) {

            // جدول users عندك يستخدم Full_name
            $user->Full_name = $guardian->name;

            // جدول users عندك يستخدم phone_number
            $user->phone_number = $guardian->phone;

            if ($request->filled('password')) {
                $user->password = $guardian->password;
            }

            $user->role = 'guardian';

            $user->save();
        }


        return response()->json([
            'message' => 'Profile updated successfully.',
            'guardian' => $guardian,
        ]);
    }


    // =====================================================================
    // CHILDREN
    // =====================================================================

    public function children(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Guardian not found.'
            ], 404);
        }

        $children = $guardian->students()->get();

        return response()->json($children);
    }


    // =====================================================================
    // REGISTER
    // =====================================================================

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:guardians,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);


        // ولي الأمر الذي أضافه المدير
        $guardian = Guardian::where(
            'email',
            $request->email
        )->first();


        if (!$guardian) {
            return response()->json([
                'message' => 'Guardian not found.'
            ], 404);
        }


        // منع التسجيل مرة ثانية
        if ($guardian->password) {
            return response()->json([
                'message' => '❌ This guardian is already registered.'
            ], 409);
        }


        // =================================================================
        // تحديث Guardian
        // =================================================================

        $guardian->update([
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);


        // =================================================================
        // إنشاء User للحساب الموحد
        // =================================================================

        /*
        |--------------------------------------------------------------------------
        | مهم:
        | users migration عندك يحتوي:
        |
        | Full_name
        | phone_number
        | address
        | email
        | password
        |
        */

        $user = User::updateOrCreate(
            [
                'email' => $guardian->email,
            ],
            [
                'Full_name' => $guardian->name,
                'phone_number' => $guardian->phone,
                'password' => $guardian->password,
                'role' => 'guardian',
            ]
        );


        // =================================================================
        // Spatie Role
        // =================================================================

        if (method_exists($user, 'assignRole')) {

            if (!$user->hasRole('guardian')) {
                $user->assignRole('guardian');
            }
        }


        // =================================================================
        // Token من User
        // =================================================================

        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;


        return response()->json([
            'status' => 'success',

            'message' => '✅ Guardian registered successfully.',

            'token' => $token,

            'role' => 'guardian',

            'user' => [
                'id' => $user->id,
                'name' => $user->Full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],

            'guardian' => $guardian,
        ], 201);
    }


    // =====================================================================
    // LOGIN
    // =====================================================================

    /*
    |--------------------------------------------------------------------------
    | هذا Login القديم يبقى موجوداً للتوافق.
    |
    | لكن Login الرئيسي الجديد سيكون:
    |
    | POST /api/login
    |
    | من UnifiedAuthController
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);


        // البحث في users
        $user = User::where(
            'email',
            $request->email
        )
            ->where(
                'role',
                'guardian'
            )
            ->first();


        if (
            !$user ||
            !$user->password ||
            !Hash::check(
                $request->password,
                $user->password
            )
        ) {

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }


        // جلب Guardian
        $guardian = Guardian::where(
            'email',
            $user->email
        )->first();


        if (!$guardian) {

            return response()->json([
                'message' => 'Guardian record not found.'
            ], 404);
        }


        // تأكيد Role
        if (method_exists($user, 'hasRole')) {

            if (!$user->hasRole('guardian')) {
                $user->assignRole('guardian');
            }
        }


        // Token من User
        $token = $user
            ->createToken('auth_token')
            ->plainTextToken;


        return response()->json([
            'status' => 'success',

            'message' => 'Login successful.',

            'token' => $token,

            'role' => 'guardian',

            'user' => [
                'id' => $user->id,
                'name' => $user->Full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],

            'guardian' => $guardian,
        ]);
    }


    // =====================================================================
    // DASHBOARD
    // =====================================================================

    public function dashboard(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }


        // جلب الأبناء مع الصف والمشرف
        $children = $guardian->students()
            ->with([
                'classRoom:id,name,supervisor_id',
                'classRoom.supervisor:id,name,email',
                'schoolTrips' => function ($query) {
                    $query->wherePivot(
                        'confirmation_status',
                        'confirmed'
                    );
                }
            ])
            ->get([
                'id',
                'full_name',
                'class_room_id'
            ]);


        // مجموع المدفوعات
        $totalPaid = $guardian
            ->payments()
            ->where('status', 'paid')
            ->sum('amount');


        // مجموع المستحقات
        $totalDue = $guardian
            ->duePayments()
            ->where('status', 'unpaid')
            ->sum('amount');


        // عدد المدفوعات
        $paymentsCount = $guardian
            ->payments()
            ->where('status', 'paid')
            ->count();


        // عدد المستحقات
        $duePaymentsCount = $guardian
            ->duePayments()
            ->where('status', 'unpaid')
            ->count();


        // بيانات الأبناء
        $childrenData = $children->map(function ($student) {

            $tripCount = $student->schoolTrips->count();

            $tripFees = $tripCount * 25;

            return [
                'id' => $student->id,

                'full_name' => $student->full_name,

                'class_room' => $student->classRoom?->name,

                'supervisor' => $student->classRoom?->supervisor
                    ? [
                        'name' =>
                            $student->classRoom->supervisor->name,

                        'email' =>
                            $student->classRoom->supervisor->email,
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

            'message' =>
                '✅ Guardian dashboard loaded with updated payment and trip info.'
        ]);
    }


    // =====================================================================
    // PAY
    // =====================================================================

    public function pay(Request $request, $id)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }


        $duePayment = DuePayment::where(
            'id',
            $id
        )
            ->where(
                'guardian_id',
                $guardian->id
            )
            ->where(
                'status',
                'unpaid'
            )
            ->first();


        if (!$duePayment) {

            return response()->json([
                'message' =>
                    '❌ Payment not found or already paid.'
            ], 404);
        }


        // التأخير
        if (
            $duePayment->due_date &&
            now()->gt($duePayment->due_date)
        ) {

            $daysLate =
                now()->diffInDays(
                    $duePayment->due_date
                );

            $penalty = $daysLate * 5;

        } else {

            $penalty = 0;
        }


        $totalAmount =
            $duePayment->amount + $penalty;


        // الرصيد
        if ($guardian->balance < $totalAmount) {

            return response()->json([

                'message' =>
                    '❌ Insufficient balance.',

                'current_balance' =>
                    $guardian->balance,

                'required_amount' =>
                    $totalAmount,

                'penalty' =>
                    $penalty

            ], 422);
        }


        // خصم الرصيد
        $guardian->balance -= $totalAmount;

        $guardian->save();


        // تحديث الدفعة
        $duePayment->update([
            'status' => 'paid',
            'penalty' => $penalty,
        ]);


        // تسجيل الدفعة
        Payment::create([

            'guardian_id' =>
                $guardian->id,

            'accountant_id' =>
                null,

            'amount' =>
                $totalAmount,

            'payment_date' =>
                now(),

            'method' =>
                'balance',

            'note' =>
                'Auto payment for due_payment ID: '
                . $duePayment->id,

        ]);


        return response()->json([

            'message' =>
                '✅ Payment completed successfully (with penalty if late).',

            'paid_payment' =>
                $duePayment,

            'penalty' =>
                $penalty,

            'paid_amount' =>
                $totalAmount,

            'remaining_balance' =>
                $guardian->balance,

        ]);
    }


    // =====================================================================
    // UNPAID DUE PAYMENTS
    // =====================================================================

    public function unpaidDuePayments(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }


        $children = $guardian
            ->students()
            ->with([

                'classRoom.grade:id,name',

                'duePayments' => function ($query) {

                    $query->where(
                        'status',
                        'unpaid'
                    );

                }

            ])
            ->get([
                'id',
                'full_name',
                'class_room_id'
            ]);


        $result = $children->map(function ($child) {

            return [

                'student_id' =>
                    $child->id,

                'student_name' =>
                    $child->full_name,

                'grade' =>
                    optional(
                        $child->classRoom?->grade
                    )->name,

                'due_payments' =>
                    $child->duePayments->map(
                        function ($payment) {

                            $dueDate =
                                Carbon::parse(
                                    $payment->due_date
                                );

                            return [

                                'id' =>
                                    $payment->id,

                                'amount' =>
                                    $payment->amount,

                                'penalty' =>
                                    $payment->penalty,

                                'total_due' =>
                                    $payment->amount
                                    + $payment->penalty,

                                'description' =>
                                    $payment->description,

                                'due_date' =>
                                    $dueDate->toDateString(),

                                'is_overdue' =>
                                    $dueDate->isPast(),

                            ];
                        }
                    ),
            ];
        });


        return response()->json([

            'message' =>
                '✅ Your children and their unpaid dues.',

            'children' =>
                $result,

        ]);
    }


    // =====================================================================
    // PAY AND CONFIRM ATTENDANCE
    // =====================================================================

    public function payAndConfirmAttendance(
        Request $request,
        $tripId
    ) {

        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {
            return response()->json([
                'message' => 'Unauthorized.'
            ], 401);
        }


        $request->validate([
            'student_id' =>
                'required|exists:students,id',
        ]);


        // الطالب التابع لولي الأمر
        $student = Student::where(
            'id',
            $request->student_id
        )
            ->where(
                'guardian_id',
                $guardian->id
            )
            ->first();


        if (!$student) {

            return response()->json([
                'message' =>
                    '❌ Unauthorized access to student.'
            ], 403);
        }


        // الرحلة
        $trip = SchoolTrip::find($tripId);


        if (!$trip) {

            return response()->json([
                'message' =>
                    '❌ Trip not found.'
            ], 404);
        }


        // نفس الصف
        if (
            $student->class_room_id
            !== $trip->class_room_id
        ) {

            return response()->json([
                'message' =>
                    '❌ This trip is not for your child\'s class.'
            ], 403);
        }


        // التأكد من الحضور
        $pivot = $trip
            ->students()
            ->where(
                'student_id',
                $student->id
            )
            ->first();


        if (
            $pivot &&
            $pivot->pivot->confirmation_status === 'confirmed'
        ) {

            return response()->json([

                'message' =>
                    'ℹ️ Attendance has already been confirmed for this trip.',

                'confirmation_status' =>
                    $pivot->pivot->confirmation_status,

                'trip_details' => [

                    'title' =>
                        $trip->title,

                    'description' =>
                        $trip->description,

                    'trip_date' =>
                        $trip->trip_date,

                    'class_room_id' =>
                        $trip->class_room_id,

                    'supervisor_id' =>
                        $trip->supervisor_id,

                ],

            ]);
        }


        // الرصيد
        if ($guardian->balance < 25) {

            return response()->json([
                'message' =>
                    '❌ Insufficient balance. Please recharge your account.'
            ], 400);
        }


        // خصم الرصيد
        $guardian->balance -= 25;

        $guardian->save();


        // تأكيد الحضور
        if ($pivot) {

            $trip->students()->updateExistingPivot(

                $student->id,

                [
                    'confirmation_status' =>
                        'confirmed'
                ]

            );

        } else {

            $trip->students()->attach(

                $student->id,

                [
                    'confirmation_status' =>
                        'confirmed'
                ]

            );
        }


        // الحالة بعد التحديث
        $status = $trip
            ->students()
            ->where(
                'student_id',
                $student->id
            )
            ->first()
            ->pivot
            ->confirmation_status;


        return response()->json([

            'message' =>
                '✅ Payment successful and attendance confirmed.',

            'note' =>
                '💰 25 units have been deducted as a trip fee.',

            'confirmation_status' =>
                $status,

            'trip_details' => [

                'title' =>
                    $trip->title,

                'description' =>
                    $trip->description,

                'trip_date' =>
                    $trip->trip_date,

                'class_room_id' =>
                    $trip->class_room_id,

                'supervisor_id' =>
                    $trip->supervisor_id,

            ],

        ]);
    }


    // =====================================================================
    // GUARDIAN TRIPS
    // =====================================================================

    public function guardianTrips(Request $request)
    {
        $guardian = $this->getGuardianFromUser($request);

        if (!$guardian) {

            return response()->json([
                'message' =>
                    '❌ Unauthorized access.',
            ], 401);
        }


        $students = $guardian->students;


        if ($students->isEmpty()) {

            return response()->json([

                'message' =>
                    'ℹ️ No students linked to your account.',

            ], 200);
        }


        $data = [];


        foreach ($students as $student) {

            $trips = $student
                ->schoolTrips()
                ->withPivot(
                    'confirmation_status'
                )
                ->get()
                ->map(
                    function ($trip) use ($student) {

                        return [

                            'student_id' =>
                                $student->id,

                            'student_name' =>
                                $student->Full_name,

                            'trip_id' =>
                                $trip->id,

                            'title' =>
                                $trip->title,

                            'trip_date' =>
                                $trip->trip_date,

                            'location' =>
                                $trip->location,

                            'supervisor_id' =>
                                $trip->supervisor_id,

                            'confirmation_status' =>
                                $trip->pivot->confirmation_status,

                        ];
                    }
                );


            $data = array_merge(
                $data,
                $trips->toArray()
            );
        }


        return response()->json([

            'message' =>
                '✅ Trips for your children.',

            'trips' =>
                $data,

        ], 200);
    }
}
