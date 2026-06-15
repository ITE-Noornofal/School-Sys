<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\DuePaymentTemplate;
use App\Models\ClassRoom;

class GradeWithPaymentsSeeder extends Seeder
{
    public function run()
    {
        $grades = [
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12',
        ];

        foreach ($grades as $index => $name) {
            $grade = Grade::updateOrCreate(
                ['id' => $index + 1],
                ['name' => $name]
            );

            $baseAmount = 500 + (100 * $index); // تزداد حسب الصف
            $registrationFee = 200 + (20 * $index); // تزداد حسب الصف

            // حذف القوالب السابقة لتفادي التكرار
            DuePaymentTemplate::where('grade_id', $grade->id)->delete();

            // رسوم الفصل الأول
            DuePaymentTemplate::create([
                'grade_id' => $grade->id,
                'title' => "First Semester Fee - {$grade->name}",
                'description' => "Tuition fee for the first semester of {$grade->name}.",
                'amount' => $baseAmount,
                'penalty_per_day' => 10,
            ]);

            // رسوم الفصل الثاني
            DuePaymentTemplate::create([
                'grade_id' => $grade->id,
                'title' => "Second Semester Fee - {$grade->name}",
                'description' => "Tuition fee for the second semester of {$grade->name}.",
                'amount' => $baseAmount,
                'penalty_per_day' => 10,
            ]);

            // رسوم التسجيل السنوي
            DuePaymentTemplate::create([
                'grade_id' => $grade->id,
                'title' => "Annual Registration Fee - {$grade->name}",
                'description' => "Annual registration fee for {$grade->name}.",
                'amount' => $registrationFee,
                'penalty_per_day' => 5,
            ]);

            // ✅ إنشاء صفوف دراسية (ClassRooms) لكل Grade
            for ($i = 1; $i <= 3; $i++) {
                ClassRoom::updateOrCreate(
                    [
                        'grade_id' => $grade->id,
                        'name' => "{$grade->name} - Section {$i}"
                    ],
                    [
                        'grade_id' => $grade->id,
                        'name' => "{$grade->name} - Section {$i}"
                    ]
                );
            }
        }
    }
}
