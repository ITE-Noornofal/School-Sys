<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use Illuminate\Http\Request;

class ClassRoomController extends Controller//يضيف الشعب ويعدل  عليها
{
    public function index()
    {
        return ClassRoom::with('students')->get();
    }

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'section' => 'nullable|string',
        'grade_id' => 'required|exists:grades,id',
    ]);

    // تحقق من وجود صف بنفس الاسم والقسم ونفس الـ grade_id
    $exists = ClassRoom::where('name', $validated['name'])
        ->where('section', $validated['section'])
        ->where('grade_id', $validated['grade_id'])
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'A class with the same name, section and grade already exists.'
        ], 409);
    }

    $classRoom = ClassRoom::create($validated);

    return response()->json([
        'message' => 'Class created successfully.',
        'data' => $classRoom
    ], 201);
}
public function update(Request $request, $id)
{
    // التحقق من صحة البيانات مع السماح بتعديل grade_id
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'section' => 'nullable|string|max:255',
        'grade_id' => 'required|exists:grades,id',
    ]);

    // البحث عن الصف (class room) حسب الـ id
    $classRoom = ClassRoom::find($id);

    if (!$classRoom) {
        return response()->json([
            'message' => 'The class room you are trying to update does not exist.'
        ], 404);
    }

    // تحقق من وجود صف آخر بنفس الاسم والقسم والـ grade_id (غير هذا الصف)
    $exists = ClassRoom::where('name', $validated['name'])
        ->where('section', $validated['section'])
        ->where('grade_id', $validated['grade_id'])
        ->where('id', '!=', $id)
        ->exists();

    if ($exists) {
        return response()->json([
            'message' => 'Another class with the same name, section and grade already exists.'
        ], 409);
    }

    // تحديث الصف
    $classRoom->update($validated);

    return response()->json([
        'message' => 'Class updated successfully.',
        'data' => $classRoom
    ], 200);
}


  public function destroy($id)
{
    $classRoom = ClassRoom::find($id);

    if (!$classRoom) {
        return response()->json([
            'message' => 'The requested class does not exist'
        ], 404);
    }

    $classRoom->delete();

    return response()->json([
        'message' => 'Class deleted successfully'
    ], 200);
}

}
