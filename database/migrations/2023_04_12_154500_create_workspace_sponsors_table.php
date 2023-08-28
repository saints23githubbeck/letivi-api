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
        Schema::create('workspace_sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('sponsors');
            $table->foreignId('event_id')->nullable();
            $table->foreign('event_id')->references('id')->on('events')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_sponsors');
    }
};
