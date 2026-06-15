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
       Schema::create('due_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('guardian_id')->constrained()->onDelete('cascade'); // ولي الأمر
    $table->foreignId('student_id')->constrained()->onDelete('cascade');
    $table->foreignId('accountant_id')->nullable()->constrained()->onDelete('set null'); // المحاسب
    $table->decimal('amount', 10, 2);
    $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
     $table->decimal('penalty', 10, 2)->default(0);
    $table->string('description')->nullable(); // وصف أو سبب الدفعة
     $table->foreignId('template_id')->nullable()->constrained('due_payment_templates')->nullOnDelete();
    $table->date('due_date')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_payments');
    }
};
