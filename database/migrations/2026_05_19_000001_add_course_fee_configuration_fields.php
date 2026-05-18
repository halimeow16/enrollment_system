<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('fee_configurations', 'course_code')) {
                $table->string('course_code', 30)->nullable()->after('id');
            }

            if (! Schema::hasColumn('fee_configurations', 'fee_type')) {
                $table->string('fee_type', 50)->nullable()->after('course_code');
            }
        });

        $defaults = [
            'BSBA' => [
                'tuition_per_unit' => 441.71,
                'misc_fee' => 3118.68,
                'hands_on_fee' => 1214.71,
                'lab_fee' => 0,
                'nstp_fee' => 662.00,
            ],
            'BSA' => [
                'tuition_per_unit' => 485.89,
                'misc_fee' => 3588.90,
                'hands_on_fee' => 662.57,
                'lab_fee' => 0,
                'nstp_fee' => 728.82,
            ],
            'ACT' => [
                'tuition_per_unit' => 485.88,
                'misc_fee' => 3588.90,
                'hands_on_fee' => 552.14,
                'lab_fee' => 993.85,
                'nstp_fee' => 728.82,
            ],
            'BSIT' => [
                'tuition_per_unit' => 485.88,
                'misc_fee' => 3588.90,
                'hands_on_fee' => 552.14,
                'lab_fee' => 993.85,
                'nstp_fee' => 728.82,
            ],
            'BSCS' => [
                'tuition_per_unit' => 485.88,
                'misc_fee' => 3588.90,
                'hands_on_fee' => 552.14,
                'lab_fee' => 993.85,
                'nstp_fee' => 728.82,
            ],
            'BSOM' => [
                'tuition_per_unit' => 441.71,
                'misc_fee' => 3118.68,
                'hands_on_fee' => 1214.71,
                'lab_fee' => 0,
                'nstp_fee' => 662.00,
            ],
        ];

        foreach ($defaults as $courseCode => $fees) {
            foreach ($fees as $feeType => $amount) {
                DB::table('fee_configurations')->updateOrInsert(
                    ['course_code' => $courseCode, 'fee_type' => $feeType],
                    [
                        'name' => str($feeType)->replace('_', ' ')->title()->toString(),
                        'basis' => $feeType === 'tuition_per_unit' || $feeType === 'lab_fee' ? 'per_unit' : 'flat',
                        'amount' => $amount,
                        'applies_to' => 'ALL',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('fee_configurations', function (Blueprint $table) {
            if (Schema::hasColumn('fee_configurations', 'course_code')) {
                $table->dropColumn('course_code');
            }

            if (Schema::hasColumn('fee_configurations', 'fee_type')) {
                $table->dropColumn('fee_type');
            }
        });
    }
};