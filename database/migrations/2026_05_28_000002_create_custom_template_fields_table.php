<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_template_fields', function (Blueprint $table) {
            $table->id();
            $table->enum('scope', ['enrollment', 'id']);
            $table->string('key')->unique();
            $table->string('label');
            $table->string('input_type', 20)->default('text');
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['scope', 'is_active', 'sort_order']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('credentials');
            }
        });

        Schema::table('student_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('student_ids', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('emergency_contact_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_ids', function (Blueprint $table) {
            if (Schema::hasColumn('student_ids', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });

        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });

        Schema::dropIfExists('custom_template_fields');
    }
};
