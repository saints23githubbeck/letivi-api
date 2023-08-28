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
        Schema::create('my_saves', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('path');
            $table->foreignId('user_id');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('my_saves');
    }
};
