<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_trips', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger

            $table->string('title');
            $table->text('description')->nullable();
            $table->date('trip_date');
            $table->string('location');
            $table->foreignId('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('supervisor_id')->nullable()->constrained()->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_trips');
    }
};
