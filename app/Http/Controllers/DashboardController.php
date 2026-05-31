<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\AppSetting;
use App\Models\CustomTemplateField;
use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\EnrollmentTemplate;
use App\Models\FeeConfiguration;
use App\Models\IdTemplate;
use App\Models\Room;
use App\Models\StudentId;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $accountUsers = User::orderByRaw("FIELD(user_type, 'admin', 'registrar', 'department_head')")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'user_type']);
        $activityLogs = $this->activityLogPayloads();

        // Stat cards
        $stats = $this->dashboardStats();

        // Course leaderboard
        $courseStats = DB::table('enrollments')
            ->select('course_code', DB::raw('COUNT(*) as total'))
            ->whereNotNull('course_code')
            ->whereNull('archived_at')
            ->where('school_year', $academicYear)
            ->groupBy('course_code')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        // Chart: per semester
        $semRows = DB::table('enrollments')
            ->select('school_year', 'semester',
                DB::raw("SUM(enrollment_status = 'enrolled') as enrolled"),
                DB::raw("SUM(enrollment_status = 'pending')  as pending"))
            ->whereNotNull('semester')
            ->whereNull('archived_at')
            ->where('school_year', $academicYear)
            ->groupBy('school_year', 'semester')
            ->orderBy('school_year')
            ->orderByRaw("FIELD(semester, '1st', '2nd', 'Summer')")
            ->get();

        $semLabels  = $semRows->map(fn($r) => $r->school_year . ' ' . $r->semester)->toArray();
        $semEnrolled = $semRows->pluck('enrolled')->map(fn($v) => (int) $v)->toArray();
        $semPending  = $semRows->pluck('pending')->map(fn($v) => (int) $v)->toArray();

        // Chart: per school year
        $yearRows = DB::table('enrollments')
            ->select('school_year',
                DB::raw("SUM(enrollment_status = 'enrolled') as enrolled"),
                DB::raw("SUM(enrollment_status = 'pending')  as pending"))
            ->whereNotNull('school_year')
            ->whereNull('archived_at')
            ->where('school_year', $academicYear)
            ->groupBy('school_year')
            ->orderBy('school_year')
            ->get();

        $yearLabels  = $yearRows->pluck('school_year')->toArray();
        $yearEnrolled = $yearRows->pluck('enrolled')->map(fn($v) => (int) $v)->toArray();
        $yearPending  = $yearRows->pluck('pending')->map(fn($v) => (int) $v)->toArray();

        // Fallback placeholders when DB is empty
        if (empty($semLabels)) {
            $semLabels   = ['1st Sem', '2nd Sem', 'Summer'];
            $semEnrolled = [0, 0, 0];
            $semPending  = [0, 0, 0];
        }
        if (empty($yearLabels)) {
            $yearLabels   = ['2026-2027'];
            $yearEnrolled = [0];
            $yearPending  = [0];
        }

        $chartData = [
            'semester' => ['labels' => $semLabels,  'enrolled' => $semEnrolled,  'pending' => $semPending],
            'year'     => ['labels' => $yearLabels, 'enrolled' => $yearEnrolled, 'pending' => $yearPending],
        ];

        // Recent enrollments
        $recentEnrollments = $this->activeEnrollmentQuery($academicYear)->orderByDesc('created_at')->limit(8)->get();
        $allEnrollments = $this->activeEnrollmentQuery($academicYear)->with('studentId')->orderByDesc('created_at')->get();
        $archivedEnrollmentYears = Enrollment::whereNotNull('archived_at')
            ->whereNotNull('archived_school_year')
            ->distinct()
            ->orderByDesc('archived_school_year')
            ->pluck('archived_school_year')
            ->values();
        $idGenerationStatuses = $allEnrollments->mapWithKeys(fn (Enrollment $enrollment) => [
            $enrollment->id => $this->idGenerationStatusPayload($enrollment),
        ]);
        $subjects = Subject::with(['schedules.day', 'schedules.timeSlot', 'schedules.room'])
            ->orderBy('course_code')
            ->orderBy('year_level')
            ->orderBy('semester')
            ->orderBy('code')
            ->get();
        $days = Day::orderBy('sort_order')->orderBy('name')->get();
        $rooms = Room::orderBy('name')->get();
        $timeSlots = TimeSlot::orderBy('start_time')->get();
        $subjectSchedules = $this->activeScheduleQuery($academicYear)
            ->with(['subject', 'day', 'timeSlot', 'room'])
            ->latest()
            ->get();
        $currentScheduleRooms = $subjectSchedules
            ->pluck('room')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
        $scheduleInstructorOptions = $subjectSchedules
            ->pluck('instructor')
            ->map(fn ($instructor) => trim((string) $instructor))
            ->filter()
            ->unique(fn ($instructor) => strtolower($instructor))
            ->sort()
            ->values();
        $scheduleForOptions = $subjectSchedules
            ->pluck('schedule_for')
            ->map(fn ($scheduleFor) => trim((string) $scheduleFor))
            ->filter()
            ->unique(fn ($scheduleFor) => strtolower($scheduleFor))
            ->sort()
            ->values();
        $archivedScheduleYears = SubjectSchedule::whereNotNull('archived_at')
            ->whereNotNull('archived_school_year')
            ->distinct()
            ->orderByDesc('archived_school_year')
            ->pluck('archived_school_year')
            ->values();
        $archivedScheduleRows = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->whereNotNull('archived_at')
            ->latest()
            ->get()
            ->map(fn (SubjectSchedule $schedule) => $this->schedulePayload($schedule))
            ->values();
        $scheduleRows = $subjectSchedules->map(fn (SubjectSchedule $schedule) => [
            'id' => $schedule->id,
            'subject' => [
                'id' => $schedule->subject?->id,
                'code' => $schedule->subject?->code,
                'name' => $schedule->subject?->name,
                'course_code' => $schedule->subject?->course_code,
                'year_level' => $schedule->subject?->year_level,
                'semester' => $schedule->subject?->semester,
                'type' => $schedule->subject?->type,
            ],
            'day_id' => $schedule->day_id,
            'day' => $schedule->day?->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'start_time' => $schedule->timeSlot?->start_time ? substr((string) $schedule->timeSlot->start_time, 0, 5) : null,
            'end_time' => $schedule->timeSlot?->end_time ? substr((string) $schedule->timeSlot->end_time, 0, 5) : null,
            'room_id' => $schedule->room_id,
            'room' => $schedule->room?->name,
            'instructor' => $schedule->instructor ?: 'Unassigned',
            'schedule_type' => $schedule->schedule_type ?: 'LEC',
            'schedule_for' => $schedule->schedule_for ?: 'Whole Class',
            'school_year' => $schedule->school_year,
            'archived_school_year' => $schedule->archived_school_year,
            'archived_at' => $schedule->archived_at?->toDateTimeString(),
            'subject_display_name' => $this->scheduleSubjectName($schedule),
            'update_url' => route('academic.schedules.update', $schedule),
            'delete_url' => route('academic.schedules.destroy', $schedule),
        ])->values();
        $scheduleSubjectOptions = $subjects->flatMap(function (Subject $subject) {
            $types = $subject->type === 'BOTH'
                ? ['LEC', 'LAB']
                : [$subject->type === 'LAB' ? 'LAB' : 'LEC'];

            return collect($types)->map(fn (string $type) => [
                'id' => $subject->id,
                'schedule_type' => $type,
                'label' => "{$subject->code} - {$type} / {$subject->name} / {$subject->course_code} / {$subject->year_level} / {$subject->semester}",
            ]);
        })->values();
        $departmentHeads = DepartmentHead::where('is_active', true)
            ->orderBy('course_code')
            ->get();
        $feeTypes = [
            'tuition_per_unit' => 'Tuition / Unit',
            'misc_fee' => 'Misc. Fees',
            'hands_on_fee' => 'Hands-on Fee',
            'lab_fee' => 'Lab Fee / Unit',
            'nstp_fee' => 'NSTP Fee',
        ];
        $feeConfigurations = FeeConfiguration::where('is_active', true)
            ->whereNotNull('course_code')
            ->whereNotNull('fee_type')
            ->get()
            ->groupBy('course_code');
        $courseOrder = collect(['BSIT', 'BSCS', 'ACT', 'BSA', 'BSBA', 'BSOM']);
        $feeCourses = $courseOrder
            ->merge($subjects->pluck('course_code'))
            ->merge($feeConfigurations->keys())
            ->filter()
            ->unique()
            ->sortBy(fn ($courseCode) => ($index = $courseOrder->search($courseCode)) === false ? 999 : $index)
            ->values();
        $feeRows = $feeCourses->map(function ($courseCode) use ($feeConfigurations, $feeTypes) {
            $courseFees = $feeConfigurations->get($courseCode, collect())->keyBy('fee_type');

            return [
                'course_code' => $courseCode,
                'fees' => collect($feeTypes)->mapWithKeys(fn ($label, $type) => [
                    $type => (float) optional($courseFees->get($type))->amount,
                ])->all(),
            ];
        });
        $activeEnrollmentTemplate = $this->preferredEnrollmentTemplate();
        $enrollmentTemplatePayload = $activeEnrollmentTemplate ? [
            'id' => $activeEnrollmentTemplate->id,
            'name' => $activeEnrollmentTemplate->name,
            'original_filename' => $activeEnrollmentTemplate->original_filename,
            'page_width' => (float) $activeEnrollmentTemplate->page_width,
            'page_height' => (float) $activeEnrollmentTemplate->page_height,
            'field_mappings' => $activeEnrollmentTemplate->field_mappings ?? [],
            'pdf_url' => route('academic.templates.pdf', $activeEnrollmentTemplate),
            'save_url' => route('academic.templates.mappings.update', $activeEnrollmentTemplate),
        ] : null;
        $idTemplates = collect(['front', 'back'])->mapWithKeys(fn (string $side) => [
            $side => $this->preferredIdTemplate($side),
        ]);
        $idTemplatePayloads = collect(['front', 'back'])->mapWithKeys(function ($side) use ($idTemplates) {
            $template = $idTemplates->get($side);

            return [$side => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'side' => $template->side,
                'school_year' => $template->school_year,
                'background_url' => route('academic.id-templates.background', $template),
                'save_url' => route('academic.id-templates.layout.update', $template),
                'width' => (float) ($template->layout_config['width'] ?? 540),
                'height' => (float) ($template->layout_config['height'] ?? 340),
                'fields' => $template->layout_config['fields'] ?? [],
            ] : null];
        })->all();
        $idTemplatePayload = $idTemplatePayloads['front'] ?? $idTemplatePayloads['back'] ?? null;
        $idFonts = $this->idFontPayloads();
        $customEnrollmentFields = $this->customTemplateFields('enrollment');
        $customIdFields = $this->customTemplateFields('id');

        return view('dashboard.index', compact(
            'stats',
            'courseStats',
            'chartData',
            'recentEnrollments',
            'allEnrollments',
            'archivedEnrollmentYears',
            'idGenerationStatuses',
            'subjects',
            'days',
            'rooms',
            'currentScheduleRooms',
            'timeSlots',
            'scheduleInstructorOptions',
            'scheduleForOptions',
            'subjectSchedules',
            'scheduleRows',
            'archivedScheduleRows',
            'archivedScheduleYears',
            'scheduleSubjectOptions',
            'departmentHeads',
            'feeRows',
            'feeTypes',
            'activeEnrollmentTemplate',
            'enrollmentTemplatePayload',
            'idTemplatePayloads',
            'idTemplatePayload',
            'idFonts',
            'customEnrollmentFields',
            'customIdFields',
            'academicYear',
            'accountUsers',
            'activityLogs'
        ));
    }

    public function activityLogs(): JsonResponse
    {
        return response()->json([
            'logs' => $this->activityLogPayloads(),
        ]);
    }

    public function liveEnrollments(): JsonResponse
    {
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');
        $archiveYear = request('archived_year');
        $recentEnrollments = $this->activeEnrollmentQuery($academicYear)->orderByDesc('created_at')->limit(8)->get();
        $allEnrollments = $archiveYear
            ? Enrollment::with('studentId')
                ->whereNotNull('archived_at')
                ->where('archived_school_year', $archiveYear)
                ->orderByDesc('created_at')
                ->get()
            : $this->activeEnrollmentQuery($academicYear)->with('studentId')->orderByDesc('created_at')->get();

        return response()->json([
            'recent_html' => view('dashboard.partials.enrollment-table', [
                'enrollments' => $recentEnrollments,
                'compact' => true,
            ])->render(),
            'all_html' => view('dashboard.partials.enrollment-table', [
                'enrollments' => $allEnrollments,
                'compact' => false,
            ])->render(),
            'total' => $allEnrollments->count(),
            'statuses' => $allEnrollments->pluck('enrollment_status', 'id'),
            'stats' => $this->dashboardStats(),
        ]);
    }

    private function dashboardStats(): array
    {
        $academicYear = AppSetting::getValue('academic_year', '2026-2027');

        return [
            'total_enrolled' => $this->activeEnrollmentQuery($academicYear)->where('enrollment_status', 'enrolled')->count(),
            'pending'        => $this->activeEnrollmentQuery($academicYear)->where('enrollment_status', 'pending')->count(),
            'enrolled_today' => $this->activeEnrollmentQuery($academicYear)->where('enrollment_status', 'enrolled')
                ->whereDate('updated_at', today())
                ->count(),
            'courses'        => DB::table('enrollments')
                ->whereNull('archived_at')
                ->where('school_year', $academicYear)
                ->distinct()
                ->count('course_code'),
            'subjects'       => DB::table('subjects')->where('is_active', true)->count(),
        ];
    }

    private function activeEnrollmentQuery(string $academicYear)
    {
        return Enrollment::query()
            ->whereNull('archived_at')
            ->where('school_year', $academicYear);
    }

    private function activeScheduleQuery(string $academicYear)
    {
        return SubjectSchedule::query()
            ->whereNull('archived_at')
            ->where(fn ($query) => $query
                ->where('school_year', $academicYear)
                ->orWhereNull('school_year'));
    }

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): RedirectResponse|JsonResponse
    {
        abort_if($enrollment->archived_at, 403);

        $validated = $request->validate([
            'enrollment_status' => ['required', 'in:pending,enrolled,cancelled'],
        ]);

        $oldStatus = $enrollment->enrollment_status;
        $enrollment->update($validated);

        ActivityLog::record('enrollment_status_updated', $enrollment, [
            'enrollment_status' => $oldStatus,
        ], [
            'enrollment_status' => $enrollment->enrollment_status,
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
        ], $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Enrollment status updated.',
                'status' => $enrollment->enrollment_status,
            ]);
        }

        return back()->with('success', 'Enrollment status updated.');
    }

    public function updateEnrollment(Request $request, Enrollment $enrollment): RedirectResponse|JsonResponse
    {
        abort_if($enrollment->archived_at, 403);

        $validated = $request->validate([
            'student_number' => ['nullable', 'string', 'max:50'],
            'date_filed' => ['nullable', 'date_format:Y-m-d'],
            'school_year' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'cellphone' => ['nullable', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'email', 'max:100'],
            'last_school' => ['nullable', 'string', 'max:150'],
            'present_address' => ['nullable', 'string'],
            'barangay' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'age' => ['nullable', 'integer', 'min:1'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:50'],
            'religion' => ['nullable', 'string', 'max:100'],
            'father_name' => ['nullable', 'string', 'max:255'],
            'father_address' => ['nullable', 'string'],
            'father_cpNumber' => ['nullable', 'regex:/^09\d{9}$/'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_address' => ['nullable', 'string'],
            'mother_cpNumber' => ['nullable', 'regex:/^09\d{9}$/'],
            'course_code' => ['required', 'string', 'max:50'],
            'course_name' => ['required', 'string', 'max:255'],
            'year_level' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'string', 'max:20'],
            'department_head_name' => ['nullable', 'string', 'max:120'],
            'credentials' => ['nullable', 'array'],
            'credentials.*' => ['string'],
        ], [
            'cellphone.regex' => 'Enter a valid 11-digit cellphone number starting with 09.',
            'father_cpNumber.regex' => 'Enter a valid father cellphone number starting with 09.',
            'mother_cpNumber.regex' => 'Enter a valid mother cellphone number starting with 09.',
        ]);

        $validated['credentials'] = array_values($validated['credentials'] ?? []);
        $validated['enrollment_identity_hash'] = $this->enrollmentIdentityHash($validated);
        $oldValues = $enrollment->only(array_keys($validated));

        if (
            $validated['enrollment_identity_hash']
            && Enrollment::where('enrollment_identity_hash', $validated['enrollment_identity_hash'])
                ->whereKeyNot($enrollment->id)
                ->exists()
        ) {
            $message = 'Another enrollment already uses this student identity, school year, year level, and semester.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['enrollment' => $message])->withInput();
        }

        try {
            $enrollment->update($validated);
        } catch (UniqueConstraintViolationException) {
            $message = 'Another enrollment already uses this student identity, school year, year level, and semester.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['enrollment' => $message])->withInput();
        }

        ActivityLog::record('enrollment_updated', $enrollment, $oldValues, $enrollment->only(array_keys($validated)), $request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Enrollment updated.',
                'enrollment' => $this->enrollmentEditPayload($enrollment->fresh()),
                'stats' => $this->dashboardStats(),
            ]);
        }

        return back()->with('success', 'Enrollment updated.');
    }

    public function idCardData(Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true), 403);

        if ($enrollment->enrollment_status !== 'enrolled') {
            return response()->json([
                'message' => 'Only enrolled students can have an ID generated.',
            ], 422);
        }

        $studentId = StudentId::where('enrollment_id', $enrollment->id)->first();

        if (! $studentId || ! $studentId->emergency_contact_name || ! $studentId->emergency_contact_relationship || ! $studentId->emergency_contact_number) {
            return response()->json([
                'message' => 'Emergency contact details are required before generating this ID.',
            ], 422);
        }

        if (! $studentId || ! $studentId->photo_path || ! Storage::disk('public')->exists($studentId->photo_path)) {
            return response()->json([
                'message' => 'Student photo is missing. Capture or upload a student photo before generating this ID.',
            ], 422);
        }

        $templates = collect(['front', 'back'])
            ->map(fn (string $side) => $this->preferredIdTemplate($side))
            ->filter()
            ->values()
            ->map(fn (IdTemplate $template) => $this->idTemplateGenerationPayload($template))
            ->filter(fn (?array $template) => $template && count($template['fields']) > 0)
            ->sortBy(fn (array $template) => $template['side'] === 'front' ? 0 : 1)
            ->values();

        if ($templates->isEmpty()) {
            return response()->json([
                'message' => 'No mapped ID template is available. Please map a front or back ID template first.',
            ], 422);
        }

        return response()->json([
            'student' => [
                'id' => $enrollment->id,
                'file_name' => str($enrollment->last_name . '-' . $enrollment->first_name . '-id')
                    ->slug()
                    ->append('.jpg')
                    ->toString(),
                'fields' => $this->idStudentFields($enrollment),
                'photo' => $this->storageDataUrl($studentId->photo_path),
                'signature' => $studentId->signature_path && Storage::disk('public')->exists($studentId->signature_path)
                    ? $this->storageDataUrl($studentId->signature_path)
                    : null,
                'images' => $this->idCustomImages($studentId),
            ],
            'templates' => $templates,
        ]);
    }

    public function idGenerationStatuses(): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true), 403);

        $statuses = $this->activeEnrollmentQuery(AppSetting::getValue('academic_year', '2026-2027'))
            ->with('studentId')
            ->where('enrollment_status', 'enrolled')
            ->get()
            ->mapWithKeys(fn (Enrollment $enrollment) => [
                $enrollment->id => $this->idGenerationStatusPayload($enrollment),
            ]);

        return response()->json([
            'statuses' => $statuses,
        ]);
    }

    public function markIdGenerated(Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true), 403);

        if ($enrollment->enrollment_status !== 'enrolled') {
            return response()->json([
                'message' => 'Only enrolled students can have an ID generated.',
            ], 422);
        }

        $studentId = $this->studentIdForEnrollment($enrollment);
        $studentId->fill([
            'school_year' => $enrollment->school_year,
            'status' => 'generated',
            'generated_at' => now(),
        ])->save();

        ActivityLog::record('student_id_generated', $studentId, [], [
            'enrollment_id' => $enrollment->id,
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'status' => 'generated',
        ], request());

        $enrollment->load('studentId');

        return response()->json([
            'message' => 'ID generation status updated.',
            'status' => $this->idGenerationStatusPayload($enrollment),
        ]);
    }

    public function uploadIdPhoto(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true), 403);

        if ($enrollment->enrollment_status !== 'enrolled') {
            return response()->json([
                'message' => 'Only enrolled students can have an ID photo uploaded.',
            ], 422);
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Choose a student photo to upload.',
            'photo.image' => 'The student photo must be a valid image file.',
        ]);

        $studentId = $this->studentIdForEnrollment($enrollment);
        $oldValues = ['photo_path' => $studentId->photo_path];

        if ($studentId->photo_path) {
            Storage::disk('public')->delete($studentId->photo_path);
        }

        $photo = $validated['photo'];
        $studentId->fill([
            'school_year' => $enrollment->school_year,
            'photo_path' => $photo->store("student-id-requirements/{$enrollment->id}", 'public'),
            'photo_mime_type' => $photo->getMimeType(),
            'requirements_status' => $studentId->requirements_status ?: 'pending',
            'status' => $studentId->status ?: 'draft',
            'submitted_at' => $studentId->submitted_at ?: now(),
        ])->save();

        ActivityLog::record('student_photo_uploaded', $studentId, $oldValues, [
            'enrollment_id' => $enrollment->id,
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'photo_path' => $studentId->photo_path,
        ], $request);

        $enrollment->load('studentId');

        return response()->json([
            'message' => 'Student photo uploaded.',
            'status' => $this->idGenerationStatusPayload($enrollment),
        ]);
    }

    public function uploadIdSignature(Request $request, Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true), 403);

        if ($enrollment->enrollment_status !== 'enrolled') {
            return response()->json([
                'message' => 'Only enrolled students can have an ID signature uploaded.',
            ], 422);
        }

        $validated = $request->validate([
            'signature' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'signature.required' => 'Choose a signature image to upload.',
            'signature.image' => 'The signature must be a valid image file.',
        ]);

        $studentId = $this->studentIdForEnrollment($enrollment);
        $oldValues = ['signature_path' => $studentId->signature_path];

        if ($studentId->signature_path) {
            Storage::disk('public')->delete($studentId->signature_path);
        }

        $signature = $validated['signature'];
        $studentId->fill([
            'school_year' => $enrollment->school_year,
            'signature_path' => $signature->store("student-id-requirements/{$enrollment->id}", 'public'),
            'signature_mime_type' => $signature->getMimeType(),
            'requirements_status' => $studentId->requirements_status ?: 'pending',
            'status' => $studentId->status ?: 'draft',
            'submitted_at' => $studentId->submitted_at ?: now(),
        ])->save();

        ActivityLog::record('student_signature_uploaded', $studentId, $oldValues, [
            'enrollment_id' => $enrollment->id,
            'student' => trim($enrollment->last_name . ', ' . $enrollment->first_name),
            'signature_path' => $studentId->signature_path,
        ], $request);

        $enrollment->load('studentId');

        return response()->json([
            'message' => 'Signature uploaded.',
            'status' => $this->idGenerationStatusPayload($enrollment),
        ]);
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

    private function idGenerationStatusPayload(Enrollment $enrollment): array
    {
        $studentId = $enrollment->studentId;
        $emergencyContactSubmitted = $studentId && (
            $studentId->emergency_contact_name
            && $studentId->emergency_contact_relationship
            && $studentId->emergency_contact_number
        );
        $photoSubmitted = (bool) ($studentId?->photo_path && Storage::disk('public')->exists($studentId->photo_path));
        $signatureSubmitted = (bool) ($studentId?->signature_path && Storage::disk('public')->exists($studentId->signature_path));
        $generated = $studentId && ($studentId->status === 'generated' || $studentId->generated_at);

        return [
            'requirements_submitted' => (bool) ($emergencyContactSubmitted || $photoSubmitted || $signatureSubmitted),
            'emergency_contact_submitted' => (bool) $emergencyContactSubmitted,
            'photo_submitted' => $photoSubmitted,
            'signature_submitted' => $signatureSubmitted,
            'requirements_status' => $studentId?->requirements_status ?? 'not_submitted',
            'submitted_at' => $studentId?->submitted_at?->format('M d, Y g:i A'),
            'generated' => (bool) $generated,
            'generated_at' => $studentId?->generated_at?->format('M d, Y g:i A'),
            'status' => $studentId?->status ?? 'draft',
        ];
    }

    private function activityLogPayloads()
    {
        return ActivityLog::with('user')
            ->latest('created_at')
            ->limit(150)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'label' => Str::of($log->action)->replace('_', ' ')->headline()->toString(),
                'model_type' => $log->model_type,
                'model_id' => $log->model_id,
                'user' => $log->user?->name ?? 'System / Guest',
                'user_role' => $log->user?->user_type ? str_replace('_', ' ', $log->user->user_type) : 'guest',
                'old_values' => $log->old_values ?? [],
                'new_values' => $log->new_values ?? [],
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->format('M d, Y g:i A'),
                'created_date' => $log->created_at?->toDateString(),
            ]);
    }

    private function idTemplateGenerationPayload(IdTemplate $template): ?array
    {
        if (! $this->templateAbsolutePath($template->background_image_path)) {
            return null;
        }

        $layout = $template->layout_config ?? [];

        return [
            'side' => $template->side,
            'width' => (int) round((float) ($layout['width'] ?? 540)),
            'height' => (int) round((float) ($layout['height'] ?? 340)),
            'background' => $this->templateDataUrl($template->background_image_path),
            'fields' => $layout['fields'] ?? [],
        ];
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

    private function preferredIdTemplate(string $side): ?IdTemplate
    {
        return IdTemplate::where('side', $side)
            ->where('is_active', true)
            ->where('background_image_path', 'not like', 'templates/%')
            ->latest()
            ->first()
            ?? IdTemplate::where('side', $side)
                ->where('background_image_path', 'like', 'templates/%')
                ->latest()
                ->first();
    }

    private function idStudentFields(Enrollment $enrollment): array
    {
        $studentId = $enrollment->studentId;
        $courseShortNames = [
            'BSCS' => 'BS in Computer Science',
            'BSIT' => 'BS in Information Technology',
            'BSBA' => 'BS in Business Administration',
            'BSOM' => 'BS in Office Management',
            'BSHM' => 'BS in Hospitality Management',
            'BSA' => 'BS in Accountancy',
            'ACT' => 'Associate in Computer Technology',
        ];
        $coursePlainNames = [
            'BSCS' => 'Computer Science',
            'BSIT' => 'Information Technology',
            'BSBA' => 'Business Administration',
            'BSOM' => 'Office Management',
            'BSHM' => 'Hospitality Management',
            'BSA' => 'Accountancy',
            'ACT' => 'Computer Technology',
        ];
        $courseFullNames = [
            'BSCS' => 'Bachelor of Science in Computer Science',
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSBA' => 'Bachelor of Science in Business Administration',
            'BSOM' => 'Bachelor of Science in Office Management',
            'BSHM' => 'Bachelor of Science in Hospitality Management',
            'BSA' => 'Bachelor of Science in Accountancy',
            'ACT' => 'Associate in Computer Technology',
        ];
        $courseCode = strtoupper((string) ($enrollment->course_code ?? ''));
        $yearLabels = [
            '1' => 'First Year',
            '2' => 'Second Year',
            '3' => 'Third Year',
            '4' => 'Fourth Year',
        ];
        $middleInitial = $enrollment->middle_name
            ? strtoupper(substr(trim($enrollment->middle_name), 0, 1)) . '.'
            : '';
        $fullName = trim(collect([
            $enrollment->last_name ? rtrim($enrollment->last_name, ',') . ',' : '',
            $enrollment->first_name,
            $middleInitial,
        ])->filter()->implode(' '));

        return [
            'student_number' => $enrollment->student_number ?? '',
            'full_name' => $fullName,
            'first_name' => $enrollment->first_name ?? '',
            'last_name' => $enrollment->last_name ?? '',
            'course_code' => $enrollment->course_code ?? '',
            'course_name' => $enrollment->course_name ?? '',
            'course_plain_name' => $coursePlainNames[$courseCode] ?? ($enrollment->course_name ?? $enrollment->course_code ?? ''),
            'course_short_name' => $courseShortNames[$courseCode] ?? ($enrollment->course_name ?? $enrollment->course_code ?? ''),
            'course_full_name' => $courseFullNames[$courseCode] ?? ($enrollment->course_name ?? $enrollment->course_code ?? ''),
            'year_level' => $yearLabels[(string) $enrollment->year_level] ?? ($enrollment->year_level ? $enrollment->year_level . ' Year' : ''),
            'date_of_birth' => $enrollment->date_of_birth?->format('M d, Y') ?? '',
            'school_year' => $enrollment->school_year ?? '',
            'present_address' => collect([
                $enrollment->present_address,
                $enrollment->barangay,
                $enrollment->city,
                $enrollment->province,
            ])->filter()->implode(', '),
            'cellphone' => $enrollment->cellphone ?? '',
            'email' => $enrollment->email ?? '',
            'emergency_contact_name' => $studentId?->emergency_contact_name ?? '',
            'emergency_contact_relationship' => $studentId?->emergency_contact_relationship ?? '',
            'emergency_contact_number' => $studentId?->emergency_contact_number ?? '',
        ] + collect($enrollment->custom_fields ?? [])
            ->merge($studentId?->custom_fields ?? [])
            ->mapWithKeys(fn ($value, $key) => [$key => is_array($value) ? implode(', ', $value) : (string) $value])
            ->all();
    }

    private function idCustomImages(StudentId $studentId): array
    {
        return CustomTemplateField::where('scope', 'id')
            ->where('input_type', 'photo')
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (CustomTemplateField $field) use ($studentId) {
                $path = $studentId->custom_fields[$field->key] ?? null;

                if (! is_string($path) || ! Storage::disk('public')->exists($path)) {
                    return [];
                }

                return [$field->key => $this->storageDataUrl($path)];
            })
            ->all();
    }

    private function idFontPayloads(): array
    {
        $disk = Storage::disk('public');

        return collect($disk->files('id-template-fonts'))
            ->filter(fn (string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['ttf', 'otf', 'woff', 'woff2'], true))
            ->map(fn (string $path) => [
                'family' => Str::of(pathinfo($path, PATHINFO_FILENAME))->headline()->toString(),
                'url' => '/storage/' . ltrim($path, '/'),
                'extension' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            ])
            ->values()
            ->all();
    }

    private function customTemplateFields(string $scope): array
    {
        return CustomTemplateField::where('scope', $scope)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(function (CustomTemplateField $field) {
                $isImage = $field->scope === 'id' && $field->input_type === 'photo';

                return [
                    'id' => $field->id,
                    'key' => $field->key,
                    'label' => $field->label,
                    'type' => $isImage ? 'image' : 'text',
                    'input_type' => $field->input_type,
                    'is_required' => (bool) $field->is_required,
                    'is_custom' => true,
                    'width' => $isImage ? 120 : 180,
                    'height' => $isImage ? 140 : 28,
                    'font_size' => 14,
                    'font_family' => 'Arial',
                    'font_weight' => '600',
                    'shape' => $isImage ? 'rectangle' : null,
                    'object_fit' => $isImage ? 'cover' : null,
                ];
            })
            ->values()
            ->all();
    }

    private function storageDataUrl(string $path): string
    {
        $disk = Storage::disk('public');
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
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

    private function templateDataUrl(string $path): string
    {
        if (str_starts_with($path, 'templates/')) {
            $absolutePath = public_path($path);
            $mime = mime_content_type($absolutePath) ?: 'application/octet-stream';

            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
        }

        return $this->storageDataUrl($path);
    }

    private function scheduleTimeLabel(SubjectSchedule $schedule): string
    {
        if ($schedule->timeSlot?->label) {
            return $schedule->timeSlot->label;
        }

        $start = $schedule->timeSlot?->start_time;
        $end = $schedule->timeSlot?->end_time;

        if (! $start || ! $end) {
            return '';
        }

        return date('g:i A', strtotime((string) $start)) . ' - ' . date('g:i A', strtotime((string) $end));
    }

    private function enrollmentEditPayload(Enrollment $enrollment): array
    {
        return [
            'id' => $enrollment->id,
            'student_number' => $enrollment->student_number,
            'date_filed' => $enrollment->date_filed?->format('Y-m-d'),
            'school_year' => $enrollment->school_year,
            'first_name' => $enrollment->first_name,
            'middle_name' => $enrollment->middle_name,
            'last_name' => $enrollment->last_name,
            'cellphone' => $enrollment->cellphone,
            'email' => $enrollment->email,
            'last_school' => $enrollment->last_school,
            'present_address' => $enrollment->present_address,
            'barangay' => $enrollment->barangay,
            'city' => $enrollment->city,
            'province' => $enrollment->province,
            'date_of_birth' => $enrollment->date_of_birth?->format('Y-m-d'),
            'age' => $enrollment->age,
            'place_of_birth' => $enrollment->place_of_birth,
            'civil_status' => $enrollment->civil_status,
            'gender' => $enrollment->gender,
            'religion' => $enrollment->religion,
            'father_name' => $enrollment->father_name,
            'father_address' => $enrollment->father_address,
            'father_cpNumber' => $enrollment->father_cpNumber,
            'mother_name' => $enrollment->mother_name,
            'mother_address' => $enrollment->mother_address,
            'mother_cpNumber' => $enrollment->mother_cpNumber,
            'course_code' => $enrollment->course_code,
            'course_name' => $enrollment->course_name,
            'year_level' => $enrollment->year_level,
            'semester' => $enrollment->semester,
            'department_head_name' => $enrollment->department_head_name,
            'credentials' => $enrollment->credentials ?? [],
            'enrollment_status' => $enrollment->enrollment_status,
        ];
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

    private function scheduleSubjectName(SubjectSchedule $schedule): string
    {
        $type = $schedule->schedule_type ?: ($schedule->subject?->type === 'LAB' ? 'LAB' : 'LEC');

        return ($schedule->subject?->name ?? 'No subject') . ' - ' . $type;
    }

    private function schedulePayload(SubjectSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'subject' => [
                'id' => $schedule->subject?->id,
                'code' => $schedule->subject?->code,
                'name' => $schedule->subject?->name,
                'course_code' => $schedule->subject?->course_code,
                'year_level' => $schedule->subject?->year_level,
                'semester' => $schedule->subject?->semester,
                'type' => $schedule->subject?->type,
            ],
            'day_id' => $schedule->day_id,
            'day' => $schedule->day?->name,
            'time' => $this->scheduleTimeLabel($schedule),
            'start_time' => $schedule->timeSlot?->start_time ? substr((string) $schedule->timeSlot->start_time, 0, 5) : null,
            'end_time' => $schedule->timeSlot?->end_time ? substr((string) $schedule->timeSlot->end_time, 0, 5) : null,
            'room_id' => $schedule->room_id,
            'room' => $schedule->room?->name,
            'instructor' => $schedule->instructor ?: 'Unassigned',
            'schedule_type' => $schedule->schedule_type ?: 'LEC',
            'schedule_for' => $schedule->schedule_for ?: 'Whole Class',
            'school_year' => $schedule->school_year,
            'archived_school_year' => $schedule->archived_school_year,
            'archived_at' => $schedule->archived_at?->toDateTimeString(),
            'subject_display_name' => $this->scheduleSubjectName($schedule),
            'update_url' => route('academic.schedules.update', $schedule),
            'delete_url' => route('academic.schedules.destroy', $schedule),
        ];
    }
}
