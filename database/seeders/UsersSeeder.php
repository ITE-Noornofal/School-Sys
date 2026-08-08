<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Accountant;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // ─── إضافة Admin ───
        $admin = Admin::create([
            'name'      => 'ITE.Noor',
            'email'     => 'ITE.Noor@gmail.com',
            'password'  => Hash::make('password123'),
        ]);

        // ─── إضافة Accountant ───
        $accountant = Accountant::create([
            'name'      => 'خالد علي',
            'email'     => 'accountant@school.com',
            'password'  => Hash::make('password123'),
        ]);
    }
}
