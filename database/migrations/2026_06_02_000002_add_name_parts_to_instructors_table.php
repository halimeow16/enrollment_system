<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->string('title', 20)->nullable()->after('id');
            $table->string('first_name')->nullable()->after('title');
            $table->string('middle_initial', 10)->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_initial');
        });

        DB::table('instructors')->orderBy('id')->get()->each(function ($instructor): void {
            $parts = preg_split('/\s+/', trim((string) $instructor->name)) ?: [];
            $titles = ['Mr.', 'Mrs.', 'Ms.', 'Dr.', 'Prof.', 'Engr.', 'Mx.'];
            $title = in_array($parts[0] ?? '', $titles, true) ? array_shift($parts) : null;
            $lastName = count($parts) > 1 ? array_pop($parts) : ($parts[0] ?? $instructor->name);
            $firstName = trim(implode(' ', $parts)) ?: $lastName;

            DB::table('instructors')
                ->where('id', $instructor->id)
                ->update([
                    'title' => $title,
                    'first_name' => $firstName,
                    'middle_initial' => null,
                    'last_name' => $lastName,
                    'updated_at' => now(),
                ]);
        });
    }

    public function down(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->dropColumn(['title', 'first_name', 'middle_initial', 'last_name']);
        });
    }
};
