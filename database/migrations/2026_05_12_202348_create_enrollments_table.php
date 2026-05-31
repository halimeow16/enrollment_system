<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            
            $table->string('student_number')->nullable();
            $table->date('date_filed')->nullable();
            $table->string('school_year')->nullable();

            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            
            $table->string('cellphone')->nullable();
            $table->string('email')->nullable();
            $table->string('last_school')->nullable();

            $table->text('present_address')->nullable();
            $table->string('barangay')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->integer('age')->nullable();
            $table->string('place_of_birth')->nullable();
            
            $table->string('civil_status')->nullable();
            $table->string('gender')->nullable();
            $table->string('religion')->nullable();

            $table->string('father_name')->nullable();
            $table->text('father_address')->nullable();
            $table->string('father_cpNumber')->nullable();

            $table->string('mother_name')->nullable();
            $table->text('mother_address')->nullable();
            $table->string('mother_cpNumber')->nullable();

            $table->string('course_code')->nullable();
            $table->string('course_name')->nullable();
            $table->string('year_level')->nullable();
            $table->string('semester')->nullable();
            $table->enum('student_type', ['new', 'old', 'transferee'])->default('new');

            $table->json('credentials')->nullable();

            $table->timestamps();

            $table->index(['course_code', 'year_level']);
            $table->index('student_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('enrollments');
    }
};
