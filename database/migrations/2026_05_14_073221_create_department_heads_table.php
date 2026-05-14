<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_heads', function (Blueprint $table) {
            $table->id();
            $table->string('course_code')->index();
            $table->string('name');
            $table->string('title')->nullable();   
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_heads');
    }
};