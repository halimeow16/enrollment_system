<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subject_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('subject_schedules', 'schedule_for')) {
                $table->string('schedule_for', 80)->default('Whole Class')->after('schedule_type');
            }
        });

        if (! $this->indexExists('subject_schedules', 'subject_schedules_subject_id_index')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->index('subject_id', 'subject_schedules_subject_id_index');
            });
        }

        if (! $this->indexExists('subject_schedules', 'subject_schedules_room_id_index')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->index('room_id', 'subject_schedules_room_id_index');
            });
        }

        if ($this->indexExists('subject_schedules', 'subject_schedules_subject_id_day_id_time_slot_id_unique')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->dropUnique('subject_schedules_subject_id_day_id_time_slot_id_unique');
            });
        }

        if ($this->indexExists('subject_schedules', 'unique_room_day_timeslot')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->dropUnique('unique_room_day_timeslot');
            });
        }

        if (! $this->indexExists('subject_schedules', 'subject_schedules_subject_type_for_index')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->index(['subject_id', 'schedule_type', 'schedule_for'], 'subject_schedules_subject_type_for_index');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('subject_schedules', 'subject_schedules_subject_type_for_index')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->dropIndex('subject_schedules_subject_type_for_index');
            });
        }

        if (! $this->indexExists('subject_schedules', 'subject_schedules_subject_id_day_id_time_slot_id_unique')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->unique(['subject_id', 'day_id', 'time_slot_id']);
            });
        }

        if (! $this->indexExists('subject_schedules', 'unique_room_day_timeslot')) {
            Schema::table('subject_schedules', function (Blueprint $table) {
                $table->unique(['room_id', 'day_id', 'time_slot_id'], 'unique_room_day_timeslot');
            });
        }

        Schema::table('subject_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('subject_schedules', 'schedule_for')) {
                $table->dropColumn('schedule_for');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table}"))
            ->contains(fn ($row) => $row->Key_name === $index);
    }
};
