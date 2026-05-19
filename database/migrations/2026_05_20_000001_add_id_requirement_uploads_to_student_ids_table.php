<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('student_ids', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('photo_mime_type');
            }

            if (! Schema::hasColumn('student_ids', 'signature_mime_type')) {
                $table->string('signature_mime_type')->nullable()->after('signature_path');
            }

            if (! Schema::hasColumn('student_ids', 'requirements_status')) {
                $table->enum('requirements_status', ['pending', 'approved', 'rejected'])
                    ->default('pending')
                    ->after('status');
            }

            if (! Schema::hasColumn('student_ids', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('requirements_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_ids', function (Blueprint $table) {
            if (Schema::hasColumn('student_ids', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            if (Schema::hasColumn('student_ids', 'requirements_status')) {
                $table->dropColumn('requirements_status');
            }

            if (Schema::hasColumn('student_ids', 'signature_mime_type')) {
                $table->dropColumn('signature_mime_type');
            }

            if (Schema::hasColumn('student_ids', 'signature_path')) {
                $table->dropColumn('signature_path');
            }
        });
    }
};
