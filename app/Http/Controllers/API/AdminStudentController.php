<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use App\Models\DuePaymentTemplate;
use App\Models\DuePayment;
use App\Models\ClassRoom;
class AdminStudentController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:admin', 'role:admin']);
    }

    // 📚 إضافة طالب

public function store(Request $request)
{
    $request->validate([
        'Full_name'         => 'required|string|max:255',
        'email'             => 'required|email|unique:students,email',
        'user_id'           => 'nullable|exists:users,id',
        'class_room_id'     => 'nullable|exists:class_rooms,id',
        'specialization_id' => 'nullable|exists:specializations,id',
        'guardian_id'       => 'nullable|exists:guardians,id',
        'age'               => 'nullable|integer|min:1',
        'birth_date'        => 'nullable|date',
        'address'           => 'nullable|string|max:255',
    ]);

    DB::beginTransaction();

    try {
        $student = Student::create([
            'Full_name'         => $request->Full_name,
            'email'             => $request->email,
            'user_id'           => $request->user_id,
            'class_room_id'     => $request->class_room_id,
            'specialization_id' => $request->specialization_id,
            'guardian_id'       => $request->guardian_id,
            'age'               => $request->age,
            'birth_date'        => $request->birth_date,
            'address'           => $request->address,
        ]);

        $gradeId = null;
        if ($request->class_room_id) {
            $classRoom = ClassRoom::find($request->class_room_id);
            if ($classRoom) {
                $gradeId = $classRoom->grade_id;
            }
        }

        if ($gradeId && $request->guardian_id) {
            $templates = DuePaymentTemplate::where('grade_id', $gradeId)->get();

            foreach ($templates as $template) {
                DuePayment::create([
                    'guardian_id'   => $request->guardian_id,
                    'student_id'    => $student->id,
                    'accountant_id' => auth()->id() ?? null,
                    'template_id'   => $template->id,
                    'amount'        => $template->amount,
                    'penalty'       => 0,
                    'description'   => $template->description,
                    'due_date'      => now()->addDays(15),
                    'status'        => 'unpaid',
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'message' => '✅ Student created successfully and due payments assigned.',
            'student' => $student
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'message' => '❌ Failed to create student and assign payments.',
            'error' => $e->getMessage()
        ], 500);
    }
}


//تحديث الطالب
public function update(Request $request, $id)
{
    $student = Student::findOrFail($id);

    $request->validate([
        'Full_name'         => 'sometimes|required|string|max:255',
        'email'             => 'sometimes|required|email|unique:students,email,' . $student->id,
        'user_id'           => 'nullable|exists:users,id',
        'class_room_id'     => 'nullable|exists:class_rooms,id',
        'specialization_id' => 'nullable|exists:specializations,id',
        'guardian_id'       => 'nullable|exists:guardians,id',  // تحقق من وجود ولي الأمر
        'age'               => 'nullable|integer|min:1',
        'birth_date'        => 'nullable|date',
        'address'           => 'nullable|string|max:255',
    ]);

    $student->update($request->only([
        'Full_name',
        'email',
        'user_id',
        'class_room_id',
        'specialization_id',
        'guardian_id',   // أضف هذا
        'age',
        'birth_date',
        'address',
    ]));

    return response()->json([
        'message' => '✅ Student updated successfully',
        'student' => $student
    ], 200);
}



    // 🧑‍🏫 إضافة معلم (يضيف فقط الإيميل، والباقي يُكمل لاحقًا عند تسجيل أول مرة)
    public function addTeacher(Request $request)
    {


        $request->validate([
            'email' => 'required|email|unique:teachers,email',
        ]);

        $teacher = Teacher::create([
            'email' => $request->email,
        ]);

        return response()->json(['teacher' => $teacher], 201);
    }


}
