<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use App\Models\Teacher;
use App\Models\Admin;
use App\Models\Supervisor;
use App\Models\Accountant;
use App\Models\Guardian;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        /*
        |--------------------------------------------------------------------------
        | 🧑‍🏫 صلاحيات المعلم
        |--------------------------------------------------------------------------
        */

        Permission::firstOrCreate([
            'name' => 'manage grades',
            'guard_name' => 'api',
        ]);

        $teacherRole = Role::firstOrCreate([
            'name' => 'teacher',
            'guard_name' => 'api',
        ]);

        $teacherRole->syncPermissions([
            'manage grades',
        ]);

        /*
        | ربط المعلمين بالدور
        */

        $teachers = Teacher::all();

        foreach ($teachers as $teacher) {
            $teacher->assignRole($teacherRole);
        }


        /*
        |--------------------------------------------------------------------------
        | 🧑‍💼 صلاحيات المدير
        |--------------------------------------------------------------------------
        | لا تغيير هنا
        */

        $adminPermissions = [
            'add students',
            'addteacher',
            'editclasses',
            'transfer students',
            'manage specializations',
            'management grades',
        ];

        foreach ($adminPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin',
            ]);
        }

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'admin',
        ]);

        $adminRole->syncPermissions($adminPermissions);

        $admins = Admin::all();

        if ($admins->isEmpty()) {

            echo "⚠️ No admin users found to assign role.\n";

        } else {

            foreach ($admins as $admin) {
                $admin->assignRole($adminRole);
            }

            echo "✅ Admin roles assigned.\n";
        }


        /*
        |--------------------------------------------------------------------------
        | 🎓 صلاحيات الطالب
        |--------------------------------------------------------------------------
        | API Guard
        */

        $studentPermissions = [
            'view lessons',
            'submit assignments',
        ];

        foreach ($studentPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $studentRole = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'api',
        ]);

        $studentRole->syncPermissions($studentPermissions);


        /*
        |--------------------------------------------------------------------------
        | 🧑‍💼 صلاحيات المشرف
        |--------------------------------------------------------------------------
        | لا تغيير هنا
        */

        $supervisorPermissions = [
            'view assigned grades',
            'view assigned classes',
            'take attendance',
            'view attendance',
            'edit attendance',
            'manage classes',
        ];

        foreach ($supervisorPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'supervisor',
            ]);
        }

        $supervisorRole = Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'supervisor',
        ]);

        $supervisorRole->syncPermissions($supervisorPermissions);

        $supervisors = Supervisor::all();

        if ($supervisors->isEmpty()) {

            echo "⚠️ No supervisors found to assign role.\n";

        } else {

            foreach ($supervisors as $supervisor) {
                $supervisor->assignRole($supervisorRole);
            }

            echo "✅ Supervisors assigned with roles and permissions.\n";
        }


        /*
        |--------------------------------------------------------------------------
        | 💰 صلاحيات المحاسب
        |--------------------------------------------------------------------------
        | لا تغيير هنا
        */

        $accountantPermissions = [
            'view payments',
            'create payments',
            'edit payments',
            'delete payments',
            'view student balances',
        ];

        foreach ($accountantPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'accountant',
            ]);
        }

        $accountantRole = Role::firstOrCreate([
            'name' => 'accountant',
            'guard_name' => 'accountant',
        ]);

        $accountantRole->syncPermissions($accountantPermissions);

        $accountants = Accountant::all();

        if ($accountants->isEmpty()) {

            echo "⚠️ No accountants found to assign role.\n";

        } else {

            foreach ($accountants as $accountant) {
                $accountant->assignRole($accountantRole);
            }

            echo "✅ Accountants assigned with roles and permissions.\n";
        }


        /*
        |--------------------------------------------------------------------------
        | 👨‍👩‍👧 صلاحيات ولي الأمر
        |--------------------------------------------------------------------------
        | API Guard
        */

        $guardianPermissions = [
            'view own children grades',
            'view own children attendance',
            'make payments',
            'view payment history',
        ];

        foreach ($guardianPermissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        $guardianRole = Role::firstOrCreate([
            'name' => 'guardian',
            'guard_name' => 'api',
        ]);

        $guardianRole->syncPermissions($guardianPermissions);

        $guardians = Guardian::all();

        if ($guardians->isEmpty()) {

            echo "⚠️ No guardians found to assign role.\n";

        } else {

            foreach ($guardians as $guardian) {
                $guardian->assignRole($guardianRole);
            }

            echo "✅ Guardians assigned with roles and permissions.\n";
        }
    }
}
