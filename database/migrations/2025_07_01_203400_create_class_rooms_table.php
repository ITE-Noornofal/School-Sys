<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // ← تغيير من grades إلى grade_levels
            $table->foreignId('grade_level_id')  // ← أو حافظ على grade_id لكن عدّل الـ constrained
                  ->constrained('grade_levels')   // ← تحديد الجدول صراحة
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
