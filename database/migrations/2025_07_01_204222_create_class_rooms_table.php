<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
     public function up(): void {
    Schema::create('class_rooms', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('section')->nullable();
        $table->foreignId('grade_id')->constrained()->onDelete('cascade'); // ← الربط مع grades
        $table->foreignId('supervisor_id')->nullable()->constrained()->onDelete('set null');

        $table->foreignId('bus_id')->nullable()->constrained('buses')->onDelete('set null');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
