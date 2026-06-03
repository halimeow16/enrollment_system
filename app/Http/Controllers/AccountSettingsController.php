<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountSettingsController extends Controller
{
    private const BACKUP_VERSION = 1;
    private const BACKUP_ENCRYPTION_VERSION = 2;
    private const BACKUP_KDF_ITERATIONS = 200000;

    public function updateOwn(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required_with:password', 'nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (! empty($data['password']) && ! Hash::check((string) $data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $oldValues = $user->getOriginal();
        $user->save();

        ActivityLog::record('account_updated_self', $user, [
            'name' => $oldValues['name'] ?? null,
            'email' => $oldValues['email'] ?? null,
        ], [
            'name' => $user->name,
            'email' => $user->email,
            'password_changed' => ! empty($data['password']),
        ], $request);

        return response()->json([
            'message' => 'Account updated.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'user_type' => ['required', Rule::in(['registrar', 'department_head'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create($data);

        ActivityLog::record('account_created', $user, [], [
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
        ], $request);

        return response()->json([
            'message' => 'Account created.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'user_type' => ['required', Rule::in(['admin', 'registrar', 'department_head'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->user()->id === $user->id && $data['user_type'] !== 'admin') {
            return response()->json([
                'message' => 'You cannot remove your own admin role.',
            ], 422);
        }

        if ($user->user_type === 'admin' && $data['user_type'] !== 'admin' && $this->adminCount() <= 1) {
            return response()->json([
                'message' => 'At least one admin account must remain.',
            ], 422);
        }

        $oldValues = $user->only(['name', 'email', 'user_type']);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'user_type' => $data['user_type'],
        ]);

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        ActivityLog::record('account_updated', $user, $oldValues, [
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'password_changed' => ! empty($data['password']),
        ], $request);

        return response()->json([
            'message' => 'Account updated.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'admin', 403);

        if ($request->user()->id === $user->id) {
            return response()->json([
                'message' => 'You cannot remove your own account.',
            ], 422);
        }

        if ($user->user_type === 'admin' && $this->adminCount() <= 1) {
            return response()->json([
                'message' => 'At least one admin account must remain.',
            ], 422);
        }

        $oldValues = $user->only(['name', 'email', 'user_type']);
        $user->delete();

        ActivityLog::record('account_removed', $user, $oldValues, [], $request);

        return response()->json([
            'message' => 'Account removed.',
            'id' => $user->id,
        ]);
    }

    public function exportDatabase(Request $request)
    {
        abort_unless($request->user()?->user_type === 'admin', 403);

        $data = $request->validate([
            'password' => ['required', 'string'],
            'backup_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $this->confirmPassword($request, $data['password']);

        $payload = [
            'version' => self::BACKUP_VERSION,
            'exported_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'database' => config('database.default'),
            'tables' => collect($this->backupTables())
                ->mapWithKeys(fn (string $table) => [$table => $this->backupTableRows($table)])
                ->all(),
        ];
        $encrypted = $this->encryptBackupPayload($payload, $data['backup_password']);
        $fileName = 'enrollment-system-' . now()->format('Y-m-d-His') . '.esbackup';

        ActivityLog::record('database_exported', null, [], [
            'tables' => array_keys($payload['tables']),
        ], $request);

        return response()->streamDownload(function () use ($encrypted): void {
            echo $encrypted;
        }, $fileName, [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function importDatabase(Request $request): JsonResponse
    {
        abort_unless($request->user()?->user_type === 'admin', 403);

        $data = $request->validate([
            'password' => ['required', 'string'],
            'backup_password' => ['required', 'string'],
            'backup_file' => ['required', 'file', 'max:51200'],
            'replace_confirmation' => ['accepted'],
        ], [
            'replace_confirmation.accepted' => 'Please confirm that all current data will be replaced.',
        ]);

        $this->confirmPassword($request, $data['password']);

        try {
            $encrypted = $request->file('backup_file')->get();
            $payload = $this->decryptBackupPayload($encrypted, $data['backup_password']);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'backup_file' => 'The uploaded backup could not be decrypted. Check the backup password and make sure this is a valid enrollment system backup.',
            ]);
        }

        if (($payload['version'] ?? null) !== self::BACKUP_VERSION || ! is_array($payload['tables'] ?? null)) {
            throw ValidationException::withMessages([
                'backup_file' => 'The uploaded backup version is not supported.',
            ]);
        }

        $allowedTables = $this->backupTables();
        $payloadTables = array_intersect_key($payload['tables'], array_flip($allowedTables));

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach (array_reverse($allowedTables) as $table) {
                DB::table($table)->truncate();
            }

            foreach ($allowedTables as $table) {
                $columns = array_flip($this->backupTableColumns($table));
                $rows = collect($payloadTables[$table] ?? [])
                    ->map(fn (array $row) => array_intersect_key($row, $columns))
                    ->all();

                foreach (array_chunk($rows, 250) as $chunk) {
                    if ($chunk) {
                        DB::table($table)->insert($chunk);
                    }
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        ActivityLog::create([
            'user_id' => null,
            'action' => 'database_imported',
            'model_type' => 'System',
            'model_id' => null,
            'old_values' => null,
            'new_values' => [
                'exported_at' => $payload['exported_at'] ?? null,
                'tables' => array_keys($payloadTables),
            ],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Database restored. The uploaded encrypted backup replaced the current application data.',
        ]);
    }

    private function adminCount(): int
    {
        return User::where('user_type', 'admin')->count();
    }

    private function confirmPassword(Request $request, string $password): void
    {
        if (! Hash::check($password, (string) $request->user()?->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password confirmation is incorrect.',
            ]);
        }
    }

    private function encryptBackupPayload(array $payload, string $password): string
    {
        $salt = random_bytes(16);
        $iv = random_bytes(12);
        $tag = '';
        $key = $this->backupEncryptionKey($password, $salt);
        $plainText = gzencode(json_encode($payload, JSON_THROW_ON_ERROR), 9);
        $cipherText = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($cipherText === false) {
            throw new \RuntimeException('Unable to encrypt database backup.');
        }

        return json_encode([
            'format' => 'comteq-enrollment-backup',
            'version' => self::BACKUP_ENCRYPTION_VERSION,
            'cipher' => 'aes-256-gcm',
            'kdf' => 'pbkdf2-sha256',
            'iterations' => self::BACKUP_KDF_ITERATIONS,
            'salt' => base64_encode($salt),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($cipherText),
        ], JSON_THROW_ON_ERROR);
    }

    private function decryptBackupPayload(string $encrypted, string $password): array
    {
        $metadata = json_decode($encrypted, true);

        if (is_array($metadata) && ($metadata['format'] ?? null) === 'comteq-enrollment-backup') {
            if (($metadata['version'] ?? null) !== self::BACKUP_ENCRYPTION_VERSION) {
                throw new \RuntimeException('Unsupported backup encryption version.');
            }

            $salt = base64_decode((string) ($metadata['salt'] ?? ''), true);
            $iv = base64_decode((string) ($metadata['iv'] ?? ''), true);
            $tag = base64_decode((string) ($metadata['tag'] ?? ''), true);
            $cipherText = base64_decode((string) ($metadata['data'] ?? ''), true);

            if ($salt === false || $iv === false || $tag === false || $cipherText === false) {
                throw new \RuntimeException('Invalid encrypted backup.');
            }

            $key = $this->backupEncryptionKey($password, $salt, (int) ($metadata['iterations'] ?? self::BACKUP_KDF_ITERATIONS));
            $plainText = openssl_decrypt($cipherText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

            if ($plainText === false) {
                throw new \RuntimeException('Invalid backup password.');
            }

            $json = gzdecode($plainText);

            if ($json === false) {
                throw new \RuntimeException('Invalid backup payload.');
            }

            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        }

        $legacy = Crypt::decryptString($encrypted);
        $json = gzdecode(base64_decode($legacy, true));

        if ($json === false) {
            throw new \RuntimeException('Invalid legacy backup payload.');
        }

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function backupEncryptionKey(string $password, string $salt, int $iterations = self::BACKUP_KDF_ITERATIONS): string
    {
        return hash_pbkdf2('sha256', $password, $salt, $iterations, 32, true);
    }

    private function backupTables(): array
    {
        $skipTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
            'password_reset_tokens',
            'sessions',
        ];

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => (string) array_values((array) $row)[0])
            ->reject(fn (string $table) => in_array($table, $skipTables, true))
            ->values()
            ->all();
    }

    private function backupTableRows(string $table): array
    {
        $columns = array_flip($this->backupTableColumns($table));

        return DB::table($table)
            ->get()
            ->map(fn ($row) => array_intersect_key((array) $row, $columns))
            ->all();
    }

    private function backupTableColumns(string $table): array
    {
        return collect(DB::select(
            'SELECT COLUMN_NAME, EXTRA FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [DB::getDatabaseName(), $table]
        ))
            ->reject(fn ($column) => str_contains(strtoupper((string) $column->EXTRA), 'GENERATED'))
            ->map(fn ($column) => (string) $column->COLUMN_NAME)
            ->values()
            ->all();
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
        ];
    }
}
