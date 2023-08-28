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
        Schema::create('user_projects', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('specialize')->nullable();
            $table->boolean('private')->default(false);
            $table->string('source')->nullable();
            $table->string('country')->nullable();
            $table->string('media')->nullable();
            $table->string('media_type')->nullable();
            $table->string('type');
            $table->foreignId('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreignId('industry_id')->nullable();
            $table->foreign('industry_id')->references('id')->on('industries')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_projects');
    }
};
