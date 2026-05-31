<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use setasign\Fpdi\Tcpdf\Fpdi;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\CustomTemplateField;
use App\Models\Enrollment;
use App\Models\DepartmentHead;
use App\Models\EnrollmentTemplate;
use App\Models\FeeConfiguration;
use App\Models\Subject;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class EnrollmentController extends Controller
{
    public function create()
    {
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $subjects = Subject::query()
            ->where('is_active', true)
            ->whereIn('type', ['LEC', 'BOTH'])
            ->orderBy('course_code')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();

        $departmentHeads = DepartmentHead::where('is_active', true)
            ->get()
            ->keyBy('course_code');
        $customEnrollmentFields = $this->customTemplateFields('enrollment');

        return view('enrollment.create', compact('subjects', 'departmentHeads', 'academicYear', 'customEnrollmentFields'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_number'   => 'nullable|string|max:50',
            'date_filed'       => 'nullable|date_format:Y-m-d',
            'school_year'      => 'nullable|string',
            'first_name'       => 'required|string|max:100',
            'middle_name'      => 'nullable|string|max:100',
            'last_name'        => 'required|string|max:100',
            'cellphone'        => ['nullable', 'regex:/^09\d{9}$/'],
            'email'            => 'nullable|email|max:100',
            'last_school'      => 'nullable|string|max:150',
            'present_address'  => 'nullable|string',
            'barangay'         => 'nullable|string',
            'city'             => 'nullable|string',
            'province'         => 'nullable|string',
            'date_of_birth'    => 'required|date_format:Y-m-d',
            'age'              => 'nullable|integer|min:1',
            'place_of_birth'   => 'nullable|string',
            'civil_status'     => 'nullable|string',
            'gender'           => 'nullable|string',
            'religion'         => 'nullable|string',
            'father_name'      => 'nullable|string',
            'father_address'   => 'nullable|string',
            'father_cpNumber'  => ['nullable', 'regex:/^09\d{9}$/'],
            'mother_name'      => 'nullable|string',
            'mother_address'   => 'nullable|string',
            'mother_cpNumber'  => ['nullable', 'regex:/^09\d{9}$/'],
            'course_code'      => 'required|string',
            'course_name'      => 'required|string',
            'year_level'       => 'required|string',
            'semester'         => 'required|string',
            'student_type'     => 'required|in:new,old,transferee',
            'department_head_name' => 'nullable|string|max:120',
            'subject_ids'      => 'nullable|array',
            'subject_ids.*'    => 'integer|exists:subjects,id',
            'credentials'      => 'nullable|array',
            'credentials.*'    => 'string',
            'custom_fields'    => 'nullable|array',
            'custom_fields.*'  => 'nullable|string|max:255',
            'replace_existing' => 'nullable|boolean',
        ], [
            'cellphone.regex' => 'Enter a valid 11-digit cellphone number starting with 09.',
            'father_cpNumber.regex' => 'Enter a valid father cellphone number starting with 09.',
            'mother_cpNumber.regex' => 'Enter a valid mother cellphone number starting with 09.',
        ]);

        $validated['school_year'] = AppSetting::getValue('academic_year', '2026-2027');
        $validated['custom_fields'] = $this->validatedCustomFields('enrollment', $validated['custom_fields'] ?? []);
        $this->ensureRequiredCustomFields('enrollment', $validated['custom_fields']);
        $replaceExisting = (bool) ($validated['replace_existing'] ?? false);
        unset($validated['replace_existing']);
        $validated['enrollment_identity_hash'] = $this->enrollmentIdentityHash($validated);

        if (! $replaceExisting && $this->existingEnrollmentQuery($validated)->exists()) {
            return $this->backWithDuplicateConfirmation($validated);
        }

        $selectedSubjectIds = collect($validated['subject_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        $selectedSubjects = Subject::query()
            ->whereIn('id', $selectedSubjectIds)
            ->whereIn('type', ['LEC', 'BOTH'])
            ->get()
            ->sortBy(fn ($subject) => $selectedSubjectIds->search((int) $subject->id))
            ->values();

        if (! $this->hasAvailablePdfLayout()) {
            return back()
                ->withErrors(['layout' => 'The enrollment form layout is unavailable. Please inform the admin so it can be updated.'])
                ->withInput();
        }


        $validated['department_head_name'] = DepartmentHead::where('course_code', $validated['course_code'] ?? null)
            ->where('is_active', true)
            ->value('name') ?? $validated['department_head_name'] ?? null;

        unset($validated['subject_ids']);

        try {
            $enrollment = DB::transaction(function () use ($validated, $selectedSubjects, $replaceExisting) {
                $duplicate = $replaceExisting
                    ? $this->existingEnrollmentQuery($validated)->lockForUpdate()->first()
                    : null;

                if ($duplicate) {
                    $duplicate->update($validated);
                    $enrollment = $duplicate;
                } else {
                    $enrollment = Enrollment::create($validated);
                }

                $subjectPayload = [];
                foreach ($selectedSubjects as $subject) {
                    $subjectPayload[$subject->id] = [
                        'lecture_units' => $subject->lecture_units,
                        'laboratory_units' => $subject->laboratory_units,
                        'total_units' => $subject->total_units,
                    ];
                }
                $enrollment->subjects()->sync($subjectPayload);

                return $enrollment;
            });
        } catch (UniqueConstraintViolationException) {
            return $this->backWithDuplicateConfirmation($validated);
        }

        $enrollment->setRelation('subjects', $selectedSubjects);

        ActivityLog::record('enrollment_created', $enrollment, [], [
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'student_number' => $enrollment->student_number,
            'course_code' => $enrollment->course_code,
            'year_level' => $enrollment->year_level,
            'semester' => $enrollment->semester,
            'student_type' => $enrollment->student_type,
            'subjects' => $selectedSubjects->pluck('code')->values()->all(),
        ], $request);

        try {
            $pdfContent = $this->fillExistingPDF($enrollment);
        } catch (Throwable) {
            return back()
                ->with('success', 'Enrollment was saved, but the PDF could not be generated. Please ask the admin to check the enrollment form template.')
                ->withInput();
        }

        $filename = 'Enrollment_' . ($enrollment->student_number ?? 'Unknown') . '_' . now()->format('YmdHis') . '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function checkExisting(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'year_level' => 'required|string',
            'semester' => 'required|string',
            'student_type' => 'nullable|in:new,old,transferee',
        ]);

        $validated['school_year'] = AppSetting::getValue('academic_year', '2026-2027');
        $validated['enrollment_identity_hash'] = $this->enrollmentIdentityHash($validated);
        $enrollment = $this->existingEnrollmentQuery($validated)->first();

        return response()->json([
            'exists' => (bool) $enrollment,
            'school_year' => $validated['school_year'],
            'submitted_at' => $enrollment?->created_at?->format('F j, Y'),
        ]);
    }

    public function show(Request $request, Enrollment $enrollment)
    {
        if (! $this->hasAvailablePdfLayout()) {
            abort(404, 'Enrollment form layout is unavailable.');
        }

        $enrollment->load('subjects');

        try {
            $pdfContent = $this->fillExistingPDF($enrollment);
        } catch (Throwable) {
            abort(500, 'Enrollment form could not be generated.');
        }

        ActivityLog::record('enrollment_form_viewed', $enrollment, [], [
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'student_number' => $enrollment->student_number,
            'course_code' => $enrollment->course_code,
            'year_level' => $enrollment->year_level,
            'semester' => $enrollment->semester,
        ], $request);

        $filename = 'Enrollment_' . ($enrollment->student_number ?: $enrollment->id) . '.pdf';

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function preview(Request $request)
    {
        $data = $request->all();
        $data['school_year'] = AppSetting::getValue('academic_year', '2026-2027');
        $data['custom_fields'] = $this->validatedCustomFields('enrollment', $data['custom_fields'] ?? []);
        $data['department_head_name'] = DepartmentHead::where('course_code', $data['course_code'] ?? null)
            ->where('is_active', true)
            ->value('name') ?? $data['department_head_name'] ?? null;

        $selectedSubjectIds = collect($data['subject_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        $data['selected_subjects'] = Subject::whereIn('id', $selectedSubjectIds)
            ->whereIn('type', ['LEC', 'BOTH'])
            ->get(['id', 'code', 'name', 'lecture_units', 'laboratory_units', 'total_units'])
            ->sortBy(fn ($subject) => $selectedSubjectIds->search((int) $subject->id))
            ->values()
            ->map(fn ($subject) => [
                'code' => $subject->code,
                'name' => $subject->name,
                'lecture_units' => (int) $subject->lecture_units,
                'laboratory_units' => (int) $subject->laboratory_units,
                'total_units' => (int) $subject->total_units,
            ])
            ->all();

        try {
            $pdfContent = $this->fillExistingPDF((object)$data);
        } catch (Throwable) {
            return response()->json([
                'message' => 'Enrollment form preview is unavailable.',
            ], 422);
        }

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf');
    }

    private function existingEnrollmentQuery(array $data)
    {
        if (! empty($data['enrollment_identity_hash'])) {
            return Enrollment::query()
                ->where('enrollment_identity_hash', $data['enrollment_identity_hash'])
                ->orWhere(function ($query) use ($data) {
                    $this->applyLegacyEnrollmentIdentityQuery($query, $data);
                });
        }

        return Enrollment::query()
            ->where(function ($query) use ($data) {
                $this->applyLegacyEnrollmentIdentityQuery($query, $data);
            });
    }

    private function backWithDuplicateConfirmation(array $data)
    {
        $existingEnrollment = $this->existingEnrollmentQuery($data)->first();

        return back()
            ->with('duplicate_enrollment', [
                'school_year' => $data['school_year'] ?? AppSetting::getValue('academic_year', '2026-2027'),
                'submitted_at' => $existingEnrollment?->created_at?->format('F j, Y'),
            ])
            ->withInput();
    }

    private function applyLegacyEnrollmentIdentityQuery($query, array $data): void
    {
        $middleName = trim((string) ($data['middle_name'] ?? ''));

        $query->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim((string) ($data['first_name'] ?? '')))])
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim((string) ($data['last_name'] ?? '')))])
            ->whereDate('date_of_birth', $data['date_of_birth'] ?? null)
            ->where('school_year', $data['school_year'] ?? AppSetting::getValue('academic_year', '2026-2027'))
            ->where('year_level', $data['year_level'] ?? null)
            ->where('semester', $data['semester'] ?? null)
            ->when($middleName !== '', function ($query) use ($middleName) {
                $query->whereRaw('LOWER(TRIM(middle_name)) = ?', [strtolower($middleName)]);
            }, function ($query) {
                $query->where(function ($middleQuery) {
                    $middleQuery->whereNull('middle_name')
                        ->orWhereRaw("TRIM(COALESCE(middle_name, '')) = ''");
                });
            });
    }

    private function enrollmentIdentityHash(array $data): ?string
    {
        $normalized = [
            strtolower(trim((string) ($data['first_name'] ?? ''))),
            strtolower(trim((string) ($data['middle_name'] ?? ''))),
            strtolower(trim((string) ($data['last_name'] ?? ''))),
            strtolower(trim((string) ($data['date_of_birth'] ?? ''))),
            strtolower(trim((string) ($data['school_year'] ?? AppSetting::getValue('academic_year', '2026-2027')))),
            strtolower(trim((string) ($data['year_level'] ?? ''))),
            strtolower(trim((string) ($data['semester'] ?? ''))),
        ];

        if (in_array('', [$normalized[0], $normalized[2], $normalized[3], $normalized[4], $normalized[5], $normalized[6]], true)) {
            return null;
        }

        return hash('sha256', implode('|', $normalized));
    }

    private function fillExistingPDF($data)
    {
        $activeTemplate = $this->preferredEnrollmentTemplate();

        if ($activeTemplate && ! empty($activeTemplate->field_mappings)) {
            return $this->fillMappedPDF($data, $activeTemplate);
        }

        $pdf = new Fpdi();

        $templatePath = public_path('templates/enrollment-template.pdf');

        if (!file_exists($templatePath)) {
            abort(500, 'PDF Template not found!');
        }

        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);

        $pdf->AddPage('P', [381, 508]);
        $pdf->useTemplate($templateId);

        $pdf->SetFont('Helvetica', '', 15);
        $pdf->SetTextColor(0, 0, 0);

        // ==================== TEXT FIELDS ====================
        $pdf->SetXY(90, 37.5);   $pdf->Write(0, $data->student_number ?? '');
        $pdf->SetXY(205, 37.5);  $pdf->Write(0, $this->dateOnly($data->date_filed ?? ''));
        $pdf->SetXY(313, 37.5);  $pdf->Write(0, $data->school_year ?? '');

        $pdf->SetXY(98, 45);   $pdf->Write(0, $data->last_name ?? '');
        $pdf->SetXY(185, 45);  $pdf->Write(0, $data->first_name ?? '');
        $pdf->SetXY(289, 45);  $pdf->Write(0, $data->middle_name ?? '');

        $pdf->SetXY(90, 59);   $pdf->Write(0, $data->cellphone ?? '');
        $pdf->SetXY(193, 59);  $pdf->Write(0, $data->email ?? '');
        $pdf->SetXY(105, 112);  $pdf->Write(0, $data->last_school ?? '');

        $address = $data->present_address ?? '';
        $maxWidth = 107;
        $initialFontSize = 15;
        $minFontSize = 6;
        $currentFontSize = $initialFontSize;
        $pdf->SetFont('Helvetica', '', $currentFontSize);
        while ($pdf->GetStringWidth($address) > $maxWidth && $currentFontSize > $minFontSize) {
            $currentFontSize -= 0.5;
            $pdf->SetFont('Helvetica', '', $currentFontSize);
        }
        $pdf->SetXY(84, 284);
        $pdf->Cell($maxWidth, 5, $address, 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', $initialFontSize);

        $pdf->SetXY(224, 284); $pdf->Write(0, $this->printableAddressValue($data->barangay ?? ''));
        $pdf->SetXY(70, 291.5); $pdf->Write(0, $this->printableAddressValue($data->city ?? ''));
        $pdf->SetXY(224, 291.5); $pdf->Write(0, $this->printableAddressValue($data->province ?? ''));

        $pdf->SetXY(77, 299); $pdf->Write(0, $this->dateOnly($data->date_of_birth ?? ''));
        $pdf->SetXY(200, 299); $pdf->Write(0, $data->age ?? '');
        $pdf->SetXY(261, 299); $pdf->Write(0, $data->civil_status ?? '');

        $place_of_birth = $data->place_of_birth ?? '';
        $maxWidth = 99; 
        $initialFontSize = 15; 
        $minFontSize = 6;      
        $currentFontSize = $initialFontSize;
        $pdf->SetFont('Helvetica', '', $currentFontSize);
        while ($pdf->GetStringWidth($place_of_birth) > $maxWidth && $currentFontSize > $minFontSize) {
            $currentFontSize -= 0.5;
            $pdf->SetFont('Helvetica', '', $currentFontSize);
        }
        $pdf->SetXY(77, 307);
        $pdf->Cell($maxWidth, 5, $place_of_birth, 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', $initialFontSize);




        $pdf->SetXY(203, 307); $pdf->Write(0, $data->gender ?? '');
        $pdf->SetXY(255, 307); $pdf->Write(0, $data->religion ?? '');

        $pdf->SetXY(80, 314); $pdf->Write(0, $data->father_name ?? '');
        $pdf->SetXY(203, 314); $pdf->Write(0, $data->father_address ?? '');
        $pdf->SetXY(300, 314); $pdf->Write(0, $data->father_cpNumber ?? '');

        $pdf->SetXY(102, 322); $pdf->Write(0, $data->mother_name ?? '');
        $pdf->SetXY(203, 322); $pdf->Write(0, $data->mother_address ?? '');
        $pdf->SetXY(300, 322); $pdf->Write(0, $data->mother_cpNumber ?? '');

        $this->checkCourseBox($pdf, $data->course_code ?? '');
        $this->checkYearLevelBox($pdf, $data->year_level ?? '');
        $this->checkSemesterBox($pdf, $data->semester ?? '');

        $this->checkCredentialBoxes($pdf, $data->credentials ?? []);

        if (! empty($data->department_head_name)) {
            $pdf->SetFont('Helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(235, 334);
            $pdf->Write(0, 'Dept. Head: ' . $data->department_head_name);
        }

        return $pdf->Output('', 'S');
    }

    private function hasAvailablePdfLayout(): bool
    {
        $activeTemplate = $this->preferredEnrollmentTemplate();

        if (
            $activeTemplate &&
            ! empty($activeTemplate->field_mappings) &&
            $this->templateAbsolutePath($activeTemplate->file_path)
        ) {
            return true;
        }

        return file_exists(public_path('templates/enrollment-template.pdf'));
    }

    private function fillMappedPDF($data, EnrollmentTemplate $template)
    {
        $templatePath = $this->templateAbsolutePath($template->file_path);

        if (! $templatePath) {
            abort(500, 'Mapped PDF template file not found.');
        }

        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false, 0);
        $pageCount = $pdf->setSourceFile($templatePath);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }

        $pdf->SetTextColor(0, 0, 0);

        foreach ($template->field_mappings ?? [] as $mapping) {
            $key = $mapping['key'] ?? null;
            $type = $mapping['type'] ?? 'text';
            $x = (float) ($mapping['x'] ?? 0);
            $y = (float) ($mapping['y'] ?? 0);
            $fontSize = (float) ($mapping['font_size'] ?? ($type === 'check' ? 14 : 10));
            $page = max(1, min((int) ($mapping['page'] ?? 1), $pageCount));

            if (! $key) {
                continue;
            }

            if ($type === 'check') {
                if (! $this->mappedCheckIsSelected($key, $data)) {
                    continue;
                }

                $pdf->SetPage($page);


                $checkFontSize = $fontSize * 1.35;
                $pdf->SetFont('dejavusans', 'B', $checkFontSize);
                $pdf->SetTextColor(0, 100, 0);
                $pdf->SetXY($x - 1.4, $y - 2.4);
                $pdf->Write(0, html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8'));
                $pdf->SetTextColor(0, 0, 0);
                continue;
            }

            $value = $this->mappedFieldValue($key, $data);

            if ($value === '') {
                continue;
            }

            $pdf->SetPage($page);


            $pdf->SetFont('Helvetica', '', $fontSize);
            $pdf->SetXY($x, $y);
            $pdf->Write(0, $value);
        }

        return $pdf->Output('', 'S');
    }

    private function preferredEnrollmentTemplate(): ?EnrollmentTemplate
    {
        return EnrollmentTemplate::where('is_active', true)
            ->where('file_path', 'not like', 'templates/%')
            ->latest()
            ->first()
            ?? EnrollmentTemplate::where('file_path', 'like', 'templates/%')
                ->latest()
                ->first();
    }

    private function templateAbsolutePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'templates/')) {
            $publicPath = public_path($path);

            return file_exists($publicPath) ? $publicPath : null;
        }

        return Storage::disk('public')->exists($path)
            ? Storage::disk('public')->path($path)
            : null;
    }

    private function mappedFieldValue(string $key, $data): string
    {
        if (preg_match('/^subject_(code|name|units)_(\d+)$/', $key, $matches)) {
            return $this->mappedSubjectValue($matches[1], (int) $matches[2], $data);
        }

        if ($key === 'total_units') {
            return $this->mappedTotalUnitsValue($data);
        }

        if (in_array($key, $this->mappedFeeKeys(), true)) {
            return $this->mappedFeeValue($key, $data);
        }

        $value = data_get($data, $key, '');

        if ($value === '' && str_starts_with($key, 'custom_')) {
            $value = data_get($data, "custom_fields.{$key}", '');
        }

        if (in_array($key, ['date_filed', 'date_of_birth'], true)) {
            return $this->dateOnly($value);
        }

        if (in_array($key, ['province', 'city', 'barangay'], true)) {
            return $this->printableAddressValue($value);
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        return (string) ($value ?? '');
    }

    private function printableAddressValue($value): string
    {
        $value = trim((string) ($value ?? ''));

        return strtolower($value) === 'other / not listed' ? '' : $value;
    }

    private function dateOnly($value): string
    {
        if (! $value) {
            return '';
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', (string) $value) ?? (string) $value;
    }

    private function mappedSubjectValue(string $field, int $position, $data): string
    {
        $subject = $this->mappedSubjects($data)->get($position - 1);

        if (! $subject) {
            return '';
        }

        return match ($field) {
            'code' => (string) data_get($subject, 'code', ''),
            'name' => (string) data_get($subject, 'name', ''),
            'units' => (string) ((int) data_get($subject, 'total_units', 0)),
            default => '',
        };
    }

    private function mappedTotalUnitsValue($data): string
    {
        $totalUnits = $this->mappedSubjects($data)
            ->sum(fn ($subject) => (int) data_get($subject, 'total_units', 0));

        return $totalUnits > 0 ? (string) $totalUnits : '';
    }
    private function mappedFeeKeys(): array
    {
        return [
            'tuition_fee',
            'nstp_fee',
            'subtotal_tuition_fee',
            'misc_fees',
            'hands_on_fee',
            'lab_fee',
            'total_tuition_fee',
            'total_account',
        ];
    }

    private function mappedFeeValue(string $key, $data): string
    {
        $fees = $this->calculatedFees($data);

        return number_format($fees[$key] ?? 0, 2, '.', ',');
    }

    private function calculatedFees($data): array
    {
        $courseCode = (string) data_get($data, 'course_code', '');
        $subjects = $this->mappedSubjects($data);
        $configuredFees = FeeConfiguration::where('is_active', true)
            ->where('course_code', $courseCode)
            ->whereIn('fee_type', ['tuition_per_unit', 'misc_fee', 'hands_on_fee', 'lab_fee', 'nstp_fee'])
            ->get()
            ->keyBy('fee_type');

        $tuitionRate = (float) optional($configuredFees->get('tuition_per_unit'))->amount;
        $labRate = (float) optional($configuredFees->get('lab_fee'))->amount;
        $miscFees = (float) optional($configuredFees->get('misc_fee'))->amount;
        $nstpRate = (float) optional($configuredFees->get('nstp_fee'))->amount;
        $handsOnRate = (float) optional($configuredFees->get('hands_on_fee'))->amount;

        $totalUnits = $subjects->sum(fn ($subject) => (int) data_get($subject, 'total_units', 0));
        $labUnits = $subjects->sum(fn ($subject) => (int) data_get($subject, 'laboratory_units', 0));
        $tuitionFee = round($totalUnits * $tuitionRate, 2);
        $labFee = round($labUnits * $labRate, 2);
        $handsOnFee = $labUnits > 0 ? $handsOnRate : 0;
        $hasNstpSubject = $subjects->contains(fn ($subject) => str_contains(strtoupper((string) data_get($subject, 'code', '') . ' ' . data_get($subject, 'name', '')), 'NSTP'));
        $nstpFee = $hasNstpSubject ? $nstpRate : 0;
        $subtotalTuitionFee = $tuitionFee;
        $totalTuitionFee = $subtotalTuitionFee + $miscFees + $handsOnFee + $labFee + $nstpFee;
        $totalAccount = round($totalTuitionFee);

        return [
            'tuition_fee' => $tuitionFee,
            'nstp_fee' => $nstpFee,
            'subtotal_tuition_fee' => $subtotalTuitionFee,
            'misc_fees' => $miscFees,
            'hands_on_fee' => $handsOnFee,
            'lab_fee' => $labFee,
            'total_tuition_fee' => $totalTuitionFee,
            'total_account' => $totalAccount,
        ];
    }

    private function mappedSubjects($data)
    {
        $subjects = data_get($data, 'selected_subjects');

        if (empty($subjects) && $data instanceof Enrollment && $data->relationLoaded('subjects')) {
            $subjects = $data->subjects;
        }

        return collect($subjects ?? [])->values();
    }

    private function customTemplateFields(string $scope)
    {
        return CustomTemplateField::where('scope', $scope)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();
    }

    private function validatedCustomFields(string $scope, array $values): array
    {
        return $this->customTemplateFields($scope)
            ->mapWithKeys(function (CustomTemplateField $field) use ($values) {
                $value = trim((string) ($values[$field->key] ?? ''));

                return [$field->key => $value];
            })
            ->filter(fn ($value) => $value !== '')
            ->all();
    }

    private function ensureRequiredCustomFields(string $scope, array $values): void
    {
        $missing = $this->customTemplateFields($scope)
            ->filter(fn (CustomTemplateField $field) => $field->is_required && trim((string) ($values[$field->key] ?? '')) === '')
            ->mapWithKeys(fn (CustomTemplateField $field) => ["custom_fields.{$field->key}" => "{$field->label} is required."])
            ->all();

        if ($missing) {
            throw ValidationException::withMessages($missing);
        }
    }

    private function mappedCheckIsSelected(string $key, $data): bool
    {
        if (str_starts_with($key, 'course_')) {
            return strtoupper((string) data_get($data, 'course_code')) === substr($key, 7);
        }

        if (str_starts_with($key, 'year_')) {
            return (string) data_get($data, 'year_level') === substr($key, 5);
        }

        if (str_starts_with($key, 'semester_')) {
            return strtolower((string) data_get($data, 'semester')) === strtolower(substr($key, 9));
        }

        if (str_starts_with($key, 'credential_')) {
            $credentials = data_get($data, 'credentials', []);

            return is_array($credentials) && in_array(substr($key, 11), $credentials, true);
        }

        return filled(data_get($data, $key));
    }

    private function checkCourseBox($pdf, $courseCode)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (strtoupper(trim($courseCode))) {
            case 'BSIT':  $pdf->SetXY(77, 71);  $pdf->Write(0, $check); break;
            case 'BSCS':  $pdf->SetXY(158, 71); $pdf->Write(0, $check); break;
            case 'ACT':   $pdf->SetXY(225, 71); $pdf->Write(0, $check); break;
            case 'BSHM':  $pdf->SetXY(77, 86);  $pdf->Write(0, $check); break;
            case 'BSOM':  $pdf->SetXY(205, 86); $pdf->Write(0, $check); break;
            case 'BSA':   $pdf->SetXY(285, 86); $pdf->Write(0, $check); break;
        }
    }

    private function checkYearLevelBox($pdf, $yearLevel)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (trim($yearLevel)) {
            case '1': $pdf->SetXY(131, 99); $pdf->Write(0, $check); break;
            case '2': $pdf->SetXY(152, 99); $pdf->Write(0, $check); break; 
            case '3': $pdf->SetXY(173, 99); $pdf->Write(0, $check); break; 
            case '4': $pdf->SetXY(194, 99); $pdf->Write(0, $check); break; 
        }
    }

    private function checkSemesterBox($pdf, $semester)
    {
        $pdf->SetFont('dejavusans', 'B', 32);
        $pdf->SetTextColor(0, 100, 0);
        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        switch (strtolower(trim($semester))) {
            case '1st':    $pdf->SetXY(262, 99); $pdf->Write(0, $check); break;
            case '2nd':    $pdf->SetXY(287.5, 99); $pdf->Write(0, $check); break;
            case 'summer': $pdf->SetXY(325, 99); $pdf->Write(0, $check); break;
        }
    }
    private function checkCredentialBoxes($pdf, $credentials)
    {
        $pdf->SetFont('dejavusans', 'B', 28);
        $pdf->SetTextColor(0, 100, 0);

        $check = html_entity_decode('&#10003;', ENT_QUOTES, 'UTF-8');

        if (!is_array($credentials)) {
            return;
        }

        $positions = [

            'form_138' => [54, 126.5],
            'birth_certificate' => [54, 138],
            'good_moral' => [54, 149],

            'certificate_grades' => [133.5, 126.5],
            'certificate_eligibility' => [133.5, 138],
            'transcript' => [133.5, 149],

            'long_folder' => [266.5, 126.5],
            'picture' => [266.5, 138],
        ];

        foreach ($credentials as $credential) {

            if (isset($positions[$credential])) {

                [$x, $y] = $positions[$credential];

                $pdf->SetXY($x, $y);
                $pdf->Write(0, $check);
            }
        }
    }
}
