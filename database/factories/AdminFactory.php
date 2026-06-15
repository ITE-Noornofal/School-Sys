<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AdminFactory extends Factory
{
    // لو لم يتم تحديد نموذج في الأمر، عرف هذا:
    protected $model = \App\Models\Admin::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'), // كلمة المرور الافتراضية: password
            'remember_token' => Str::random(10),
        ];
    }
}
