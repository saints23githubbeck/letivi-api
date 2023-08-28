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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('other_industry')->nullable();
            $table->foreignId('industry_id')->nullable()->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->string('other_industry')->nullable();
            $table->foreignId('industry_id')->nullable()->change();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('other_industry')->nullable();
            $table->foreignId('industry_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('other_industry');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('other_industry');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('other_industry');
        });
    }
};
