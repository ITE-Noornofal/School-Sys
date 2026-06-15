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

            $table->foreignId('student_id')
                  ->constrained('students')
                  ->onDelete('cascade');

            $table->foreignId('teacher_id')
                  ->constrained('teachers')
                  ->onDelete('cascade');

            $table->string('subject');
            $table->decimal('grade', 5, 2); // مثلاً 100.00 كحد أقصى

            $table->string('semester')->nullable(); // فصل دراسي (اختياري)
            $table->text('note')->nullable(); // ملاحظات إضافية

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grades');
    }
};
