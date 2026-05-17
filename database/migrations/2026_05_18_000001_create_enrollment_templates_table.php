<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Enrollment Form');
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->decimal('page_width', 8, 2)->default(381);
            $table->decimal('page_height', 8, 2)->default(508);
            $table->json('field_mappings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_templates');
    }
};
