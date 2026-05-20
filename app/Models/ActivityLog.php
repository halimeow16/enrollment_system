<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null
    ): self {
        $request ??= request();

        return self::create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'model_type' => $subject ? class_basename($subject) : 'System',
            'model_id' => $subject?->getKey(),
            'old_values' => self::cleanValues($oldValues) ?: null,
            'new_values' => self::cleanValues($newValues) ?: null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }

    private static function cleanValues(array $values): array
    {
        return collect($values)
            ->reject(fn ($value, $key) => str_contains(strtolower((string) $key), 'password'))
            ->map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value))
            ->all();
    }
}
