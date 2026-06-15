<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        //  مع الدفعات أولًا: إدخال الصفوف
        $this->call([
           GradeWithPaymentsSeeder::class,
        ]);


        // ثالثًا: تشغيل صلاحيات وأدوار
        $this->call([
            RolesAndPermissionsSeeder::class,
        ]);

         // ثالثًا: تشغيل صلاحيات وأدوار
        $this->call([
            SupervisorGradeSeeder::class,
        ]);
    }
}
