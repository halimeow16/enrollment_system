<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Enrollment;
use App\Models\AppSetting;
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
        $stats = [
            'total_enrolled' => Enrollment::where('enrollment_status', 'enrolled')->count(),
            'pending'        => Enrollment::where('enrollment_status', 'pending')->count(),
            'enrolled_today' => Enrollment::where('enrollment_status', 'enrolled')
                                          ->whereDate('updated_at', today())
                                          ->count(),
            'courses'        => DB::table('enrollments')->distinct()->count('course_code'),
            'subjects'       => DB::table('subjects')->where('is_active', true)->count(),
        ];

        // Course leaderboard
        $courseStats = DB::table('enrollments')
            ->select('course_code', DB::raw('COUNT(*) as total'))
            ->whereNotNull('course_code')
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
        $recentEnrollments = Enrollment::orderByDesc('created_at')->limit(8)->get();
        $allEnrollments = Enrollment::with('studentId')->orderByDesc('created_at')->get();
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
        $subjectSchedules = SubjectSchedule::with(['subject', 'day', 'timeSlot', 'room'])
            ->latest()
            ->get();
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

        return view('dashboard.index', compact(
            'stats',
            'courseStats',
            'chartData',
            'recentEnrollments',
            'allEnrollments',
            'idGenerationStatuses',
            'subjects',
            'days',
            'rooms',
            'timeSlots',
            'subjectSchedules',
            'departmentHeads',
            'feeRows',
            'feeTypes',
            'activeEnrollmentTemplate',
            'enrollmentTemplatePayload',
            'idTemplatePayloads',
            'idTemplatePayload',
            'idFonts',
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

    public function updateEnrollmentStatus(Request $request, Enrollment $enrollment): RedirectResponse|JsonResponse
    {
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

    public function idCardData(Enrollment $enrollment): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar'], true), 403);

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
            ],
            'templates' => $templates,
        ]);
    }

    public function idGenerationStatuses(): JsonResponse
    {
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar'], true), 403);

        $statuses = Enrollment::with('studentId')
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
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar'], true), 403);

        if ($enrollment->enrollment_status !== 'enrolled') {
            return response()->json([
                'message' => 'Only enrolled students can have an ID generated.',
            ], 422);
        }

        $studentId = StudentId::firstOrNew(['enrollment_id' => $enrollment->id]);
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
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar'], true), 403);

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

        $studentId = StudentId::firstOrNew(['enrollment_id' => $enrollment->id]);
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
        abort_unless(in_array(auth()->user()?->user_type, ['admin', 'registrar'], true), 403);

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

        $studentId = StudentId::firstOrNew(['enrollment_id' => $enrollment->id]);
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
        ];
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
}
