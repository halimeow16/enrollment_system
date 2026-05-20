<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\StudentId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class IdRequirementController extends Controller
{
    public function create(): View
    {
        return view('id-requirements.create');
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
        ], [
            'contact.required' => 'Enter the email or cellphone number used during enrollment.',
            'emergency_contact_name.required' => 'Enter an emergency contact name.',
            'emergency_contact_relationship.required' => 'Enter your emergency contact relationship.',
            'emergency_contact_number.required' => 'Enter your emergency contact number.',
            'photo.image' => 'The student photo must be a valid image file.',
            'signature.image' => 'The signature must be a valid image file.',
        ]);

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
        $studentId = StudentId::firstOrNew(['enrollment_id' => $enrollment->id]);
        $oldValues = $studentId->exists ? $studentId->only([
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_number',
            'photo_path',
            'signature_path',
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

        $studentId->fill([
            'school_year' => $enrollment->school_year,
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'emergency_contact_number' => $data['emergency_contact_number'],
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
        ], $request);

        return back()->with('success', 'Your ID requirements were submitted successfully. The registrar will review them before ID generation.');
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
}
