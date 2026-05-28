<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CustomTemplateField;
use App\Models\Enrollment;
use App\Models\StudentId;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class IdRequirementController extends Controller
{
    public function create(): View
    {
        return view('id-requirements.create', [
            'customIdFields' => $this->customTemplateFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'contact' => ['required', 'string', 'max:120'],
            'emergency_contact_name' => ['required', 'string', 'max:120'],
            'emergency_contact_relationship' => ['required', 'string', 'max:80'],
            'emergency_contact_number' => ['required', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'custom_fields' => ['nullable', 'array'],
            'custom_fields.*' => ['nullable', 'string', 'max:255'],
            'custom_field_files' => ['nullable', 'array'],
            'custom_field_files.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'contact.required' => 'Enter the email or cellphone number used during enrollment.',
            'emergency_contact_name.required' => 'Enter an emergency contact name.',
            'emergency_contact_relationship.required' => 'Enter your emergency contact relationship.',
            'emergency_contact_number.required' => 'Enter your emergency contact number.',
            'photo.image' => 'The student photo must be a valid image file.',
            'signature.image' => 'The signature must be a valid image file.',
            'custom_field_files.*.image' => 'Custom photo fields must be valid image files.',
        ]);
        $data['custom_fields'] = $this->validatedCustomFields($data['custom_fields'] ?? []);
        $this->ensureRequiredTextCustomFields($data['custom_fields']);

        $matches = Enrollment::query()
            ->whereRaw('LOWER(first_name) = ?', [strtolower(trim($data['first_name']))])
            ->whereRaw('LOWER(last_name) = ?', [strtolower(trim($data['last_name']))])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->where('enrollment_status', 'enrolled')
            ->get()
            ->filter(fn (Enrollment $enrollment) => $this->contactMatches($enrollment, $data['contact']))
            ->values();

        if ($matches->count() !== 1) {
            return back()
                ->withInput($request->except(['photo', 'signature']))
                ->withErrors([
                    'verification' => 'We could not verify your enrollment details. Please check your information or contact the registrar.',
                ]);
        }

        $enrollment = $matches->first();
        $studentId = $this->studentIdForEnrollment($enrollment);
        $oldValues = $studentId->exists ? $studentId->only([
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
            'photo_path',
            'signature_path',
            'custom_fields',
        ]) : [];

        if ($request->hasFile('photo')) {
            if ($studentId->photo_path) {
                Storage::disk('public')->delete($studentId->photo_path);
            }

            $photo = $request->file('photo');
            $studentId->photo_path = $photo->store("student-id-requirements/{$enrollment->id}", 'public');
            $studentId->photo_mime_type = $photo->getMimeType();
        }

        if ($request->hasFile('signature')) {
            if ($studentId->signature_path) {
                Storage::disk('public')->delete($studentId->signature_path);
            }

            $signature = $request->file('signature');
            $studentId->signature_path = $signature->store("student-id-requirements/{$enrollment->id}", 'public');
            $studentId->signature_mime_type = $signature->getMimeType();
        }

        $customFields = array_merge($studentId->custom_fields ?? [], $data['custom_fields']);
        $this->storeCustomPhotoFields($request, $enrollment, $customFields);
        $this->ensureRequiredPhotoCustomFields($customFields);

        $studentId->fill([
            'school_year' => $enrollment->school_year,
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'emergency_contact_number' => $data['emergency_contact_number'],
            'custom_fields' => $customFields,
            'requirements_status' => 'pending',
            'status' => $studentId->status ?: 'draft',
            'submitted_at' => now(),
        ])->save();

        ActivityLog::record('id_requirements_submitted', $studentId, $oldValues, [
            'enrollment_id' => $enrollment->id,
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'emergency_contact_name' => $studentId->emergency_contact_name,
            'emergency_contact_relationship' => $studentId->emergency_contact_relationship,
            'emergency_contact_number' => $studentId->emergency_contact_number,
            'photo_uploaded' => (bool) $request->hasFile('photo'),
            'signature_uploaded' => (bool) $request->hasFile('signature'),
            'custom_fields' => $customFields,
        ], $request);

        return back()->with('success', 'Your ID requirements were submitted successfully. The admin will review them before ID generation.');
    }

    private function contactMatches(Enrollment $enrollment, string $contact): bool
    {
        $contact = trim($contact);
        $normalizedContact = $this->normalizeContact($contact);
        $email = strtolower(trim((string) $enrollment->email));
        $cellphone = $this->normalizeContact((string) $enrollment->cellphone);

        return ($email !== '' && strtolower($contact) === $email)
            || ($cellphone !== '' && $normalizedContact === $cellphone);
    }

    private function normalizeContact(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function customTemplateFields()
    {
        return CustomTemplateField::where('scope', 'id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    private function validatedCustomFields(array $values): array
    {
        return $this->customTemplateFields()
            ->where('input_type', '!=', 'photo')
            ->mapWithKeys(function (CustomTemplateField $field) use ($values) {
                $value = trim((string) ($values[$field->key] ?? ''));

                return [$field->key => $value];
            })
            ->filter(fn ($value) => $value !== '')
            ->all();
    }

    private function ensureRequiredTextCustomFields(array $values): void
    {
        $missing = $this->customTemplateFields()
            ->filter(fn (CustomTemplateField $field) => $field->input_type !== 'photo' && $field->is_required && trim((string) ($values[$field->key] ?? '')) === '')
            ->mapWithKeys(fn (CustomTemplateField $field) => ["custom_fields.{$field->key}" => "{$field->label} is required."])
            ->all();

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }

    private function storeCustomPhotoFields(Request $request, Enrollment $enrollment, array &$customFields): void
    {
        $photoFields = $this->customTemplateFields()
            ->where('input_type', 'photo');

        foreach ($photoFields as $field) {
            $file = $request->file("custom_field_files.{$field->key}");

            if (! $file) {
                continue;
            }

            $oldPath = $customFields[$field->key] ?? null;

            if (is_string($oldPath) && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            $customFields[$field->key] = $file->store("student-id-requirements/{$enrollment->id}/custom", 'public');
        }
    }

    private function ensureRequiredPhotoCustomFields(array $values): void
    {
        $missing = $this->customTemplateFields()
            ->filter(function (CustomTemplateField $field) use ($values) {
                $path = $values[$field->key] ?? null;

                return $field->input_type === 'photo'
                    && $field->is_required
                    && (! is_string($path) || ! Storage::disk('public')->exists($path));
            })
            ->mapWithKeys(fn (CustomTemplateField $field) => ["custom_field_files.{$field->key}" => "{$field->label} is required."])
            ->all();

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }

    private function studentIdForEnrollment(Enrollment $enrollment): StudentId
    {
        try {
            return StudentId::firstOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'school_year' => $enrollment->school_year,
                    'status' => 'draft',
                    'requirements_status' => 'pending',
                ]
            );
        } catch (UniqueConstraintViolationException) {
            return StudentId::where('enrollment_id', $enrollment->id)->firstOrFail();
        }
    }
}
