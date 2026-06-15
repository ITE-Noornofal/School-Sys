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
  Schema::create('complaints', function (Blueprint $table) {
    $table->id();

    // ولي الأمر
    $table->foreignId('guardian_id')->nullable()->constrained()->onDelete('set null');
    $table->text('guardian_id_enc')->nullable();
    $table->string('guardian_id_hash')->nullable()->index();

    // الطالب
    $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
    $table->text('student_id_enc')->nullable();
    $table->string('student_id_hash')->nullable()->index();

    $table->foreignId('class_room_id')->nullable()->constrained()->onDelete('set null');
    $table->text('content');
    $table->boolean('is_anonymous')->default(false);
    $table->enum('status', ['pending', 'reviewed', 'rejected'])->default('pending');

    $table->timestamps();
});


}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
