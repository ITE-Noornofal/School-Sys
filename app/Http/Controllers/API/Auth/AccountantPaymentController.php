<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Guardian;
use App\Models\DuePayment;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Auth;
use App\Models\DuePaymentTemplate;


class AccountantPaymentController extends Controller
{
    // 🧾 عرض الدفعات المسجلة (اختياري)
    public function index()
    {
        $accountant = auth()->user();

        $payments = Payment::with('guardian')
            ->where('accountant_id', $accountant->id)
            ->get();

        return response()->json($payments);
    }

    // ➕ تسجيل دفعة جديدة

public function store(Request $request)
{
    $request->validate([
        'guardian_id' => 'required|exists:guardians,id',
        'template_id' => 'required|exists:due_payment_templates,id',
        'due_date'    => 'nullable|date',
    ]);

    $guardian = Guardian::withCount('students')->find($request->guardian_id);
    if ($guardian->students_count == 0) {
        return response()->json([
            'message' => '❌ Cannot create due payment. This guardian has no children assigned.'
        ], 422);
    }

    $template = DuePaymentTemplate::find($request->template_id);
    if (!$template) {
        return response()->json([
            'message' => '❌ Payment template not found.'
        ], 404);
    }

    $penalty = 0;
    if ($request->due_date && now()->gt(Carbon::parse($request->due_date))) {
        $daysLate = now()->diffInDays(Carbon::parse($request->due_date));
        $penalty = $daysLate * $template->penalty_per_day;
    }

    $duePayment = DuePayment::create([
        'guardian_id'   => $request->guardian_id,
        'accountant_id' => auth()->id(),
        'template_id'   => $template->id,
        'amount'        => $template->amount,
        'penalty'       => $penalty,
        'description'   => $template->description,
        'due_date'      => $request->due_date,
        'status'        => 'unpaid',
    ]);

    return response()->json([
        'message' => '✅ Due payment created successfully.',
        'due_payment' => $duePayment
    ], 201);
}


public function monthlySummary()
{
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    // 🟢 إجمالي المدفوعات الفعلية خلال الشهر (من جدول payments)
    $payments = DB::table('payments')
        ->join('guardians', 'payments.guardian_id', '=', 'guardians.id')
        ->whereMonth('payments.payment_date', $currentMonth)
        ->whereYear('payments.payment_date', $currentYear)
        ->select(
            'payments.id',
            'guardians.name as guardian_name',
            'payments.amount',
            'payments.payment_date',
            'payments.method'
        )
        ->orderBy('payments.payment_date')
        ->get();

    $totalCollected = $payments->sum('amount');
    $paymentsCount = $payments->count();

    // 🔵 إجمالي المبالغ التي حددها المحاسب كأقساط خلال هذا الشهر (من جدول due_payments)
    $dueTotal = DB::table('due_payments')
        ->whereMonth('created_at', $currentMonth)
        ->whereYear('created_at', $currentYear)
        ->sum('amount');

    return response()->json([
        'month' => Carbon::now()->format('F Y'),
        'payments_count' => $paymentsCount,
        'total_collected' => $totalCollected,
        'total_due_assigned' => $dueTotal,
        'details' => $payments,
    ]);
}




public function guardianPaymentSummary()
{
    $summary = Guardian::select(
        'guardians.id',
        'guardians.name',
        DB::raw('COUNT(payments.id) as payments_count'),
        DB::raw('SUM(payments.amount) as total_paid')
    )
    ->leftJoin('payments', 'guardians.id', '=', 'payments.guardian_id')
    ->groupBy('guardians.id', 'guardians.name')
    ->get();

    return response()->json($summary);
}





public function updatePenalties()
{
    $overduePayments = DuePayment::where('status', 'unpaid')
        ->whereDate('due_date', '<', Carbon::today())
        ->get();

    foreach ($overduePayments as $payment) {
        $daysLate = Carbon::parse($payment->due_date)->diffInDays(Carbon::today());
        $penalty = $daysLate * 5; // غرامة يومية 5 مثلاً
        $payment->update(['penalty' => $penalty]);
    }

    return response()->json([
        'message' => '✅ تم تحديث الغرامات تلقائيًا.',
        'affected' => $overduePayments->count(),
    ]);
}



// ✅ جلب جميع أنواع الدفعات المرتبطة بصف معين عبر class_room
public function byClassRoom($id)
{
    // تحميل الصف الدراسي مع الصف والدفعات التابعة للصف
    $classRoom = ClassRoom::with('grade.duePaymentTemplates')->find($id);

    if (!$classRoom) {
        return response()->json([
            'message' => '❌ Class room not found.'
        ], 404);
    }

    $grade = $classRoom->grade;

    if (!$grade) {
        return response()->json([
            'message' => '❌ Grade not found for this class room.'
        ], 404);
    }

    return response()->json([
        'class_room' => $classRoom->name,
        'grade' => $grade->name,
        'due_payment_templates' => $grade->duePaymentTemplates,
    ]);
}


}
