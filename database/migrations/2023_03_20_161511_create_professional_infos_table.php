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
        Schema::create('professional_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('organization')->nullable();
            $table->string('role')->nullable();
            $table->string('country')->nullable();
            $table->string('awards')->nullable();
            $table->string('qualification')->nullable();
            $table->string('education')->nullable();
            $table->text('work_experience')->nullable();
            $table->string('nomination')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professional_infos');
    }
};
