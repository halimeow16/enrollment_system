<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('subject_schedules')
            ->whereNotNull('instructor')
            ->select('instructor')
            ->distinct()
            ->orderBy('instructor')
            ->get()
            ->map(fn ($schedule) => trim((string) $schedule->instructor))
            ->filter()
            ->unique(fn ($name) => strtolower($name))
            ->each(fn ($name) => DB::table('instructors')->insert([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('instructors');
    }
};
