<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountSettingsController extends Controller
{
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

    private function adminCount(): int
    {
        return User::where('user_type', 'admin')->count();
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
