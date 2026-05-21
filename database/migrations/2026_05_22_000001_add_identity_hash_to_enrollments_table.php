<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'enrollment_identity_hash')) {
                $table->string('enrollment_identity_hash', 64)
                    ->nullable()
                    ->after('semester');
            }
        });

        $rows = DB::table('enrollments')
            ->select([
                'id',
                'first_name',
                'middle_name',
                'last_name',
                'date_of_birth',
                'school_year',
                'year_level',
                'semester',
            ])
            ->orderBy('id')
            ->get();

        $seen = [];

        foreach ($rows as $row) {
            $hash = $this->identityHash((array) $row);

            if (! $hash || isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;

            DB::table('enrollments')
                ->where('id', $row->id)
                ->update(['enrollment_identity_hash' => $hash]);
        }

        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique('enrollment_identity_hash', 'enrollments_identity_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique('enrollments_identity_hash_unique');

            if (Schema::hasColumn('enrollments', 'enrollment_identity_hash')) {
                $table->dropColumn('enrollment_identity_hash');
            }
        });
    }

    private function identityHash(array $data): ?string
    {
        $parts = [
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['last_name'] ?? '',
            $data['date_of_birth'] ?? '',
            $data['school_year'] ?? '',
            $data['year_level'] ?? '',
            $data['semester'] ?? '',
        ];

        $normalized = array_map(fn ($value) => strtolower(trim((string) $value)), $parts);

        if (in_array('', [$normalized[0], $normalized[2], $normalized[3], $normalized[4], $normalized[5], $normalized[6]], true)) {
            return null;
        }

        return hash('sha256', implode('|', $normalized));
    }
};
