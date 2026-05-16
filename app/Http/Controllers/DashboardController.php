<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Day;
use App\Models\DepartmentHead;
use App\Models\Room;
use App\Models\Subject;
use App\Models\SubjectSchedule;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
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
        $allEnrollments = Enrollment::orderByDesc('created_at')->get();
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

        return view('dashboard.index', compact(
            'stats',
            'courseStats',
            'chartData',
            'recentEnrollments',
            'allEnrollments',
            'subjects',
            'days',
            'rooms',
            'timeSlots',
            'subjectSchedules',
            'departmentHeads'
        ));
    }
}
