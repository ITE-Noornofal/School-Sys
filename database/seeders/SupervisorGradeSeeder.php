<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supervisor;
use App\Models\GradeLevel;  // ← تغيير من Grade إلى GradeLevel
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

        // ربط المشرفين بالـ grade_levels بالتسلسل
        $supervisors = Supervisor::orderBy('id')->take(12)->get();
        $gradeLevels = GradeLevel::orderBy('id')->take(12)->get();  // ← GradeLevel

        foreach ($gradeLevels as $index => $gradeLevel) {  // ← $gradeLevel
            if (isset($supervisors[$index])) {
                $gradeLevel->supervisor_id = $supervisors[$index]->id;  // ← $gradeLevel
                $gradeLevel->save();  // ← $gradeLevel
            }
        }

        // تحديث الـ ClassRoom لربط كل صف بالمشرف المناسب
        $classRooms = ClassRoom::with('gradeLevel')->get();  // ← gradeLevel

        foreach ($classRooms as $classRoom) {
            $gradeLevelSupervisorId = $classRoom->gradeLevel->supervisor_id ?? null;  // ← gradeLevel

            if ($gradeLevelSupervisorId) {
                $classRoom->supervisor_id = $gradeLevelSupervisorId;
                $classRoom->save();
            }
        }
    }
}
