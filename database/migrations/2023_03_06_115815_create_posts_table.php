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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('type');
            $table->boolean('private')->default(false);
            $table->string('source');
            $table->foreignId('user_id');
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreignId('event_id');
            $table->foreign('event_id')->references('id')->on('events')->restrictOnDelete();
            $table->foreignId('business_id');
            $table->foreign('business_id')->references('id')->on('businesses')->restrictOnDelete();
            $table->foreignId('album_id');
            $table->foreign('album_id')->references('id')->on('albums')->restrictOnDelete();
            $table->foreignId('project_id');
            $table->foreign('project_id')->references('id')->on('projects')->restrictOnDelete();
            $table->foreignId('category_id');
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
