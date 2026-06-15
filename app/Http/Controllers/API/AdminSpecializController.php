<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialization;
class AdminSpecializController extends Controller
{
    // عرض كل الاختصاصات
    public function index()
    {
        $specializations = Specialization::all();
        return response()->json($specializations);
    }

    // إضافة اختصاص جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:specializations,name',
        ]);

        $specialization = Specialization::create([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => '✅ Specialization created successfully',
            'specialization' => $specialization,
        ], 201);
    }

    // تعديل اختصاص موجود
    public function update(Request $request, $id)
    {
        $specialization = Specialization::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:specializations,name,' . $id,
        ]);

        $specialization->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => '✅ Specialization updated successfully',
            'specialization' => $specialization,
        ]);
    }

    // حذف اختصاص
  public function destroy($id)
{
    $specialization = Specialization::find($id);

    if (!$specialization) {
        return response()->json([
            'message' => ' Specialization not found',
        ], 404);
    }

    $specialization->delete();

    return response()->json([
        'message' => '✅ Specialization deleted successfully',
    ]);
}

}
