<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceTable extends Migration
{
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late']);
            $table->unsignedBigInteger('taken_by_supervisor_id');
            $table->timestamps();

            // علاقات مفتاحية
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('taken_by_supervisor_id')->references('id')->on('supervisors')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance');
    }
}
