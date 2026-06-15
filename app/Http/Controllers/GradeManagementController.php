<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
namespace App\Http\Controllers;

use App\Models\Grade;
use Illuminate\Http\Request;


class GradeManagementController extends Controller
{



   public function __construct()
    {
        $this->middleware(['auth:admin', 'role:admin']);
    }

 // عرض جميع الصفوف
    public function index()
    {
        $grades = Grade::all();
        return response()->json($grades);
    }

    // عرض صف محدد
    public function show($id)
    {
        $grade = Grade::findOrFail($id);
        return response()->json($grade);
    }

    // إضافة صف جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $grade = Grade::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'تم إنشاء الصف بنجاح',
            'grade' => $grade,
        ], 201);
    }

    // تعديل صف
    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $grade->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'تم تحديث الصف بنجاح',
            'grade' => $grade,
        ]);
    }

    // حذف صف
    public function destroy($id)
    {
        $grade = Grade::findOrFail($id);
        $grade->delete();

        return response()->json(['message' => 'تم حذف الصف بنجاح']);
    }
}

