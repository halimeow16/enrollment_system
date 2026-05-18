<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        
            $table->enum('basis', ['per_unit', 'per_subject', 'flat'])->default('per_unit');
            $table->decimal('amount', 10, 2);

            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();

            $table->enum('applies_to', ['LEC', 'LAB', 'BOTH', 'ALL'])->default('ALL');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();

            $table->unsignedSmallInteger('total_units')->default(0);

            $table->decimal('initial_assessment', 10, 2)->default(0);

            $table->decimal('final_assessment', 10, 2)->default(0);

            $table->json('breakdown')->nullable();

            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique('enrollment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('fee_configurations');
    }
};
