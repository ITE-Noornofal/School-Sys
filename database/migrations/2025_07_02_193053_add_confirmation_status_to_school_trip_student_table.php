<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up()
{
    Schema::table('school_trip_student', function (Blueprint $table) {
        $table->string('confirmation_status')->default('pending'); // أو enum مثلاً
    });
}

public function down()
{
    Schema::table('school_trip_student', function (Blueprint $table) {
        $table->dropColumn('confirmation_status');
    });
}

};
