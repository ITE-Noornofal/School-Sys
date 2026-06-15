<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Supervisor;
use App\Models\Bus;

class AdminAuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6|confirmed'
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        // إنشاء الدور والصلاحية إن لم تكن موجودة
        $role = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'admin']
        );

        $permission = Permission::firstOrCreate(
            ['name' => 'add students', 'guard_name' => 'admin']
        );

        // إعطاء الدور الصلاحية (مرة واحدة فقط)
        if (!$role->hasPermissionTo('add students')) {
            $role->givePermissionTo($permission);
        }

        // إسناد الدور للمدير
        $admin->assignRole($role);

        return response()->json(['message' => 'Admin registered and role assigned successfully']);
    }


public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string'
    ]);

    $admin = Admin::where('email', $request->email)->first();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $admin->createToken('admin-token', ['*'])->plainTextToken;

    return response()->json(['token' => $token, 'admin' => $admin]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

//////////////////////////////////supervisor/////////////////////////////////////////////////
     public function addEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:supervisors,email',
            'name'  => 'required|string|max:255',
        ]);

        Supervisor::create([
            'email' => $request->email,
            'name'  => $request->name,
            'password' => null,
        ]);

        return response()->json(['message' => 'تم إضافة إيميل واسم المشرف للموافقة عليه']);
    }


public function deleteByEmail(Request $request)
{
    $request->validate([
        'email' => 'required|email'
    ]);

    $supervisor = Supervisor::where('email', $request->email)->first();

    if (! $supervisor) {
        return response()->json([
            'status' => false,
            'message' => 'Supervisor not found with this email'
        ], 200); // ملاحظة: نعيد 200 لأنك لا تريدها كـ "خطأ"
    }

    $supervisorData = [
        'id' => $supervisor->id,
        'name' => $supervisor->name,
        'email' => $supervisor->email,
        'created_at' => $supervisor->created_at,
    ];

    $supervisor->delete();

    return response()->json([
        'status' => true,
        'message' => 'Supervisor deleted successfully',
        'data' => $supervisorData
    ]);
}

public function updateSupervisor(Request $request, $id)
{
    $request->validate([
        'name' => 'nullable|string|max:255',
        'email' => 'nullable|email|unique:supervisors,email,' . $id,
        'password' => 'nullable|string|min:6|confirmed',
    ]);

    $supervisor = Supervisor::find($id);

    if (! $supervisor) {
        return response()->json([
            'status' => false,
            'message' => 'Supervisor not found',
        ], 404);
    }

    if ($request->has('name')) {
        $supervisor->name = $request->name;
    }

    if ($request->has('email')) {
        $supervisor->email = $request->email;
    }

    if ($request->filled('password')) {
        $supervisor->password = bcrypt($request->password);
    }

    $supervisor->save();

    return response()->json([
        'status' => true,
        'message' => 'Supervisor updated successfully',
        'data' => [
            'id' => $supervisor->id,
            'name' => $supervisor->name,
            'email' => $supervisor->email,
            'updated_at' => $supervisor->updated_at,
        ],
    ]);
}




public function AddBus(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'license_plate' => 'nullable|string|max:50|unique:buses,license_plate', // رقم لوحة السيارة اختياري وفريد
        // أضف أي حقول أخرى حسب جدول الباصات
    ]);

    $bus = Bus::create([
        'name' => $request->name,
        'license_plate' => $request->license_plate,
        // أضف باقي الحقول هنا إذا كانت موجودة
    ]);

    return response()->json([
        'message' => '✅ Bus created successfully.',
        'bus' => $bus,
    ], 201);
}


}
