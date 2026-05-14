@extends('layouts.dashboard')

@section('title', 'COMTEQ | Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div class="space-y-6">

    {{-- Header Row --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-slate-800">@yield('page-title', 'Dashboard')</h2>
            <p class="text-slate-400 text-sm mt-0.5">A.Y. 2026–2027 · {{ now()->format('l, F j') }}</p>
        </div>
        <a href="{{ route('enrollment.create') }}"
           class="flex items-center gap-2 px-5 py-2.5 bg-[#1a52f4] hover:bg-blue-700 text-white text-sm font-semibold rounded-2xl transition-colors duration-150 shadow-lg shadow-blue-200">
            <i data-lucide="plus" class="w-4 h-4"></i>
            New Enrollment
        </a>
    </div>

    {{-- TOP ROW: Stat Cards (left) + Chart (right) --}}
    <div class="grid grid-cols-12 gap-5">

        {{-- Stat Cards Column --}}
        <div class="col-span-12 lg:col-span-4 flex flex-col gap-4">

            {{-- Total Enrolled --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total Enrolled</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">{{ number_format($stats['total_enrolled']) }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-600">
                            <i data-lucide="trending-up" class="w-3 h-3"></i>
                            +{{ $stats['enrolled_today'] }} today
                        </span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center shrink-0">
                    <i data-lucide="users" class="w-6 h-6 text-[#1a52f4]"></i>
                </div>
            </div>

            {{-- Pending --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Pending Enrollees</p>
                    <p class="text-3xl font-bold text-slate-800 mt-1">{{ number_format($stats['pending']) }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-600">
                            <i data-lucide="clock" class="w-3 h-3"></i>
                            Awaiting review
                        </span>
                    </div>
                </div>
                <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center shrink-0">
                    <i data-lucide="clock" class="w-6 h-6 text-amber-500"></i>
                </div>
            </div>

            {{-- Courses + Subjects --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center mb-3">
                        <i data-lucide="book-open" class="w-5 h-5 text-violet-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['courses'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Courses</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center mb-3">
                        <i data-lucide="layers" class="w-5 h-5 text-emerald-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">{{ $stats['subjects'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Active Subjects</p>
                </div>
            </div>

        </div>

        {{-- Enrollment Analytics Chart --}}
        <div class="col-span-12 lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="font-semibold text-slate-800">Enrollment Analytics</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Enrollments per semester</p>
                </div>
                <div class="flex items-center gap-2" x-data="{ period: 'semester' }">
                    <button @click="period = 'semester'; updateChart('semester')"
                            :class="period === 'semester' ? 'bg-[#1a52f4] text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors duration-150">
                        Per Semester
                    </button>
                    <button @click="period = 'year'; updateChart('year')"
                            :class="period === 'year' ? 'bg-[#1a52f4] text-white' : 'bg-slate-100 text-slate-500'"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors duration-150">
                        Per Year
                    </button>
                </div>
            </div>
            <div class="relative h-52">
                <canvas id="enrollmentChart"></canvas>
            </div>
        </div>

    </div>

    {{-- BOTTOM ROW: Course Leaderboard (left) + Recent Enrollments (right) --}}
    <div class="grid grid-cols-12 gap-5">

        {{-- Course Leaderboard --}}
        <div class="col-span-12 lg:col-span-4 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-slate-800">Enrollment by Course</h3>
                <span class="text-xs text-slate-400">This semester</span>
            </div>
            <div class="space-y-4">
                @forelse($courseStats as $course)
                    @php
                        $total = $courseStats->sum('total') ?: 1;
                        $pct   = round(($course->total / $total) * 100);
                        $colors = ['bg-[#1a52f4]', 'bg-violet-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500'];
                        $bar   = $colors[$loop->index % count($colors)];
                    @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-slate-700">{{ $course->course_code }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400">{{ $course->total }} students</span>
                                <span class="text-xs font-bold text-slate-600">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div class="{{ $bar }} h-full rounded-full transition-all duration-500"
                                 style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-6">No data yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Recent Enrollments Table --}}
        <div class="col-span-12 lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 flex items-center justify-between border-b border-slate-100">
                <h3 class="font-semibold text-slate-800">Recent Enrollments</h3>
                <a href="#" class="text-xs text-[#1a52f4] font-semibold hover:underline">See all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-slate-400 text-xs uppercase tracking-wide">
                            <th class="px-6 py-3 text-left font-medium">Student</th>
                            <th class="px-6 py-3 text-left font-medium">Program</th>
                            <th class="px-6 py-3 text-left font-medium">Year/Sem</th>
                            <th class="px-6 py-3 text-left font-medium">Status</th>
                            <th class="px-6 py-3 text-left font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse ($recentEnrollments as $enrollment)
                            <tr class="hover:bg-slate-50/60 transition-colors duration-100">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-blue-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                                            {{ strtoupper(substr($enrollment->first_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-slate-800 text-xs">
                                                {{ $enrollment->last_name }}, {{ $enrollment->first_name }}
                                            </p>
                                            <p class="text-slate-400 text-xs font-mono">{{ $enrollment->student_number ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-3.5 text-slate-600 text-xs font-medium">{{ $enrollment->course_code }}</td>
                                <td class="px-6 py-3.5 text-slate-400 text-xs">{{ $enrollment->year_level }}yr · {{ $enrollment->semester }}</td>
                                <td class="px-6 py-3.5">
                                    <x-enrollment-badge :status="$enrollment->enrollment_status" />
                                </td>
                                <td class="px-6 py-3.5 text-slate-400 text-xs">
                                    {{ \Carbon\Carbon::parse($enrollment->date_filed)->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="text-sm">No enrollments yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();

        const semesterData = @json($chartData['semester']);
        const yearData     = @json($chartData['year']);

        const ctx = document.getElementById('enrollmentChart').getContext('2d');

        const chartConfig = (labels, enrolled, pending) => ({
            type: 'bar',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Enrolled',
                        data: enrolled,
                        backgroundColor: '#1a52f4',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.55,
                    },
                    {
                        label: 'Pending',
                        data: pending,
                        backgroundColor: '#e2e8f0',
                        borderRadius: 8,
                        borderSkipped: false,
                        barPercentage: 0.55,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 10, boxHeight: 10, borderRadius: 5, useBorderRadius: true, font: { size: 11 } },
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 10,
                        titleFont: { size: 12 },
                        bodyFont: { size: 11 },
                    },
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } },
                    y: { grid: { color: '#f1f5f9' }, border: { dash: [4, 4] }, ticks: { font: { size: 11 }, color: '#94a3b8' }, beginAtZero: true },
                },
            },
        });

        let chart = new Chart(ctx, chartConfig(
            semesterData.labels,
            semesterData.enrolled,
            semesterData.pending
        ));

        window.updateChart = (period) => {
            const d = period === 'year' ? yearData : semesterData;
            chart.data.labels           = d.labels;
            chart.data.datasets[0].data = d.enrolled;
            chart.data.datasets[1].data = d.pending;
            chart.update();
        };
    });
</script>
@endpush