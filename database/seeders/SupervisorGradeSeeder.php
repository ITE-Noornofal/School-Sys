<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supervisor;
use App\Models\Grade;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SupervisorGradeSeeder extends Seeder
{
    public function run()
    {
        // جلب أو إنشاء دور المشرف (supervisor)
        $supervisorRole = Role::firstOrCreate([
            'name' => 'supervisor',
            'guard_name' => 'supervisor',
        ]);

        // إنشاء 12 مشرف وإعطاؤهم دور المشرف
        for ($i = 1; $i <= 12; $i++) {
            $supervisor = Supervisor::updateOrCreate(
                ['email' => "supervisor{$i}@school.com"],
                [
                    'name' => "Supervisor {$i}",
                    'email' => "supervisor{$i}@school.com",
                    'password' => Hash::make('password'),
                ]
            );

            // إعطاء الدور (الذي يحتوي جميع صلاحيات المشرف)
            $supervisor->assignRole($supervisorRole);
        }

        // ربط المشرفين بالـ grades بالتسلسل
        $supervisors = Supervisor::orderBy('id')->take(12)->get();
        $grades = Grade::orderBy('id')->take(12)->get();

        foreach ($grades as $index => $grade) {
            if (isset($supervisors[$index])) {
                $grade->supervisor_id = $supervisors[$index]->id;
                $grade->save();
            }
        }

        // تحديث الـ ClassRoom لربط كل صف بالمشرف المناسب
        $classRooms = ClassRoom::with('grade')->get();

        foreach ($classRooms as $classRoom) {
            $gradeSupervisorId = $classRoom->grade->supervisor_id ?? null;

            if ($gradeSupervisorId) {
                $classRoom->supervisor_id = $gradeSupervisorId;
                $classRoom->save();
            }
        }
    }
}
