<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();

            $table->decimal('lecture_units', 4, 1)->default(0);
            $table->decimal('laboratory_units', 4, 1)->default(0);
            $table->decimal('total_units', 4, 1)->default(0);

            $table->timestamps();

            $table->unique(['enrollment_id', 'subject_id']);
            $table->index('enrollment_id');
        });

        Schema::create('schedule_conflicts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('subject_a_id')->constrained('subjects');
            $table->foreignId('subject_b_id')->constrained('subjects');
            $table->string('conflict_type')->default('time'); 
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_conflicts');
        Schema::dropIfExists('enrollment_subjects');
    }
};