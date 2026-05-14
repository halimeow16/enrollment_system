<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('days', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('building')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('label')->nullable(); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subject_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('day_id')->constrained('days');
            $table->foreignId('time_slot_id')->constrained('time_slots');
            $table->foreignId('room_id')->constrained('rooms');
            $table->timestamps();

            $table->unique(['subject_id', 'day_id', 'time_slot_id']);
            $table->index(['day_id', 'time_slot_id', 'room_id']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_schedules');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('days');
    }
};