<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pdf_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->string('school_year')->nullable();   
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('student_ids', function (Blueprint $table) {
            $table->string('school_year')->nullable()->after('enrollment_id');
        });

        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->unique(['room_id', 'day_id', 'time_slot_id'], 'unique_room_day_timeslot');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');          
            $table->string('model_type');      
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {

        Schema::dropIfExists('activity_logs');

        Schema::table('subject_schedules', function (Blueprint $table) {
            $table->dropUnique('unique_room_day_timeslot');
        });

        Schema::table('student_ids', function (Blueprint $table) {
            $table->dropColumn('school_year');
        });

        Schema::dropIfExists('pdf_templates');
    }
};