<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');

            $table->string('course_code');
            $table->string('year_level'); 
            $table->enum('semester', ['1st', '2nd', 'Summer']);

            $table->enum('type', ['LEC', 'LAB', 'BOTH'])->default('LEC');
            $table->unsignedTinyInteger('lecture_units')->default(0);
            $table->unsignedTinyInteger('laboratory_units')->default(0);
            $table->unsignedTinyInteger('total_units')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['course_code', 'year_level', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
