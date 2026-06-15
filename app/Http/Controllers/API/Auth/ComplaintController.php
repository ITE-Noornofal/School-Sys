<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Complaint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
class ComplaintController extends Controller
{




public function storeByGuardian(Request $request)
{
    $guardian = auth()->user();
    $students = $guardian->students;

    // تحقق الطالب المرتبط إذا أرسل student_id
    $studentId = $request->student_id;
    if ($studentId && !$students->contains('id', $studentId)) {
        return response()->json(['message' => '❌ The selected student is not linked to your account.'], 403);
    }

    $isAnonymous = $request->input('is_anonymous', false);

    $guardianIdHash = hash('sha256', $guardian->id);

    if ($isAnonymous) {
        $anonymousCount = Complaint::where('guardian_id_hash', $guardianIdHash)
            ->where('is_anonymous', true)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($anonymousCount >= 3) {
            return response()->json(['message' => '❌ You have exceeded the allowed number of anonymous complaints this month.'], 403);
        }
    }

    $complaintData = [
        'class_room_id' => $request->class_room_id,
        'content' => $request->content,
        'is_anonymous' => $isAnonymous,
        'status' => 'pending',
        'student_id' => $isAnonymous ? null : $studentId,
    ];

    if ($isAnonymous) {
        $complaintData['guardian_id'] = null;
        $complaintData['guardian_id_enc'] = Crypt::encryptString($guardian->id);
        $complaintData['guardian_id_hash'] = $guardianIdHash;
    } else {
        $complaintData['guardian_id'] = $guardian->id;
        $complaintData['guardian_id_enc'] = null;
        $complaintData['guardian_id_hash'] = null;
    }

    $complaint = Complaint::create($complaintData);

    return response()->json(['message' => '✅ Complaint submitted successfully.', 'complaint' => $complaint]);
}



public function index()
{
    $guardian = auth()->user();
    $guardianIdHash = hash('sha256', $guardian->id);

    $complaints = Complaint::with(['classRoom:id,name', 'student:id,full_name'])
        ->where(function($query) use ($guardian, $guardianIdHash) {
            $query->where('guardian_id', $guardian->id)
                  ->orWhere('guardian_id_hash', $guardianIdHash);
        })
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($complaint) {
            return [
                'id'           => $complaint->id,
                'content'      => $complaint->content,
                'status'       => $complaint->status,
                'is_anonymous' => $complaint->is_anonymous,
                'created_at'   => $complaint->created_at->toDateTimeString(),
                'class_room'   => $complaint->classRoom?->name,
                'student'      => $complaint->is_anonymous ? null : ($complaint->student?->full_name ?? null),
                'guardian'     => $complaint->is_anonymous ? null : [
                    'id'    => $complaint->guardian?->id,
                    'name'  => $complaint->guardian?->Full_name ?? $complaint->guardian?->name,
                    'email' => $complaint->guardian?->email,
                ],
            ];
        });

    return response()->json([
        'message' => '✅ Complaints fetched successfully.',
        'complaints' => $complaints
    ]);
}


}
