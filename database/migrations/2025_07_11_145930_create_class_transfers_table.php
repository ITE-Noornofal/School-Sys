<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassTransfersTable extends Migration
{
    public function up()
    {
        Schema::create('class_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            $table->foreignId('from_class_room_id')->constrained('class_rooms')->onDelete('cascade');

            $table->foreignId('to_class_room_id')->constrained('class_rooms')->onDelete('cascade');

            $table->foreignId('transferred_by')->nullable()->constrained('users')->onDelete('set null');

            $table->string('reason')->nullable();

            $table->timestamp('transfer_date')->useCurrent();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('class_transfers');
    }
}
