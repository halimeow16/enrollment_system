<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'first_name', 'middle_initial', 'last_name', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function displayName(array $data): string
    {
        $middleInitial = trim((string) ($data['middle_initial'] ?? ''));
        $middleInitial = $middleInitial === '' ? '' : rtrim($middleInitial, '.') . '.';

        return preg_replace('/\s+/', ' ', trim(implode(' ', array_filter([
            $data['title'] ?? null,
            $data['first_name'] ?? null,
            $middleInitial,
            $data['last_name'] ?? null,
        ]))));
    }
}
