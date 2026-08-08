<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GradeLevel;
use App\Models\Guardian;
use App\Models\Accountant;
use App\Models\Payment;

class GradeWithPaymentsSeeder extends Seeder
{
    public function run()
    {
        // ─── إنشاء Grade Levels (الصفوف الدراسية) ───
        $gradeLevels = [
            ['id' => 1, 'name' => 'الصف الأول'],
            ['id' => 2, 'name' => 'الصف الثاني'],
            ['id' => 3, 'name' => 'الصف الثالث'],
            ['id' => 4, 'name' => 'الصف الرابع'],
            ['id' => 5, 'name' => 'الصف الخامس'],
            ['id' => 6, 'name' => 'الصف السادس'],
        ];

        foreach ($gradeLevels as $grade) {
            GradeLevel::firstOrCreate(
                ['id' => $grade['id']],
                ['name' => $grade['name']]
            );
        }

        // ─── إنشاء Guardians (إذا لم يكونوا موجودين) ───
        $guardians = Guardian::all();
        if ($guardians->isEmpty()) {
            echo "⚠️ No guardians found. Creating sample guardians...\n";
            for ($i = 1; $i <= 3; $i++) {
                Guardian::create([
                    'name' => 'ولي أمر ' . $i,
                    'email' => 'guardian' . $i . '@school.com',
                    'password' => bcrypt('password123'),
                    'phone' => '012345678' . $i,
                ]);
            }
            $guardians = Guardian::all();
        }

        // ─── إنشاء Payments ───
        foreach ($guardians as $guardian) {
            Payment::create([
                'guardian_id'   => $guardian->id,
                'accountant_id' => null, // أو Accountant::first()->id إذا موجود
                'amount'        => rand(500, 2000),
                'payment_date'  => now()->subDays(rand(1, 30)),
                'method'        => ['نقداً', 'تحويل بنكي', 'بطاقة'][rand(0, 2)],
                'note'          => 'دفع رسوم دراسية',
                'status'        => 'paid',
            ]);
        }

        echo "✅ Payments seeded successfully.\n";
    }
}
