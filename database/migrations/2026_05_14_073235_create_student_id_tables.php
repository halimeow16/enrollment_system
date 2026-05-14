<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('side', ['front', 'back']);
            $table->string('school_year')->nullable(); 

            $table->json('layout_config');

            $table->string('background_image_path')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_ids', function (Blueprint $table) {
            $table->id();

            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();

            $table->string('photo_path')->nullable();      
            $table->string('photo_mime_type')->nullable(); 

            $table->string('front_output_path')->nullable();
            $table->string('back_output_path')->nullable(); 

            $table->foreignId('front_template_id')->nullable()->constrained('id_templates')->nullOnDelete();
            $table->foreignId('back_template_id')->nullable()->constrained('id_templates')->nullOnDelete();

            $table->enum('status', ['draft', 'generated', 'printed'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique('enrollment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_ids');
        Schema::dropIfExists('id_templates');
    }
};