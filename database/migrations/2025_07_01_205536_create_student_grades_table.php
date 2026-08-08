<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');  // ← بدون constrained
            $table->unsignedBigInteger('teacher_id'); // ← بدون constrained
            $table->string('subject');
            $table->decimal('grade', 5, 2);
            $table->string('semester')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
