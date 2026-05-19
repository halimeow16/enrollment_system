<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_ids', function (Blueprint $table) {
            if (! Schema::hasColumn('student_ids', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('signature_mime_type');
            }

            if (! Schema::hasColumn('student_ids', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            }

            if (! Schema::hasColumn('student_ids', 'emergency_contact_number')) {
                $table->string('emergency_contact_number')->nullable()->after('emergency_contact_relationship');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_ids', function (Blueprint $table) {
            if (Schema::hasColumn('student_ids', 'emergency_contact_number')) {
                $table->dropColumn('emergency_contact_number');
            }

            if (Schema::hasColumn('student_ids', 'emergency_contact_relationship')) {
                $table->dropColumn('emergency_contact_relationship');
            }

            if (Schema::hasColumn('student_ids', 'emergency_contact_name')) {
                $table->dropColumn('emergency_contact_name');
            }
        });
    }
};
