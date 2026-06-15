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
    Schema::create('teachers', function (Blueprint $table) {
        $table->id();
        $table->string('email')->unique(); // يُضاف فقط الإيميل من قِبل المدير
        $table->string('name')->nullable(); // يتم تعبئته لاحقًا عند أول تسجيل
        $table->string('password')->nullable(); // يتم تعبئته لاحقًا


        $table->string('subject')->nullable(); // تخصص المدرس
        $table->string('address')->nullable(); // العنوان
        $table->string('phone')->nullable();   // رقم الهاتف
        $table->string('profile_image')->nullable(); // صورة شخصية (رابط فقط)

          $table->rememberToken(); // هذا يضيف عمود remember_token
        $table->timestamp('email_verified_at')->nullable();
          $table->timestamps();
    });
}

  /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
