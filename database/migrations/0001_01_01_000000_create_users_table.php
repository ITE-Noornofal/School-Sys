<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =========================================================
        // USERS
        // الحساب الموحد لجميع المستخدمين
        // Student / Teacher / Guardian / Admin
        // =========================================================

        Schema::create('users', function (Blueprint $table) {

            $table->id();

            // =====================================================
            // Basic Information
            // =====================================================

            $table->string('Full_name')->nullable();

            $table->string('father_name')->nullable();

            $table->string('mother_name')->nullable();

            $table->string('phone_number')->nullable();

            $table->enum('gender', [
                'male',
                'female'
            ])->nullable();

            $table->date('birth_date')->nullable();

            $table->string('address')->nullable();

            $table->string('academic_year')->nullable();


            // =====================================================
            // Authentication
            // =====================================================

            $table->string('email')->unique();

            $table->timestamp('email_verified_at')->nullable();

            // Nullable لأن المستخدم يتم إنشاؤه أولاً
            // من خلال Register بواسطة Email فقط
            // ثم يقوم بوضع Password لاحقاً
            $table->string('password')->nullable();


            // =====================================================
            // User Role
            //
            // student
            // teacher
            // guardian
            // admin
            // =====================================================

            $table->enum('role', [
                'student',
                'teacher',
                'guardian',
                'admin'
            ])->nullable();


            // =====================================================
            // Laravel Remember Token
            // =====================================================

            $table->rememberToken();

            $table->timestamps();
        });


        // =========================================================
        // PASSWORD RESET TOKENS
        // =========================================================

        Schema::create('password_reset_tokens', function (Blueprint $table) {

            $table->string('email')->primary();

            $table->string('token');

            $table->timestamp('created_at')->nullable();
        });


        // =========================================================
        // SESSIONS
        // =========================================================

        Schema::create('sessions', function (Blueprint $table) {

            $table->string('id')->primary();

            $table->foreignId('user_id')
                ->nullable()
                ->index();

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->longText('payload');

            $table->integer('last_activity')
                ->index();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');

        Schema::dropIfExists('password_reset_tokens');

        Schema::dropIfExists('users');
    }
};
