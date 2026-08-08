<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('due_payment_templates', function (Blueprint $table) {
            $table->id();

            // ← تعديل هنا: grades → grade_levels
            $table->foreignId('grade_level_id')  // أو حافظ على grade_id
                  ->nullable()
                  ->constrained('grade_levels')   // ← تحديد الجدول الصحيح
                  ->onDelete('set null');

            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('due_payment_templates');
    }
};
