@extends('layouts.dashboard')

@section('title', 'COMTEQ | Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div x-data="dashboardFrame()"
     x-init="init()"
     @dashboard-tab-selected.window="switchTab($event.detail.tab)"
     @dashboard-toast.window="showToast($event.detail.type, $event.detail.title, $event.detail.message)"
     @edit-enrollment.window="openEnrollmentEditor($event.detail.enrollment, $event.detail.url)"
     class="space-y-5">
    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <template x-teleport="body">
        <div class="fixed right-6 top-6 z-[1000] w-[min(360px,calc(100vw-2rem))] space-y-3 pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="-translate-y-2 opacity-0"
                     x-transition:enter-end="translate-y-0 opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="translate-y-0 opacity-100"
                     x-transition:leave-end="-translate-y-2 opacity-0"
                     :class="toast.type === 'success' ? 'border-emerald-300/20 bg-emerald-500/15 text-emerald-50' : 'border-red-300/20 bg-red-500/15 text-red-50'"
                     class="pointer-events-auto rounded-2xl border px-4 py-3 shadow-2xl shadow-black/30 backdrop-blur">
                    <div class="flex items-start gap-3">
                        <span :class="toast.type === 'success' ? 'bg-emerald-300/20 text-emerald-100' : 'bg-red-300/20 text-red-100'"
                              class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl">
                            <i :data-lucide="toast.type === 'success' ? 'check' : 'alert-circle'" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold" x-text="toast.title"></p>
                            <p class="mt-0.5 text-xs opacity-80" x-text="toast.message"></p>
                        </div>
                        <button type="button" @click="dismissToast(toast.id)" class="text-white/60 transition hover:text-white">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>

    {{-- Shell header --}}
    <div class="rounded-[28px] border border-white/10 bg-white/10 glass px-6 py-5 shadow-2xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">
                    {{ match (auth()->user()->user_type ?? 'registrar') {
                        'admin' => 'Admin Workspace',
                        'department_head' => 'Dept. Head Workspace',
                        default => 'Registrar Workspace',
                    } }}
                </p>
                <h1 class="mt-2 text-3xl font-extrabold text-white" x-text="titles[activeTab]"></h1>
                <p class="mt-1 text-sm text-slate-300">A.Y. <span x-text="academicYear"></span> - {{ now()->format('l, F j') }}</p>

            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="openFormFrame()"
                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#1552d4] to-[#0f43b0] px-5 py-3 text-sm font-bold text-white shadow-xl shadow-blue-950/20 transition hover:scale-[1.01]">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    New Enrollment
                </button>
                <button type="button"
                        @click="switchTab('enrollments')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/15">
                    <i data-lucide="list" class="w-4 h-4"></i>
                    View Records
                </button>
            </div>
        </div>
    </div>

    {{-- Embedded frame for pages that still have their own route --}}
    <section x-show="activeTab === 'form'" x-cloak class="overflow-hidden rounded-[28px] border border-white/10 bg-white/95 shadow-2xl shadow-black/10">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <div>
                <h2 class="text-sm font-bold text-slate-900">New Enrollment Form</h2>
                <p class="text-xs text-slate-500">Loaded inside the dashboard so the workspace does not redirect.</p>
            </div>
            <button type="button"
                    @click="switchTab(previousTab || 'overview')"
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                <i data-lucide="x" class="w-4 h-4"></i>
                Close
            </button>
        </div>
        <iframe title="Enrollment form"
                src="{{ route('enrollment.create') }}"
                class="h-[calc(100vh-220px)] min-h-[680px] w-full bg-white"></iframe>
    </section>

    {{-- Overview --}}
    <section x-show="activeTab === 'overview'" x-cloak class="space-y-5">
        <div class="grid grid-cols-12 gap-5">
            <div class="col-span-12 lg:col-span-4 grid gap-4">
                <button type="button"
                        @click="openEnrollmentsWithStatus('enrolled')"
                        class="w-full rounded-3xl border border-blue-400/20 bg-gradient-to-br from-[#143b8f]/95 via-[#10295d]/95 to-[#0b172f]/95 p-5 text-left shadow-2xl shadow-blue-950/30 transition hover:-translate-y-0.5 hover:border-blue-200/40">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-blue-100/80 uppercase tracking-wide">Total Enrolled</p>
                            <p class="text-3xl font-extrabold text-white mt-1" x-text="formatCount(stats.total_enrolled)"></p>
                        </div>
                        <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center">
                            <i data-lucide="users" class="w-5 h-5 text-blue-100"></i>
                        </span>
                    </div>
                    <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-400/15 border border-emerald-300/20 px-2.5 py-1 text-xs font-bold text-emerald-100">
                        <i data-lucide="trending-up" class="w-3 h-3"></i>
                        +{{ $stats['enrolled_today'] }} today
                    </p>
                </button>

                <button type="button"
                        @click="openEnrollmentsWithStatus('pending')"
                        class="w-full rounded-3xl border border-amber-300/20 bg-gradient-to-br from-[#4a2d08]/95 via-[#23304c]/95 to-[#0b172f]/95 p-5 text-left shadow-2xl shadow-amber-950/20 transition hover:-translate-y-0.5 hover:border-amber-200/40">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold text-amber-100/80 uppercase tracking-wide">Pending Review</p>
                            <p class="text-3xl font-extrabold text-white mt-1" x-text="formatCount(stats.pending)"></p>
                        </div>
                        <span class="w-11 h-11 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-amber-100"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-xs font-medium text-amber-100/70">Applications waiting for action.</p>
                </button>

                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-3xl border border-blue-300/20 bg-gradient-to-br from-[#0f43b0]/95 to-[#071224]/95 p-5 shadow-2xl shadow-blue-950/20">
                        <i data-lucide="book-open" class="w-5 h-5 text-blue-100"></i>
                        <p class="text-2xl font-extrabold text-white mt-3">{{ $stats['courses'] }}</p>
                        <p class="text-xs text-blue-100/70 mt-0.5">Enrolled Courses</p>
                    </div>
                    <div class="rounded-3xl border border-red-300/20 bg-gradient-to-br from-[#7f1d1d]/95 to-[#071224]/95 p-5 shadow-2xl shadow-red-950/20">
                        <i data-lucide="layers" class="w-5 h-5 text-red-100"></i>
                        <p class="text-2xl font-extrabold text-white mt-3" x-text="formatCount(subjectCount)"></p>
                        <p class="text-xs text-red-100/70 mt-0.5">Active Subjects</p>
                    </div>
                </div>
            </div>

            <div class="col-span-12 lg:col-span-8 max-h-[420px] rounded-3xl border border-blue-300/15 bg-gradient-to-br from-[#111c34]/95 via-[#0d1b33]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-5">
                    <div>
                        <h2 class="font-extrabold text-white">Enrollment Analytics</h2>
                        <p class="text-xs text-slate-300 mt-0.5">Enrolled and pending applications over time.</p>
                    </div>
                    <div class="inline-flex rounded-2xl border border-white/10 bg-white/5 p-1" x-data="{ period: 'semester' }">
                        <button @click="period = 'semester'; updateChart('semester')"
                                :class="period === 'semester' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300'"
                                class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors">
                            Semester
                        </button>
                        <button @click="period = 'year'; updateChart('year')"
                                :class="period === 'year' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300'"
                                class="rounded-xl px-3 py-1.5 text-xs font-bold transition-colors">
                            Year
                        </button>
                    </div>
                </div>
                <div class="relative h-60">
                    <canvas id="enrollmentChart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-5">
            <div class="col-span-12 max-h-[430px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30 lg:col-span-4 lg:h-[430px]">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="font-extrabold text-white">Enrollment by Course</h2>
                    <span class="text-xs font-medium text-slate-300">Current data</span>
                </div>
                <div class="max-h-[340px] space-y-4 overflow-y-auto pr-2">
                    @forelse($courseStats as $course)
                        @php
                            $total = $courseStats->sum('total') ?: 1;
                            $pct = round(($course->total / $total) * 100);
                            $colors = ['bg-[#1552d4]', 'bg-[#d9151f]', 'bg-slate-700', 'bg-emerald-600', 'bg-amber-500'];
                            $bar = $colors[$loop->index % count($colors)];
                        @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-sm font-bold text-white">{{ $course->course_code }}</span>
                                <span class="text-xs text-slate-300">{{ $course->total }} students</span>
                            </div>
                            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                                <div class="{{ $bar }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-300 text-center py-6">No course data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="col-span-12 max-h-[430px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30 lg:col-span-8 lg:h-[430px]">
                <div class="px-5 py-4 flex items-center justify-between border-b border-white/10">
                    <h2 class="font-extrabold text-white">Recent Enrollments</h2>
                    <button type="button" @click="switchTab('enrollments')" class="text-xs text-blue-200 font-bold hover:text-white">
                        See all
                    </button>
                </div>
                <div x-ref="recentEnrollmentsTable">
                    @include('dashboard.partials.enrollment-table', ['enrollments' => $recentEnrollments, 'compact' => true])
                </div>
            </div>
        </div>
    </section>

    {{-- All enrollments --}}
    <section x-show="activeTab === 'enrollments'" x-cloak class="h-[calc(100vh-170px)] min-h-[560px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
        <div class="px-5 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-white/10">
            <div>
                <h2 class="font-extrabold text-white">All Enrollments</h2>
                <p class="text-xs text-slate-300 mt-0.5">
                    <span x-text="formatCount(enrollmentCount)"></span>
                    <span x-text="archivedEnrollmentYear ? `archived records from A.Y. ${archivedEnrollmentYear}` : 'active records from the current academic year'"></span>.
                </p>
            </div>
            <div class="flex w-full flex-col gap-2 sm:flex-row md:w-auto">
                <div class="relative w-full md:w-72">
                    <i data-lucide="search" class="absolute left-3 top-2.5 w-4 h-4 text-slate-400"></i>
                    <input type="search"
                           x-model="search"
                           placeholder="Search student, number, course..."
                           class="w-full rounded-2xl border border-white/10 bg-white/10 pl-9 pr-3 py-2 text-sm text-white placeholder:text-slate-400 outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20">
                </div>
                <select x-model="statusFilter"
                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20 sm:w-44">
                    <option class="text-slate-900" value="">All Status</option>
                    <option class="text-slate-900" value="pending">Pending</option>
                    <option class="text-slate-900" value="enrolled">Enrolled</option>
                    <option class="text-slate-900" value="cancelled">Cancelled</option>
                </select>
                <select x-model="archivedEnrollmentYear"
                        @change="refreshEnrollmentTables()"
                        class="w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20 sm:w-52">
                    <option class="text-slate-900" value="">Current A.Y.</option>
                    <template x-for="year in archivedEnrollmentYears" :key="`enrollment-archive-${year}`">
                        <option class="text-slate-900" :value="year" x-text="`Archive ${year}`"></option>
                    </template>
                </select>
            </div>
        </div>
        <div x-ref="allEnrollmentsTable">
            @include('dashboard.partials.enrollment-table', ['enrollments' => $allEnrollments, 'compact' => false])
        </div>
    </section>

    {{-- ID generation --}}
    <section x-show="activeTab === 'id-generation'" x-cloak class="h-[calc(100vh-170px)] min-h-[560px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
        <div class="border-b border-white/10 px-5 py-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-extrabold text-white">Generate IDs</h2>
                    <p class="mt-0.5 text-xs text-slate-300">
                        <span x-text="formatCount(stats.total_enrolled)"></span> enrolled students ready for ID generation.
                    </p>
                </div>

                <div class="flex w-full flex-col gap-2 sm:flex-row md:w-auto">
                    <div class="relative w-full md:w-80">
                        <i data-lucide="search" class="absolute left-3 top-2.5 h-4 w-4 text-slate-400"></i>
                        <input type="search"
                               x-model="idSearch"
                               placeholder="Search student, number, course..."
                               class="w-full rounded-2xl border border-white/10 bg-white/10 py-2 pl-9 pr-3 text-sm text-white placeholder:text-slate-400 outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20">
                    </div>
                    <select x-model="idGenerationFilter"
                            class="w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-sm font-semibold text-white outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20 sm:w-36">
                        <option class="text-slate-900" value="">All</option>
                        <option class="text-slate-900" value="pending">Pending</option>
                        <option class="text-slate-900" value="generated">Generated</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="h-[calc(100%-73px)] overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-white/5 text-xs uppercase tracking-wide text-slate-300">
                        <th class="px-5 py-3 text-left font-semibold">Student</th>
                        <th class="px-5 py-3 text-left font-semibold">Program</th>
                        <th class="px-5 py-3 text-left font-semibold">Year/Sem</th>
                        <th class="px-5 py-3 text-left font-semibold">Date Filed</th>
                        <th class="px-5 py-3 text-left font-semibold">Contact</th>
                        <th class="w-44 px-5 py-3 text-left font-semibold">ID Status</th>
                        <th class="w-48 px-5 py-3 text-right font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($allEnrollments as $enrollment)
                        @php
                            $name = trim(($enrollment->last_name ?? '') . ', ' . ($enrollment->first_name ?? ''));
                            $name = $name === ',' ? 'Unnamed student' : $name;
                            $rowText = strtolower(implode(' ', [
                                $name,
                                $enrollment->student_number,
                                $enrollment->course_code,
                                $enrollment->course_name,
                                $enrollment->year_level,
                                $enrollment->semester,
                                $enrollment->email,
                                $enrollment->cellphone,
                            ]));
                        @endphp
                        <tr x-show="matchesIdGeneration(@js($rowText), {{ $enrollment->id }})"
                            x-cloak
                            class="transition-colors duration-100 hover:bg-white/5">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-blue-300/20 bg-blue-500/20 text-xs font-bold text-blue-100">
                                        {{ strtoupper(substr($enrollment->first_name ?? $enrollment->last_name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-white">{{ $name }}</p>
                                        <p class="font-mono text-xs text-slate-400">{{ $enrollment->student_number ?? 'No student no.' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-xs font-semibold text-blue-100">{{ $enrollment->course_code ?? 'Not set' }}</p>
                                <p class="max-w-52 truncate text-xs text-slate-400">{{ $enrollment->course_name ?? 'Course name unavailable' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-300">
                                {{ $enrollment->year_level ? $enrollment->year_level . 'yr' : 'Year not set' }}
                                <span class="mx-1 text-slate-500">/</span>
                                {{ $enrollment->semester ?? 'Sem not set' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">
                                {{ $enrollment->date_filed ? $enrollment->date_filed->format('M d, Y') : 'Not filed' }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-400">
                                <p>{{ $enrollment->cellphone ?? 'No phone' }}</p>
                                <p class="max-w-48 truncate">{{ $enrollment->email ?? 'No email' }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex max-w-40 flex-wrap gap-1.5">
                                    <span x-show="!idStatusFor({{ $enrollment->id }}).generated"
                                          x-cloak
                                          class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-bold leading-none"
                                          :class="idStatusFor({{ $enrollment->id }}).emergency_contact_submitted ? 'bg-emerald-400/15 text-emerald-100 ring-1 ring-emerald-300/20' : 'bg-slate-400/10 text-slate-300 ring-1 ring-white/10'">
                                        <i :data-lucide="idStatusFor({{ $enrollment->id }}).emergency_contact_submitted ? 'phone-call' : 'phone-off'" class="h-3 w-3"></i>
                                        <span x-text="idStatusFor({{ $enrollment->id }}).emergency_contact_submitted ? 'Contact' : 'No contact'"></span>
                                    </span>
                                    <span x-show="!idStatusFor({{ $enrollment->id }}).generated"
                                          x-cloak
                                          class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-bold leading-none"
                                          :class="idStatusFor({{ $enrollment->id }}).photo_submitted ? 'bg-cyan-400/15 text-cyan-100 ring-1 ring-cyan-300/20' : 'bg-slate-400/10 text-slate-300 ring-1 ring-white/10'">
                                        <i :data-lucide="idStatusFor({{ $enrollment->id }}).photo_submitted ? 'image-check' : 'image-off'" class="h-3 w-3"></i>
                                        <span x-text="idStatusFor({{ $enrollment->id }}).photo_submitted ? 'Photo' : 'No photo'"></span>
                                    </span>
                                    <span x-show="!idStatusFor({{ $enrollment->id }}).generated"
                                          x-cloak
                                          class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-bold leading-none"
                                          :class="idStatusFor({{ $enrollment->id }}).signature_submitted ? 'bg-violet-400/15 text-violet-100 ring-1 ring-violet-300/20' : 'bg-slate-400/10 text-slate-300 ring-1 ring-white/10'">
                                        <i :data-lucide="idStatusFor({{ $enrollment->id }}).signature_submitted ? 'pen-line' : 'pen-off'" class="h-3 w-3"></i>
                                        <span x-text="idStatusFor({{ $enrollment->id }}).signature_submitted ? 'Sign' : 'No sign'"></span>
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-1 text-[11px] font-bold leading-none"
                                          :class="idStatusFor({{ $enrollment->id }}).generated ? 'bg-blue-400/15 text-blue-100 ring-1 ring-blue-300/20' : 'bg-amber-400/10 text-amber-100 ring-1 ring-amber-300/15'">
                                        <i :data-lucide="idStatusFor({{ $enrollment->id }}).generated ? 'badge-check' : 'badge'" class="h-3 w-3"></i>
                                        <span x-text="idStatusFor({{ $enrollment->id }}).generated ? 'Generated' : 'Pending'"></span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-1.5">
                                    <label title="Upload photo" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-cyan-300/20 bg-cyan-500/10 text-cyan-100 transition hover:bg-cyan-500/20">
                                        <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                        <span class="sr-only">Upload photo</span>
                                        <input type="file"
                                               accept="image/png,image/jpeg,image/webp"
                                               class="sr-only"
                                               @change="uploadIdAsset($event, '{{ route('enrollments.id-photo', $enrollment) }}', {{ $enrollment->id }}, 'photo')">
                                    </label>
                                    <label title="Upload signature" class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-xl border border-violet-300/20 bg-violet-500/10 text-violet-100 transition hover:bg-violet-500/20">
                                        <i data-lucide="pen-line" class="h-3.5 w-3.5"></i>
                                        <span class="sr-only">Upload signature</span>
                                        <input type="file"
                                               accept="image/png,image/jpeg,image/webp"
                                               class="sr-only"
                                               @change="uploadIdAsset($event, '{{ route('enrollments.id-signature', $enrollment) }}', {{ $enrollment->id }}, 'signature')">
                                    </label>
                                    <button type="button"
                                            @click="previewIdCard('{{ route('enrollments.id-card-data', $enrollment) }}')"
                                            title="Preview ID"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/10 text-slate-100 transition hover:bg-white/15">
                                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                        <span class="sr-only">Preview ID</span>
                                    </button>
                                <button type="button"
                                        @click="generateIdCard('{{ route('enrollments.id-card-data', $enrollment) }}', '{{ route('enrollments.id-generated', $enrollment) }}', {{ $enrollment->id }})"
                                        title="Generate ID"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-300/20 bg-blue-500/15 text-blue-100 transition hover:bg-blue-500/25">
                                    <i data-lucide="badge" class="h-3.5 w-3.5"></i>
                                    <span class="sr-only">Generate ID</span>
                                </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-300">
                                <i data-lucide="inbox" class="mx-auto mb-2 h-8 w-8 opacity-40"></i>
                                <p class="text-sm">No enrollments yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div x-show="enrollmentEditor.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
         @click.self="closeEnrollmentEditor()"
         @keydown.escape.window="closeEnrollmentEditor()">
        <form :action="enrollmentEditor.url"
              method="POST"
              class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#101a2d] shadow-2xl shadow-black/40"
              @submit.prevent="saveEnrollmentEditor($event.target)">
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <div>
                    <h3 class="text-base font-extrabold text-white">Edit Enrollment</h3>
                    <p class="text-xs text-slate-400">Update saved student and enrollment details.</p>
                </div>
                <button type="button"
                        @click="closeEnrollmentEditor()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto p-5">
                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-3">
                        <h4 class="text-sm font-extrabold text-white">Enrollment</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-4">
                            <label class="text-xs font-semibold text-slate-300">Student No.
                                <input name="student_number" x-model="enrollmentEditor.form.student_number" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Date Filed
                                <input type="date" name="date_filed" x-model="enrollmentEditor.form.date_filed" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">School Year
                                <input name="school_year" x-model="enrollmentEditor.form.school_year" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Department Head
                                <input name="department_head_name" x-model="enrollmentEditor.form.department_head_name" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Course Code
                                <input name="course_code" x-model="enrollmentEditor.form.course_code" required list="edit-course-codes" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300 md:col-span-2">Course Name
                                <input name="course_name" x-model="enrollmentEditor.form.course_name" required class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Year / Semester
                                <div class="mt-1 grid grid-cols-2 gap-2">
                                    <select name="year_level" x-model="enrollmentEditor.form.year_level" required class="rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                                        <option class="text-slate-900" value="1">1st Year</option>
                                        <option class="text-slate-900" value="2">2nd Year</option>
                                        <option class="text-slate-900" value="3">3rd Year</option>
                                        <option class="text-slate-900" value="4">4th Year</option>
                                    </select>
                                    <select name="semester" x-model="enrollmentEditor.form.semester" required class="rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                                        <option class="text-slate-900" value="1st">1st</option>
                                        <option class="text-slate-900" value="2nd">2nd</option>
                                        <option class="text-slate-900" value="Summer">Summer</option>
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-2">
                        <h4 class="text-sm font-extrabold text-white">Student Information</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            <label class="text-xs font-semibold text-slate-300">First Name
                                <input name="first_name" x-model="enrollmentEditor.form.first_name" required class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Middle Name
                                <input name="middle_name" x-model="enrollmentEditor.form.middle_name" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Last Name
                                <input name="last_name" x-model="enrollmentEditor.form.last_name" required class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Birthdate
                                <input type="date" name="date_of_birth" x-model="enrollmentEditor.form.date_of_birth" required class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Age
                                <input type="number" min="1" name="age" x-model="enrollmentEditor.form.age" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Gender
                                <input name="gender" x-model="enrollmentEditor.form.gender" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Civil Status
                                <input name="civil_status" x-model="enrollmentEditor.form.civil_status" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Religion
                                <input name="religion" x-model="enrollmentEditor.form.religion" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Place of Birth
                                <input name="place_of_birth" x-model="enrollmentEditor.form.place_of_birth" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h4 class="text-sm font-extrabold text-white">Contact</h4>
                        <div class="mt-3 space-y-3">
                            <label class="block text-xs font-semibold text-slate-300">Cellphone
                                <input name="cellphone" x-model="enrollmentEditor.form.cellphone" placeholder="09XXXXXXXXX" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="block text-xs font-semibold text-slate-300">Email
                                <input type="email" name="email" x-model="enrollmentEditor.form.email" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="block text-xs font-semibold text-slate-300">Last School
                                <input name="last_school" x-model="enrollmentEditor.form.last_school" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-3">
                        <h4 class="text-sm font-extrabold text-white">Address</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-4">
                            <label class="text-xs font-semibold text-slate-300 md:col-span-2">Present Address
                                <input name="present_address" x-model="enrollmentEditor.form.present_address" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Barangay
                                <input name="barangay" x-model="enrollmentEditor.form.barangay" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">City
                                <input name="city" x-model="enrollmentEditor.form.city" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Province
                                <input name="province" x-model="enrollmentEditor.form.province" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-3">
                        <h4 class="text-sm font-extrabold text-white">Parents / Guardian</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            <label class="text-xs font-semibold text-slate-300">Father Name
                                <input name="father_name" x-model="enrollmentEditor.form.father_name" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Father Contact
                                <input name="father_cpNumber" x-model="enrollmentEditor.form.father_cpNumber" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Father Address
                                <input name="father_address" x-model="enrollmentEditor.form.father_address" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Mother Name
                                <input name="mother_name" x-model="enrollmentEditor.form.mother_name" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Mother Contact
                                <input name="mother_cpNumber" x-model="enrollmentEditor.form.mother_cpNumber" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">Mother Address
                                <input name="mother_address" x-model="enrollmentEditor.form.mother_address" class="mt-1 w-full rounded-lg border border-white/10 bg-white/10 px-3 py-2 text-sm text-white outline-none">
                            </label>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-3">
                        <h4 class="text-sm font-extrabold text-white">Credentials</h4>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach([
                                'form_138' => 'Form 138',
                                'birth_certificate' => 'Birth Certificate',
                                'good_moral' => 'Good Moral',
                                'certificate_grades' => 'Certificate of Grades',
                                'certificate_eligibility' => 'Certificate of Eligibility',
                                'transcript' => 'Transcript',
                                'long_folder' => 'Long Folder',
                                'picture' => 'Picture',
                            ] as $credential => $label)
                                <label class="flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-200">
                                    <input type="checkbox"
                                           name="credentials[]"
                                           value="{{ $credential }}"
                                           x-model="enrollmentEditor.form.credentials"
                                           class="rounded border-white/20 bg-white/10 text-[#1552d4]">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <datalist id="edit-course-codes">
                @foreach($subjects->pluck('course_code')->filter()->unique()->sort()->values() as $courseCode)
                    <option value="{{ $courseCode }}"></option>
                @endforeach
            </datalist>

            <div class="flex justify-end gap-2 border-t border-white/10 px-5 py-4">
                <button type="button"
                        @click="closeEnrollmentEditor()"
                        class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-sm font-bold text-slate-200 transition hover:bg-white/10">
                    Cancel
                </button>
                <button type="submit"
                        :disabled="enrollmentEditor.saving"
                        class="inline-flex items-center gap-2 rounded-xl bg-[#1552d4] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600">
                    <i x-show="!enrollmentEditor.saving" data-lucide="save" class="h-4 w-4"></i>
                    <i x-show="enrollmentEditor.saving" data-lucide="loader-2" class="h-4 w-4 animate-spin" x-cloak></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <div x-show="idPreview.open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
         @click.self="closeIdPreview()"
         @keydown.escape.window="closeIdPreview()">
        <div class="flex max-h-full w-full max-w-3xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#101a2d] shadow-2xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <div>
                    <h3 class="text-base font-extrabold text-white">ID Preview</h3>
                    <p class="text-xs text-slate-400">Preview only. Use Generate ID to download the JPEG.</p>
                </div>
                <button type="button"
                        @click="closeIdPreview()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div class="overflow-auto p-5">
                <template x-if="idPreview.loading">
                    <div class="flex min-h-72 items-center justify-center text-sm font-semibold text-slate-300">
                        Rendering preview...
                    </div>
                </template>
                <template x-if="!idPreview.loading && idPreview.image">
                    <div class="grid gap-4 md:grid-cols-2">
                        <template x-for="side in idPreview.sides" :key="side.side">
                            <div>
                                <p class="mb-2 text-center text-xs font-bold uppercase tracking-wide text-slate-300" x-text="side.side"></p>
                                <img :src="side.image"
                                     :alt="`${side.side} ID preview`"
                                     class="mx-auto max-h-[72vh] max-w-full rounded-2xl bg-white shadow-xl">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Scheduling --}}
    <section x-show="activeTab === 'scheduling'" x-cloak x-data="{ showScheduleReports: false }">
        <div class="academic-config-frame overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
            <div class="border-b border-white/10 px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Academic Setup</p>
                        <h2 class="mt-1 text-xl font-extrabold text-white">Scheduling</h2>
                        <p class="mt-1 text-xs text-slate-300">Manage schedule options, subject schedules, rooms, and assigned schedules.</p>
                    </div>
                    <button type="button"
                            @click="showScheduleReports = !showScheduleReports"
                            :class="showScheduleReports ? 'border-violet-300/30 bg-violet-500/25 text-violet-50' : 'border-violet-300/20 bg-violet-500/15 text-violet-100 hover:bg-violet-500/25'"
                            class="group inline-flex items-center justify-center gap-2 rounded-lg border px-4 py-2.5 text-sm font-bold transition">
                        <i data-lucide="files" class="h-4 w-4"></i>
                        <span>Generate Reports</span>
                        <i data-lucide="chevron-down"
                           :class="showScheduleReports ? 'rotate-180' : ''"
                           class="h-4 w-4 transition"></i>
                    </button>
                </div>

                <div x-show="showScheduleReports"
                     x-transition
                     x-cloak
                     class="mt-4 grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 xl:grid-cols-3">
                    <form action="{{ route('academic.schedules.pdf') }}"
                          method="GET"
                          target="_blank"
                          class="grid gap-2 rounded-xl border border-blue-300/10 bg-blue-500/10 p-3">
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-blue-100">Class Schedule</p>
                        <div class="grid gap-2 sm:grid-cols-3 xl:grid-cols-1 2xl:grid-cols-3">
                            <select name="course_code" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Course</option>
                                @foreach($subjects->pluck('course_code')->filter()->unique()->sort()->values() as $courseCode)
                                    <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                                @endforeach
                            </select>
                            <select name="year_level" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Year</option>
                                @foreach($subjects->pluck('year_level')->filter()->unique()->sort()->values() as $yearLevel)
                                    <option value="{{ $yearLevel }}">Year {{ $yearLevel }}</option>
                                @endforeach
                            </select>
                            <select name="semester" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Semester</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-300/20 bg-blue-500/20 px-4 py-2 text-sm font-bold text-blue-100 transition hover:bg-blue-500/30">
                            <i data-lucide="file-down" class="h-4 w-4"></i>
                            Download DOCX
                        </button>
                    </form>

                    <form action="{{ route('academic.schedules.instructor') }}"
                          method="GET"
                          target="_blank"
                          class="grid gap-2 rounded-xl border border-emerald-300/10 bg-emerald-500/10 p-3">
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-emerald-100">Faculty Schedule</p>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            <select name="instructor" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Select instructor</option>
                                @foreach($scheduleInstructorOptions as $instructor)
                                    <option value="{{ $instructor }}">{{ $instructor }}</option>
                                @endforeach
                            </select>
                            <select name="semester" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Semester</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-300/20 bg-emerald-500/20 px-4 py-2 text-sm font-bold text-emerald-100 transition hover:bg-emerald-500/30">
                            <i data-lucide="file-user" class="h-4 w-4"></i>
                            Download DOCX
                        </button>
                    </form>

                    <form action="{{ route('academic.schedules.room') }}"
                          method="GET"
                          target="_blank"
                          class="grid gap-2 rounded-xl border border-cyan-300/10 bg-cyan-500/10 p-3">
                        <p class="text-xs font-extrabold uppercase tracking-[0.14em] text-cyan-100">Room Schedule</p>
                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                            <select name="room_name" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Select room</option>
                                @foreach($currentScheduleRooms as $room)
                                    <option value="{{ $room->name }}">{{ $room->name }}</option>
                                @endforeach
                            </select>
                            <select name="semester" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="">Semester</option>
                                <option value="1st">1st Semester</option>
                                <option value="2nd">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-cyan-300/20 bg-cyan-500/20 px-4 py-2 text-sm font-bold text-cyan-100 transition hover:bg-cyan-500/30">
                            <i data-lucide="door-open" class="h-4 w-4"></i>
                            Download DOCX
                        </button>
                    </form>
                </div>
            </div>

            <div class="p-5">
                <section class="grid grid-cols-12 gap-5">
                    @include('dashboard.partials.academic-scheduling')
                </section>
            </div>
        </div>
    </section>

    @if(in_array(auth()->user()->user_type, ['admin', 'registrar', 'department_head'], true))
        <section x-show="activeTab === 'configuration'" x-cloak>
            @include('dashboard.partials.academic-configuration')
        </section>
    @endif
</div>

@endsection

@push('scripts')
<style>
    @foreach($idFonts ?? [] as $font)
        @font-face {
            font-family: @js($font['family']);
            src: url(@js($font['url'])) format('{{ $font['extension'] === 'otf' ? 'opentype' : ($font['extension'] === 'ttf' ? 'truetype' : $font['extension']) }}');
            font-style: normal;
            font-weight: 100 900;
            font-display: swap;
        }
    @endforeach
</style>

<script>
    window.appData = {
        chartData: @json($chartData),
        user: @json(auth()->user()),
    };

    window.dirtyFormState = function () {
        return {
            initialSnapshot: '',
            dirty: false,
            init() {
                this.$nextTick(() => this.markClean());
                this.$el.addEventListener('input', () => this.refreshDirty());
                this.$el.addEventListener('change', () => this.refreshDirty());
            },
            snapshot() {
                const values = [];
                const formData = new FormData(this.$el);

                for (const [key, value] of formData.entries()) {
                    if (['_token', '_method'].includes(key)) continue;

                    if (value instanceof File) {
                        values.push([key, value.name, value.size, value.lastModified]);
                    } else {
                        values.push([key, String(value)]);
                    }
                }

                return JSON.stringify(values.sort((a, b) => String(a[0]).localeCompare(String(b[0]))));
            },
            refreshDirty() {
                this.dirty = this.snapshot() !== this.initialSnapshot;
            },
            markClean() {
                this.initialSnapshot = this.snapshot();
                this.dirty = false;
            },
        };
    };

    window.dirtyForm = function () {
        return window.dirtyFormState();
    };

    window.dashboardResponseErrorMessage = function (data, fallback) {
        const messages = [];

        if (data?.message) {
            messages.push(data.message);
        }

        if (data?.errors && typeof data.errors === 'object') {
            Object.values(data.errors).forEach((fieldErrors) => {
                const entries = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
                entries.filter(Boolean).forEach((entry) => messages.push(entry));
            });
        }

        return [...new Set(messages)].join(' ') || fallback;
    };

    window.dashboardFrame = function () {
        return {
            activeTab: 'overview',
            previousTab: 'overview',
            search: '',
            idSearch: '',
            statusFilter: '',
            archivedEnrollmentYear: '',
            archivedEnrollmentYears: @json($archivedEnrollmentYears),
            idGenerationFilter: '',
            stats: @json($stats),
            academicYear: @json($academicYear),
            enrollmentCount: {{ $allEnrollments->count() }},
            enrollmentStatuses: @json($allEnrollments->pluck('enrollment_status', 'id')),
            enrollmentRefreshUrl: @json(route('dashboard.enrollments.live')),
            enrollmentRefreshTimer: null,
            idGenerationStatuses: @json($idGenerationStatuses),
            idGenerationStatusUrl: @json(route('id-generation.statuses')),
            idGenerationRefreshTimer: null,
            idFonts: @json($idFonts ?? []),
            registeredIdFonts: {},
            idPreview: {
                open: false,
                loading: false,
                image: '',
                sides: [],
            },
            enrollmentEditor: {
                open: false,
                saving: false,
                url: '',
                form: {},
            },
            subjectCount: {{ $subjects->count() }},
            scheduleSubjectOptions: @json($scheduleSubjectOptions),
            addedSubjects: [],
            addedDays: [],
            addedRooms: [],
            addedTimeSlots: [],
            addedSchedules: [],
            scheduleInstructorOptions: @json($scheduleInstructorOptions),
            scheduleForOptions: @json($scheduleForOptions),
            scheduleRows: @json($scheduleRows),
            archivedScheduleRows: @json($archivedScheduleRows),
            archivedScheduleYears: @json($archivedScheduleYears),
            scheduleCount: {{ $subjectSchedules->count() }},
            scheduleSearch: '',
            scheduleLiveSearch: '',
            scheduleArchiveYear: '',
            scheduleCourseFilter: '',
            scheduleYearFilter: '',
            scheduleSemesterFilter: '',
            scheduleDayFilter: '',
            confirmingScheduleRemoval: null,
            editingSchedule: null,
            addedDepartmentHeads: [],
            activeDepartmentHeadCourses: @json($departmentHeads->pluck('course_code')->values()),
            departmentHeadCount: {{ $departmentHeads->count() }},
            toasts: [],
            titles: {
                overview: 'Dashboard',
                enrollments: 'Enrollments',
                'id-generation': 'ID Generation',
                scheduling: 'Scheduling',
                configuration: 'Academic Configuration',
                form: 'New Enrollment',
            },
            init() {
                this.registerDashboardIdFonts();
                window.addEventListener('id-font-uploaded', (event) => {
                    const font = event.detail?.font;
                    if (!font) return;

                    this.idFonts = [
                        ...(this.idFonts || []).filter((item) => item.family !== font.family),
                        font,
                    ];
                    this.registerDashboardIdFont(font);
                });
                this.$watch('activeTab', (tab) => {
                    window.dispatchEvent(new CustomEvent('dashboard-tab-changed', { detail: { tab } }));
                    if (['overview', 'enrollments'].includes(tab)) {
                        this.refreshEnrollmentTables();
                    }
                    if (tab === 'id-generation') {
                        this.refreshIdGenerationStatuses();
                    }
                    this.$nextTick(() => window.lucide?.createIcons());
                });
                this.enrollmentRefreshTimer = setInterval(() => {
                    if (['overview', 'enrollments'].includes(this.activeTab)) {
                        this.refreshEnrollmentTables();
                    }
                }, 6000);
                this.idGenerationRefreshTimer = setInterval(() => {
                    if (this.activeTab === 'id-generation') {
                        this.refreshIdGenerationStatuses();
                    }
                }, 7000);
            },
            switchTab(tab) {
                if (tab !== this.activeTab) {
                    this.previousTab = this.activeTab === 'form' ? this.previousTab : this.activeTab;
                    this.activeTab = tab;
                }
            },
            openFormFrame() {
                this.previousTab = this.activeTab === 'form' ? this.previousTab : this.activeTab;
                this.activeTab = 'form';
            },
            openEnrollmentsWithStatus(status) {
                this.search = '';
                this.statusFilter = status;
                this.switchTab('enrollments');
            },
            formatCount(value) {
                return Number(value || 0).toLocaleString();
            },
            adjustStatusStats(oldStatus, newStatus) {
                if (oldStatus === newStatus) return;

                if (oldStatus === 'enrolled') {
                    this.stats.total_enrolled = Math.max(0, Number(this.stats.total_enrolled || 0) - 1);
                }

                if (newStatus === 'enrolled') {
                    this.stats.total_enrolled = Number(this.stats.total_enrolled || 0) + 1;
                }

                if (oldStatus === 'pending') {
                    this.stats.pending = Math.max(0, Number(this.stats.pending || 0) - 1);
                }

                if (newStatus === 'pending') {
                    this.stats.pending = Number(this.stats.pending || 0) + 1;
                }
            },
            setEnrollmentStatus(enrollmentId, oldStatus, newStatus) {
                this.adjustStatusStats(oldStatus, newStatus);
                this.enrollmentStatuses[enrollmentId] = newStatus;
            },
            statusFor(enrollmentId, fallback = 'pending') {
                return this.enrollmentStatuses[enrollmentId] || fallback;
            },
            showToast(type, title, message) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type, title, message: message || 'Something went wrong. Please try again.', visible: true });
                this.$nextTick(() => window.lucide?.createIcons());

                setTimeout(() => this.dismissToast(id), 5000);
            },
            responseErrorMessage(data, fallback) {
                return window.dashboardResponseErrorMessage(data, fallback);
            },
            dismissToast(id) {
                const toast = this.toasts.find((item) => item.id === id);
                if (!toast) return;

                toast.visible = false;
                setTimeout(() => {
                    this.toasts = this.toasts.filter((item) => item.id !== id);
                }, 200);
            },
            async updateEnrollmentStatus(form) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(this.responseErrorMessage(data, 'Unable to update enrollment status. Please refresh the page and try again.'));
                }

                return data.status;
            },
            openEnrollmentEditor(enrollment, url) {
                this.enrollmentEditor = {
                    open: true,
                    saving: false,
                    url,
                    form: {
                        ...enrollment,
                        credentials: enrollment.credentials || [],
                    },
                };
                this.$nextTick(() => window.lucide?.createIcons());
            },
            closeEnrollmentEditor() {
                this.enrollmentEditor.open = false;
                this.enrollmentEditor.saving = false;
                this.enrollmentEditor.url = '';
                this.enrollmentEditor.form = {};
            },
            async saveEnrollmentEditor(form) {
                this.enrollmentEditor.saving = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(this.responseErrorMessage(data, 'Unable to update enrollment. Please check the student details and try again.'));
                    }

                    this.closeEnrollmentEditor();
                    this.showToast('success', 'Enrollment updated', 'Student data was saved.');
                    await this.refreshEnrollmentTables();
                } catch (error) {
                    this.showToast('error', 'Update failed', error.message);
                } finally {
                    this.enrollmentEditor.saving = false;
                }
            },
            async refreshEnrollmentTables() {
                try {
                    const url = new URL(this.enrollmentRefreshUrl, window.location.origin);
                    if (this.archivedEnrollmentYear) {
                        url.searchParams.set('archived_year', this.archivedEnrollmentYear);
                    }

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    if (this.$refs.recentEnrollmentsTable && data.recent_html) {
                        this.$refs.recentEnrollmentsTable.innerHTML = data.recent_html;
                        window.Alpine?.initTree(this.$refs.recentEnrollmentsTable);
                    }

                    if (this.$refs.allEnrollmentsTable && data.all_html) {
                        this.$refs.allEnrollmentsTable.innerHTML = data.all_html;
                        window.Alpine?.initTree(this.$refs.allEnrollmentsTable);
                    }

                    this.enrollmentCount = Number(data.total || 0);
                    this.enrollmentStatuses = {
                        ...(data.statuses || {}),
                    };

                    if (data.stats) {
                        this.stats = {
                            ...this.stats,
                            ...data.stats,
                        };
                    }

                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (error) {
                    // Keep the current table visible if a background refresh fails.
                }
            },
            async submitSubjectForm(form) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(this.responseErrorMessage(data, 'Unable to save subject. Please check the subject details and try again.'));
                }

                return data.subject;
            },
            async deleteSubject(form) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(this.responseErrorMessage(data, 'Unable to remove subject. It may already be used by another record.'));
                }

                return data;
            },
            async submitAcademicForm(form) {
                const targetForm = form instanceof HTMLFormElement ? form : form?.form;

                if (!(targetForm instanceof HTMLFormElement)) {
                    throw new Error('Unable to find the form. Please refresh the page and try again.');
                }

                const response = await fetch(targetForm.action, {
                    method: 'POST',
                    body: new FormData(targetForm),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(this.responseErrorMessage(data, 'Unable to save changes. Please review the highlighted fields and try again.'));
                }

                return response.json();
            },
            async submitScheduleForm(form, overwrite = false) {
                const formData = new FormData(form);

                if (overwrite) {
                    formData.set('overwrite_schedule', '1');
                }

                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json().catch(() => ({}));

                if (response.status === 409 && data.requires_confirmation) {
                    const error = new Error(this.responseErrorMessage(data, 'This subject already has a schedule.'));
                    error.requiresScheduleOverwrite = true;
                    error.schedule = data.schedule || null;
                    throw error;
                }

                if (!response.ok) {
                    throw new Error(this.responseErrorMessage(data, 'Unable to save schedule. Please check the selected day, room, and time slot.'));
                }

                return data;
            },
            setAcademicYear(value) {
                const previousAcademicYear = this.academicYear;
                this.academicYear = value;

                if (previousAcademicYear && previousAcademicYear !== value) {
                    if (!this.archivedEnrollmentYears.includes(previousAcademicYear)) {
                        this.archivedEnrollmentYears = [previousAcademicYear, ...this.archivedEnrollmentYears];
                    }

                    if (!this.archivedScheduleYears.includes(previousAcademicYear)) {
                        this.archivedScheduleYears = [previousAcademicYear, ...this.archivedScheduleYears];
                    }

                    this.archivedEnrollmentYear = '';
                    this.scheduleArchiveYear = '';
                    this.addedSchedules = [];
                    this.scheduleRows = [];
                    this.scheduleCount = 0;
                    this.refreshEnrollmentTables();
                }

                window.dispatchEvent(new CustomEvent('academic-year-updated', {
                    detail: { academicYear: value },
                }));
            },
            async deleteAcademicItem(form) {
                return this.deleteSubject(form);
            },
            addLiveSubject(subject) {
                this.addedSubjects.unshift(subject);
                this.$nextTick(() => window.lucide?.createIcons());
            },
            addDepartmentHead(head) {
                this.addedDepartmentHeads = [
                    head,
                    ...this.addedDepartmentHeads.filter((item) => item.course_code !== head.course_code),
                ];

                if (!this.activeDepartmentHeadCourses.includes(head.course_code)) {
                    this.activeDepartmentHeadCourses.push(head.course_code);
                    this.departmentHeadCount += 1;
                }
            },
            matchesSearch(rowText) {
                return !this.search || rowText.toLowerCase().includes(this.search.toLowerCase());
            },
            matchesEnrollment(rowText, status) {
                const matchesText = !this.search || rowText.toLowerCase().includes(this.search.toLowerCase());
                const matchesStatus = !this.statusFilter || status === this.statusFilter;

                return matchesText && matchesStatus;
            },
            matchesIdGeneration(rowText, enrollmentId) {
                const status = this.enrollmentStatuses[enrollmentId];
                const matchesText = !this.idSearch || rowText.toLowerCase().includes(this.idSearch.toLowerCase());
                const idStatus = this.idStatusFor(enrollmentId);
                const matchesGeneratedFilter = !this.idGenerationFilter
                    || (this.idGenerationFilter === 'generated' && idStatus.generated)
                    || (this.idGenerationFilter === 'pending' && !idStatus.generated);

                return status === 'enrolled' && matchesText && matchesGeneratedFilter;
            },
            allScheduleRows() {
                if (this.scheduleArchiveYear) {
                    return (this.archivedScheduleRows || [])
                        .filter((schedule) => schedule.archived_school_year === this.scheduleArchiveYear);
                }

                return [
                    ...(this.addedSchedules || []),
                    ...(this.scheduleRows || []),
                ];
            },
            scheduleDayCode(day) {
                return {
                    monday: 'M',
                    tuesday: 'T',
                    wednesday: 'W',
                    thursday: 'TH',
                    friday: 'FRI',
                    saturday: 'SAT',
                    sunday: 'SUN',
                }[(day || '').toLowerCase()] || String(day || '').toUpperCase();
            },
            scheduleDayOrder(day) {
                return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].indexOf(day);
            },
            scheduleGroupKey(schedule) {
                return [
                    schedule.subject?.id || '',
                    schedule.schedule_type || '',
                    schedule.start_time || '',
                    schedule.end_time || '',
                    schedule.room_id || schedule.room || '',
                    (schedule.instructor || '').toLowerCase().trim(),
                    (schedule.schedule_for || 'Whole Class').toLowerCase().trim(),
                ].join('|');
            },
            groupScheduleRows(rows) {
                const groups = new Map();

                rows.forEach((schedule) => {
                    const key = this.scheduleGroupKey(schedule);
                    const group = groups.get(key) || { ...schedule, schedule_ids: [], day_ids: [], days: [] };

                    group.schedule_ids.push(schedule.id);
                    group.day_ids.push(schedule.day_id);
                    group.days.push(schedule.day);
                    group.id = Math.min(...group.schedule_ids);
                    group.update_url = schedule.update_url;
                    group.delete_url = schedule.delete_url;
                    groups.set(key, group);
                });

                return [...groups.values()].map((schedule) => {
                    const orderedDays = [...new Set(schedule.days)]
                        .sort((a, b) => this.scheduleDayOrder(a) - this.scheduleDayOrder(b));

                    return {
                        ...schedule,
                        days: orderedDays,
                        day_ids: [...new Set(schedule.day_ids)],
                        day_label: orderedDays.map((day) => this.scheduleDayCode(day)).join(''),
                    };
                });
            },
            filteredScheduleRows() {
                const search = this.scheduleLiveSearch.trim().toLowerCase();

                const filtered = this.allScheduleRows()
                    .filter((schedule) => {
                        const subject = schedule.subject || {};
                        if (this.scheduleCourseFilter && subject.course_code !== this.scheduleCourseFilter) return false;
                        if (this.scheduleYearFilter && subject.year_level !== this.scheduleYearFilter) return false;
                        if (this.scheduleSemesterFilter && subject.semester !== this.scheduleSemesterFilter) return false;
                        if (this.scheduleDayFilter && schedule.day !== this.scheduleDayFilter) return false;

                        if (!search) return true;

                        return [
                            subject.code,
                            subject.name,
                            subject.course_code,
                            subject.year_level,
                            subject.semester,
                            schedule.day,
                            schedule.time,
                            schedule.room,
                            schedule.instructor,
                            schedule.schedule_for,
                        ].join(' ').toLowerCase().includes(search);
                    })
                return this.groupScheduleRows(filtered)
                    .sort((a, b) => `${a.start_time || ''} ${a.subject?.code || ''} ${a.day_label || ''}`.localeCompare(`${b.start_time || ''} ${b.subject?.code || ''} ${b.day_label || ''}`));
            },
            allScheduleSubjectOptions() {
                const liveSubjects = (this.addedSubjects || []).map((subject) => ({
                    id: subject.id,
                    code: subject.code,
                    name: subject.name,
                    course_code: subject.course_code,
                    year_level: subject.year_level,
                    semester: subject.semester,
                    type: subject.type,
                })).flatMap((subject) => {
                    const types = subject.type === 'BOTH' ? ['LEC', 'LAB'] : [subject.type === 'LAB' ? 'LAB' : 'LEC'];

                    return types.map((type) => ({
                        id: subject.id,
                        schedule_type: type,
                        label: `${subject.code} - ${type} / ${subject.name} / ${subject.course_code} / ${subject.year_level} / ${subject.semester}`,
                    }));
                });

                return [
                    ...(this.scheduleSubjectOptions || []),
                    ...liveSubjects.filter((subject) => !(this.scheduleSubjectOptions || []).some((item) => item.id === subject.id && item.schedule_type === subject.schedule_type)),
                ];
            },
            resolveScheduleSubjectSelection(label) {
                return this.allScheduleSubjectOptions().find((subject) => subject.label === label) || null;
            },
            resolveScheduleSubjectId(label) {
                return this.resolveScheduleSubjectSelection(label)?.id || '';
            },
            resolveScheduleType(label) {
                return this.resolveScheduleSubjectSelection(label)?.schedule_type || '';
            },
            scheduleSubjectLabel(subjectId, scheduleType = 'LEC') {
                return this.allScheduleSubjectOptions().find((subject) => Number(subject.id) === Number(subjectId) && subject.schedule_type === scheduleType)?.label || '';
            },
            upsertSchedule(schedule) {
                this.addedSchedules = (this.addedSchedules || []).map((item) => item.id === schedule.id ? schedule : item);
                this.scheduleRows = (this.scheduleRows || []).map((item) => item.id === schedule.id ? schedule : item);

                if (!this.addedSchedules.some((item) => item.id === schedule.id) && !this.scheduleRows.some((item) => item.id === schedule.id)) {
                    this.addedSchedules.unshift(schedule);
                }
            },
            applyScheduleResponse(data) {
                const removedIds = data.removed_schedule_ids || [];

                if (removedIds.length) {
                    this.addedSchedules = (this.addedSchedules || []).filter((schedule) => !removedIds.includes(schedule.id));
                    this.scheduleRows = (this.scheduleRows || []).filter((schedule) => !removedIds.includes(schedule.id));
                }

                (data.schedules || [data.schedule]).filter(Boolean).forEach((schedule) => this.upsertSchedule(schedule));
                (data.schedules || [data.schedule]).filter(Boolean).forEach((schedule) => {
                    const scheduleFor = schedule.schedule_for || 'Whole Class';
                    if (!this.scheduleForOptions.includes(scheduleFor)) {
                        this.scheduleForOptions.push(scheduleFor);
                        this.scheduleForOptions.sort();
                    }

                    const instructor = (schedule.instructor || '').trim();
                    if (instructor && !this.scheduleInstructorOptions.some((item) => item.toLowerCase() === instructor.toLowerCase())) {
                        this.scheduleInstructorOptions.push(instructor);
                        this.scheduleInstructorOptions.sort();
                    }
                });
                this.scheduleCount = (this.scheduleRows || []).length + (this.addedSchedules || []).length;
            },
            idStatusFor(enrollmentId) {
                return this.idGenerationStatuses[enrollmentId] || {
                    requirements_submitted: false,
                    emergency_contact_submitted: false,
                    photo_submitted: false,
                    signature_submitted: false,
                    requirements_status: 'not_submitted',
                    submitted_at: null,
                    generated: false,
                    generated_at: null,
                    status: 'draft',
                };
            },
            async refreshIdGenerationStatuses() {
                try {
                    const response = await fetch(this.idGenerationStatusUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) return;

                    const data = await response.json();
                    this.idGenerationStatuses = {
                        ...this.idGenerationStatuses,
                        ...(data.statuses || {}),
                    };
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (error) {
                    // Keep the current list visible if a background refresh fails.
                }
            },
            async markIdGenerated(url, enrollmentId) {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token || '',
                    },
                });

                if (!response.ok) return;

                const data = await response.json();
                if (data.status) {
                    this.idGenerationStatuses[enrollmentId] = data.status;
                    this.$nextTick(() => window.lucide?.createIcons());
                }
            },
            async uploadIdAsset(event, url, enrollmentId, fieldName = 'photo') {
                const input = event.target;
                const file = input.files?.[0];

                if (!file) return;

                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const formData = new FormData();
                formData.append(fieldName, file);

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token || '',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(this.responseErrorMessage(data, `Unable to upload student ${fieldName === 'signature' ? 'signature' : 'photo'}. Please use a valid image file and try again.`));
                    }

                    if (data.status) {
                        this.idGenerationStatuses[enrollmentId] = data.status;
                    }

                    const label = fieldName === 'signature' ? 'Signature' : 'Photo';
                    this.showToast('success', `${label} uploaded`, `${label} is ready for ID generation.`);
                    this.$nextTick(() => window.lucide?.createIcons());
                } catch (error) {
                    this.showToast('error', 'Upload failed', error.message);
                } finally {
                    input.value = '';
                }
            },
            async buildIdOutput(url) {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(this.responseErrorMessage(data, 'Unable to render ID. Please confirm the student has a mapped template and complete ID assets.'));
                }

                const renderedSides = [];

                for (const template of data.templates || []) {
                    renderedSides.push({
                        side: template.side || `side-${renderedSides.length + 1}`,
                        canvas: await this.renderIdSide(template, data.student),
                    });
                }

                if (renderedSides.length === 0) {
                    throw new Error('No mapped ID template is available.');
                }

                const gap = renderedSides.length > 1 ? 32 : 0;
                const outputWidth = Math.max(...renderedSides.map((item) => item.canvas.width));
                const outputHeight = renderedSides.reduce((sum, item) => sum + item.canvas.height, 0) + gap;
                const output = document.createElement('canvas');
                output.width = outputWidth;
                output.height = outputHeight;
                const context = output.getContext('2d');
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, output.width, output.height);

                let y = 0;
                renderedSides.forEach(({ canvas }, index) => {
                    context.drawImage(canvas, (outputWidth - canvas.width) / 2, y);
                    y += canvas.height + (index === 0 ? gap : 0);
                });

                const baseFileName = (data.student?.file_name || 'student-id.jpg').replace(/\.jpe?g$/i, '');

                return {
                    image: output.toDataURL('image/jpeg', 0.95),
                    fileName: `${baseFileName}.jpg`,
                    baseFileName,
                    sides: renderedSides.map(({ side, canvas }) => ({
                        side,
                        image: canvas.toDataURL('image/jpeg', 0.95),
                        fileName: `${baseFileName}-${side}.jpg`,
                    })),
                };
            },
            async previewIdCard(url) {
                this.idPreview.open = true;
                this.idPreview.loading = true;
                this.idPreview.image = '';
                this.$nextTick(() => window.lucide?.createIcons());

                try {
                    const output = await this.buildIdOutput(url);
                    this.idPreview.image = output.image;
                    this.idPreview.sides = output.sides || [];
                } catch (error) {
                    this.idPreview.open = false;
                    this.showToast('error', 'Preview failed', error.message);
                } finally {
                    this.idPreview.loading = false;
                }
            },
            closeIdPreview() {
                this.idPreview.open = false;
                this.idPreview.loading = false;
                this.idPreview.image = '';
                this.idPreview.sides = [];
            },
            async generateIdCard(url, markUrl = null, enrollmentId = null) {
                try {
                    const output = await this.buildIdOutput(url);
                    const downloads = output.sides?.length ? output.sides : [{ image: output.image, fileName: output.fileName }];

                    if (downloads.length > 1 && window.JSZip) {
                        const zip = new window.JSZip();

                        downloads.forEach((download) => {
                            zip.file(download.fileName, this.dataUrlToBlob(download.image));
                        });

                        const zipBlob = await zip.generateAsync({ type: 'blob' });
                        this.downloadBlob(zipBlob, `${output.baseFileName || 'student-id'}.zip`);
                    } else {
                        this.downloadBlob(this.dataUrlToBlob(downloads[0].image), downloads[0].fileName);
                    }

                    if (markUrl && enrollmentId) {
                        await this.markIdGenerated(markUrl, enrollmentId);
                    }

                    this.showToast('success', 'ID generated', 'JPEG download is ready.');
                } catch (error) {
                    this.showToast('error', 'Generation failed', error.message);
                }
            },
            dataUrlToBlob(dataUrl) {
                const [header, data] = dataUrl.split(',');
                const mime = header.match(/:(.*?);/)?.[1] || 'application/octet-stream';
                const binary = atob(data);
                const bytes = new Uint8Array(binary.length);

                for (let index = 0; index < binary.length; index++) {
                    bytes[index] = binary.charCodeAt(index);
                }

                return new Blob([bytes], { type: mime });
            },
            downloadBlob(blob, fileName) {
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = fileName;
                link.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            },
            async renderIdSide(template, student) {
                const canvas = document.createElement('canvas');
                canvas.width = Number(template.width || 540);
                canvas.height = Number(template.height || 340);
                const context = canvas.getContext('2d');
                context.imageSmoothingEnabled = true;
                context.imageSmoothingQuality = 'high';

                const background = await this.loadCanvasImage(template.background);
                context.drawImage(background, 0, 0, canvas.width, canvas.height);

                for (const field of template.fields || []) {
                    if (field.type === 'image') {
                        const source = field.key === 'signature'
                            ? student.signature
                            : (field.key === 'student_photo' ? student.photo : student.images?.[field.key]);
                        if (!source) continue;

                        const image = await this.loadCanvasImage(source);
                        this.drawMaskedImage(context, image, field);
                    } else {
                        await this.ensureIdFontLoaded(field);
                        this.drawIdText(context, student.fields?.[field.key] || '', field);
                    }
                }

                return canvas;
            },
            loadCanvasImage(source) {
                return new Promise((resolve, reject) => {
                    const image = new Image();
                    image.onload = () => resolve(image);
                    image.onerror = () => reject(new Error('Unable to load ID image assets.'));
                    image.src = source;
                });
            },
            async registerDashboardIdFonts() {
                for (const font of this.idFonts || []) {
                    await this.registerDashboardIdFont(font);
                }
            },
            async registerDashboardIdFont(font) {
                if (!font?.family || !font?.url || !window.FontFace || !document.fonts || this.registeredIdFonts[font.family]) {
                    return;
                }

                try {
                    const face = new FontFace(font.family, `url(${font.url})`, {
                        style: 'normal',
                        weight: '100 900',
                    });
                    const loadedFace = await face.load();
                    document.fonts.add(loadedFace);
                    this.registeredIdFonts[font.family] = true;
                } catch (error) {
                    this.registeredIdFonts[font.family] = false;
                }
            },
            async ensureIdFontLoaded(field) {
                if (!document.fonts || !field.font_family) return;

                try {
                    const font = (this.idFonts || []).find((item) => item.family === field.font_family);
                    if (font) {
                        await this.registerDashboardIdFont(font);
                    }

                    const fontSize = Number(field.font_size || 14);
                    const fontFamily = field.font_family;
                    const fontWeight = field.font_weight || '700';
                    await document.fonts.load(`normal ${fontSize}px "${fontFamily}"`);
                    await document.fonts.load(`${fontWeight} ${fontSize}px "${fontFamily}"`);
                    await document.fonts.ready;
                } catch (error) {
                    // Browser canvas will fall back to Arial if the font cannot load.
                }
            },
            defaultIdTextAlign(key) {
                return ['full_name', 'course_code', 'course_plain_name', 'course_short_name', 'course_full_name'].includes(key)
                    ? 'center'
                    : 'left';
            },
            drawIdText(context, value, field) {
                const text = String(value || '').trim();
                if (!text) return;

                const x = Number(field.x || 0);
                const y = Number(field.y || 0);
                const width = Number(field.width || 120);
                const height = Number(field.height || 24);
                const fontSize = Number(field.font_size || 14);
                const lineHeight = fontSize * 1.12;
                const fontFamily = field.font_family || 'Arial';
                const fontWeight = field.font_weight || '700';

                context.save();
                context.beginPath();
                context.rect(x, y, width, height);
                context.clip();
                context.font = `${fontWeight} ${fontSize}px "${fontFamily}", Arial, sans-serif`;
                context.fillStyle = field.font_color || '#111827';
                context.textBaseline = 'top';
                context.textAlign = field.text_align || this.defaultIdTextAlign(field.key);

                const words = text.split(/\s+/);
                const lines = [];
                let line = '';

                words.forEach((word) => {
                    const testLine = line ? `${line} ${word}` : word;
                    if (context.measureText(testLine).width <= width || !line) {
                        line = testLine;
                    } else {
                        lines.push(line);
                        line = word;
                    }
                });

                if (line) lines.push(line);

                const align = field.text_align || this.defaultIdTextAlign(field.key);
                const textX = align === 'center' ? x + (width / 2) : (align === 'right' ? x + width : x);

                lines.slice(0, Math.max(1, Math.floor(height / lineHeight))).forEach((textLine, index) => {
                    context.fillText(textLine, textX, y + (index * lineHeight));
                });

                context.restore();
            },
            drawMaskedImage(context, image, field) {
                const x = Number(field.x || 0);
                const y = Number(field.y || 0);
                const width = Number(field.width || 120);
                const height = Number(field.height || 120);

                context.save();
                this.applyIdImageMask(context, x, y, width, height, field.shape || 'rectangle');
                context.clip();

                const fit = field.object_fit || 'cover';
                const imageRatio = image.width / image.height;
                const boxRatio = width / height;
                let drawWidth = width;
                let drawHeight = height;

                if ((fit === 'cover' && imageRatio > boxRatio) || (fit === 'contain' && imageRatio < boxRatio)) {
                    drawHeight = height;
                    drawWidth = height * imageRatio;
                } else {
                    drawWidth = width;
                    drawHeight = width / imageRatio;
                }

                const drawX = x + ((width - drawWidth) / 2);
                const drawY = y + ((height - drawHeight) / 2);
                context.drawImage(image, drawX, drawY, drawWidth, drawHeight);
                context.restore();
            },
            applyIdImageMask(context, x, y, width, height, shape) {
                context.beginPath();

                if (shape === 'circle' || shape === 'oval') {
                    context.ellipse(x + width / 2, y + height / 2, width / 2, height / 2, 0, 0, Math.PI * 2);
                    return;
                }

                if (shape === 'hexagon') {
                    context.moveTo(x + width * 0.5, y);
                    context.lineTo(x + width * 0.93, y + height * 0.25);
                    context.lineTo(x + width * 0.93, y + height * 0.75);
                    context.lineTo(x + width * 0.5, y + height);
                    context.lineTo(x + width * 0.07, y + height * 0.75);
                    context.lineTo(x + width * 0.07, y + height * 0.25);
                    context.closePath();
                    return;
                }

                if (shape === 'rounded') {
                    context.roundRect(x, y, width, height, 14);
                    return;
                }

                context.rect(x, y, width, height);
            },
            templateMapper(config) {
                return {
                    template: config.template,
                    fields: config.fields,
                    idTemplate: config.idTemplate,
                    idTemplates: config.idTemplates || { front: config.idTemplate?.side === 'front' ? config.idTemplate : null, back: config.idTemplate?.side === 'back' ? config.idTemplate : null },
                    idTemplateSide: config.idTemplate?.side || (config.idTemplates?.front ? 'front' : 'back'),
                    idFields: config.idFields,
                    idFonts: config.idFonts || [],
                    idFontUploadUrl: config.idFontUploadUrl,
                    customFieldStoreUrl: config.customFieldStoreUrl,
                    templateSection: 'enrollment',
                    selectedField: config.fields[0]?.key || null,
                    selectedIdField: config.idFields[0]?.key || null,
                    mappings: {},
                    idMappings: {},
                    loadingPdf: false,
                    saving: false,
                    idSaving: false,
                    savedMappingSignature: '',
                    idSavedLayoutSignatures: {},
                    isFullscreen: false,
                    idFullscreen: false,
                    isDraggingMarker: false,
                    isDraggingIdMarker: false,
                    suppressNextPlacement: false,
                    suppressNextIdPlacement: false,
                    globalTextSize: 10,
                    idGlobalTextSize: 18,
                    canvasWidth: 0,
                    canvasHeight: 0,
                    idCanvasWidth: 0,
                    idCanvasHeight: 0,
                    idZoom: 1,
                    currentPage: 1,
                    pageCount: 1,
                    renderToken: 0,
                    init() {
                        this.loadMappings();
                        this.captureMappingSignature();
                        this.loadIdLayout();
                        this.captureIdLayoutSignatures();
                        this.$watch('templateSection', (section) => {
                            if (section === 'id') {
                                this.$nextTick(() => {
                                    this.loadIdLayout();
                                    this.refreshIdCanvasSize();
                                    window.lucide?.createIcons();
                                });
                            }
                        });
                        this.$nextTick(() => this.renderPdf());
                    },
                    responseErrorMessage(data, fallback) {
                        return window.dashboardResponseErrorMessage(data, fallback);
                    },
                    loadMappings() {
                        this.mappings = {};
                        const validFieldKeys = new Set(this.fields.map((field) => field.key));

                        (this.template?.field_mappings || []).forEach((mapping) => {
                            if (validFieldKeys.has(mapping.key)) {
                                this.mappings[mapping.key] = { ...mapping, page: Number(mapping.page || 1) };
                            }
                        });
                    },
                    mappingSignature() {
                        return JSON.stringify([...this.mappedFields()].sort((a, b) => a.key.localeCompare(b.key)));
                    },
                    captureMappingSignature() {
                        this.savedMappingSignature = this.mappingSignature();
                    },
                    isMappingDirty() {
                        return this.template?.save_url && this.mappingSignature() !== this.savedMappingSignature;
                    },
                    loadIdLayout() {
                        this.idMappings = {};
                        const validFieldKeys = new Set(this.idFields.map((field) => field.key));
                        this.idCanvasWidth = Number(this.idTemplate?.width || this.idCanvasWidth || 0);
                        this.idCanvasHeight = Number(this.idTemplate?.height || this.idCanvasHeight || 0);

                        (this.idTemplate?.fields || []).forEach((field) => {
                            if (validFieldKeys.has(field.key)) {
                                this.idMappings[field.key] = { ...field };
                            }
                        });
                    },
                    idLayoutSignature(template) {
                        if (!template) return '';

                        const fields = [...(template.fields || [])].sort((a, b) => a.key.localeCompare(b.key));

                        return JSON.stringify({
                            width: Number(template.width || 0),
                            height: Number(template.height || 0),
                            fields,
                        });
                    },
                    captureIdLayoutSignatures() {
                        this.idSavedLayoutSignatures = Object.fromEntries(
                            Object.entries(this.idTemplates || {})
                                .filter(([, template]) => template?.save_url)
                                .map(([side, template]) => [side, this.idLayoutSignature(template)])
                        );
                    },
                    changedIdTemplates() {
                        return Object.values(this.idTemplates || {}).filter((template) => {
                            if (!template?.save_url) return false;

                            return this.idLayoutSignature(template) !== (this.idSavedLayoutSignatures[template.side] || '');
                        });
                    },
                    hasIdLayoutChanges() {
                        if (!this.idTemplate?.save_url) {
                            return this.changedIdTemplates().length > 0;
                        }

                        const currentSideSignature = this.idLayoutSignature({
                            ...this.idTemplate,
                            fields: this.mappedIdFields(),
                        });

                        return currentSideSignature !== (this.idSavedLayoutSignatures[this.idTemplate.side] || '')
                            || this.changedIdTemplates().some((template) => template.side !== this.idTemplate.side);
                    },
                    persistCurrentIdLayout() {
                        if (!this.idTemplate?.side) return;

                        this.idTemplates = {
                            ...this.idTemplates,
                            [this.idTemplate.side]: {
                                ...this.idTemplate,
                                fields: this.mappedIdFields(),
                            },
                        };
                        this.idTemplate = this.idTemplates[this.idTemplate.side];
                    },
                    switchIdTemplateSide(side) {
                        this.persistCurrentIdLayout();
                        this.idTemplateSide = side;
                        this.idTemplate = this.idTemplates[side] || null;
                        this.selectedIdField = this.idFields[0]?.key || null;
                        this.loadIdLayout();
                        this.$nextTick(() => {
                            this.refreshIdCanvasSize();
                            window.lucide?.createIcons();
                        });
                    },
                    async uploadTemplate(form) {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(this.responseErrorMessage(data, 'Unable to upload template. Please choose a valid PDF template and try again.'));
                        }

                        this.template = data.template;
                        this.loadMappings();
                        this.captureMappingSignature();
                        form.reset();
                        await this.$nextTick();
                        await this.renderPdf();
                        window.dispatchEvent(new CustomEvent('dashboard-toast', {
                            detail: { type: 'success', title: 'Template uploaded', message: 'PDF template is ready for mapping.' },
                        }));
                    },
                    async createCustomField(form, scope) {
                        if (!this.customFieldStoreUrl) return;

                        const response = await fetch(this.customFieldStoreUrl, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            window.dispatchEvent(new CustomEvent('dashboard-toast', {
                                detail: { type: 'error', title: 'Field not added', message: this.responseErrorMessage(data, 'Unable to add field. Please enter a unique field label and try again.') },
                            }));
                            return;
                        }

                        if (scope === 'id') {
                            this.idFields = [...this.idFields, data.field];
                            this.selectedIdField = data.field.key;
                        } else {
                            this.fields = [...this.fields, data.field];
                            this.selectedField = data.field.key;
                        }

                        form.reset();
                        window.dispatchEvent(new CustomEvent('dashboard-toast', {
                            detail: { type: 'success', title: 'Field added', message: `${data.field.label} is now available.` },
                        }));
                        this.$nextTick(() => window.lucide?.createIcons());
                    },
                    async uploadIdTemplate(form) {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(this.responseErrorMessage(data, 'Unable to upload ID template. Please choose a valid front or back image and try again.'));
                        }

                        this.idTemplates = {
                            ...this.idTemplates,
                            [data.template.side]: data.template,
                        };
                        this.idTemplateSide = data.template.side;
                        this.idTemplate = data.template;
                        this.loadIdLayout();
                        this.captureIdLayoutSignatures();
                        form.reset();
                        await this.$nextTick();
                        this.refreshIdCanvasSize();
                        window.dispatchEvent(new CustomEvent('dashboard-toast', {
                            detail: { type: 'success', title: 'ID template uploaded', message: 'Background is ready for mapping.' },
                        }));
                    },
                    async uploadIdFont(form) {
                        const response = await fetch(form.action || this.idFontUploadUrl, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            throw new Error(this.responseErrorMessage(data, 'Unable to upload font. Please choose a valid TTF or OTF font file.'));
                        }

                        this.idFonts = [
                            ...this.idFonts.filter((font) => font.family !== data.font.family),
                            data.font,
                        ];
                        if (this.$root?._x_dataStack?.[0]) {
                            const dashboard = this.$root._x_dataStack[0];
                            dashboard.idFonts = [
                                ...(dashboard.idFonts || []).filter((font) => font.family !== data.font.family),
                                data.font,
                            ];
                            await dashboard.registerDashboardIdFont?.(data.font);
                        }
                        await this.registerIdFont(data.font);
                        window.dispatchEvent(new CustomEvent('id-font-uploaded', { detail: { font: data.font } }));
                        form.reset();
                        window.dispatchEvent(new CustomEvent('dashboard-toast', {
                            detail: { type: 'success', title: 'Font uploaded', message: `${data.font.family} is now available.` },
                        }));
                    },
                    async registerIdFont(font) {
                        if (!font?.family || !font?.url || !window.FontFace || !document.fonts) return;

                        try {
                            const face = new FontFace(font.family, `url(${font.url})`);
                            const loadedFace = await face.load();
                            document.fonts.add(loadedFace);
                        } catch (error) {
                            // The select can still show the font; browser CSS fallback handles failed loads.
                        }
                    },
                    async renderPdf() {
                        if (!this.template?.pdf_url || !window.pdfjsLib || !this.$refs.pdfCanvas) return;

                        const token = ++this.renderToken;
                        this.loadingPdf = true;
                        const pdf = await window.pdfjsLib.getDocument(this.template.pdf_url).promise;
                        this.pageCount = pdf.numPages || 1;
                        this.currentPage = Math.min(Math.max(1, this.currentPage), this.pageCount);
                        const page = await pdf.getPage(this.currentPage);
                        const viewport = page.getViewport({ scale: 1.35 });
                        const canvas = this.$refs.pdfCanvas;
                        const context = canvas.getContext('2d');

                        if (token !== this.renderToken) {
                            pdf.destroy?.();
                            return;
                        }

                        canvas.width = viewport.width;
                        canvas.height = viewport.height;
                        canvas.style.width = `${viewport.width}px`;
                        canvas.style.height = `${viewport.height}px`;
                        canvas.style.transform = 'none';
                        canvas.style.transformOrigin = 'top left';

                        context.setTransform(1, 0, 0, 1, 0, 0);
                        context.clearRect(0, 0, canvas.width, canvas.height);

                        this.canvasWidth = viewport.width;
                        this.canvasHeight = viewport.height;

                        await page.render({ canvasContext: context, viewport }).promise;

                        if (token === this.renderToken) {
                            context.setTransform(1, 0, 0, 1, 0, 0);
                            canvas.style.transform = 'none';
                            this.loadingPdf = false;
                        }

                        pdf.destroy?.();
                    },
                    fieldLabel(key) {
                        return this.fields.find((field) => field.key === key)?.label || key;
                    },
                    fieldType(key) {
                        return this.fields.find((field) => field.key === key)?.type || 'text';
                    },
                    idFieldLabel(key) {
                        return this.idFields.find((field) => field.key === key)?.label || key;
                    },
                    idFieldType(key) {
                        return this.idFields.find((field) => field.key === key)?.type || 'text';
                    },
                    selectedTextSize() {
                        return this.mappings[this.selectedField]?.font_size || this.globalTextSize;
                    },
                    selectedIdMapping() {
                        return this.idMappings[this.selectedIdField] || null;
                    },
                    mappedFields() {
                        return Object.values(this.mappings);
                    },
                    mappedIdFields() {
                        return Object.values(this.idMappings);
                    },
                    mappedIdFieldCount() {
                        return this.mappedIdFields().length;
                    },
                    isIdFieldMapped(key) {
                        return this.mappedIdFields().some((field) => field.key === key);
                    },
                    visibleMappedFields() {
                        return this.mappedFields().filter((mapping) => Number(mapping.page || 1) === this.currentPage);
                    },
                    missingFields() {
                        return this.fields.filter((field) => !this.mappings[field.key]);
                    },
                    refreshIdCanvasSize() {
                        if (!this.$refs.idBackground) {
                            this.idCanvasWidth = Number(this.idTemplate?.width || this.idCanvasWidth || 0);
                            this.idCanvasHeight = Number(this.idTemplate?.height || this.idCanvasHeight || 0);
                            return;
                        }

                        const rect = this.$refs.idBackground.getBoundingClientRect();
                        this.idCanvasWidth = rect.width ? rect.width / this.idZoom : Number(this.idTemplate?.width || this.idCanvasWidth || 0);
                        this.idCanvasHeight = rect.height ? rect.height / this.idZoom : Number(this.idTemplate?.height || this.idCanvasHeight || 0);
                    },
                    idStageZoomStyle() {
                        return `zoom: ${this.idZoom};`;
                    },
                    idPointerPoint(event) {
                        const rect = this.$refs.idStage.getBoundingClientRect();

                        return {
                            x: (event.clientX - rect.left) / this.idZoom,
                            y: (event.clientY - rect.top) / this.idZoom,
                        };
                    },
                    zoomIdStage(delta) {
                        this.idZoom = Math.max(0.4, Math.min(3, Number((this.idZoom + delta).toFixed(2))));
                        this.$nextTick(() => this.refreshIdCanvasSize());
                    },
                    zoomIdStageAt(event) {
                        if (!this.idTemplate) return;

                        if (event.shiftKey && !event.ctrlKey) {
                            event.preventDefault();
                            event.currentTarget.scrollLeft += event.deltaY || event.deltaX;
                            return;
                        }

                        if (!event.ctrlKey) return;

                        event.preventDefault();
                        const viewport = event.currentTarget;
                        const rect = viewport.getBoundingClientRect();
                        const offsetX = event.clientX - rect.left;
                        const offsetY = event.clientY - rect.top;
                        const oldZoom = this.idZoom;
                        const nextZoom = Math.max(0.4, Math.min(3, Number((this.idZoom + (event.deltaY < 0 ? 0.1 : -0.1)).toFixed(2))));

                        if (nextZoom === oldZoom) return;

                        const contentX = (viewport.scrollLeft + offsetX) / oldZoom;
                        const contentY = (viewport.scrollTop + offsetY) / oldZoom;
                        this.idZoom = nextZoom;

                        this.$nextTick(() => {
                            viewport.scrollLeft = (contentX * nextZoom) - offsetX;
                            viewport.scrollTop = (contentY * nextZoom) - offsetY;
                            this.refreshIdCanvasSize();
                        });
                    },
                    resetIdZoom() {
                        this.idZoom = 1;
                        this.$nextTick(() => this.refreshIdCanvasSize());
                    },
                    toggleIdFullscreen() {
                        this.idFullscreen = !this.idFullscreen;
                        this.$nextTick(() => {
                            this.refreshIdCanvasSize();
                            window.lucide?.createIcons();
                        });
                    },
                    placeSelected(event) {
                        if (this.isDraggingMarker || this.suppressNextPlacement) return;
                        if (!this.selectedField || !this.template) return;

                        const rect = this.$refs.canvasWrap.getBoundingClientRect();
                        this.setMapping(this.selectedField, event.clientX - rect.left, event.clientY - rect.top);
                    },
                    placeSelectedIdField(event) {
                        if (this.isDraggingIdMarker || this.suppressNextIdPlacement) return;
                        if (!this.idTemplate) return;

                        if (!this.selectedIdField) return;

                        if (this.idMappings[this.selectedIdField]) {
                            this.selectedIdField = null;
                            return;
                        }

                        this.refreshIdCanvasSize();
                        const point = this.idPointerPoint(event);
                        this.setIdMapping(this.selectedIdField, point.x, point.y);
                    },
                    startDrag(event, key) {
                        event.preventDefault();
                        this.selectedField = key;
                        this.isDraggingMarker = true;
                        this.suppressNextPlacement = true;
                        const markerRect = event.currentTarget.getBoundingClientRect();
                        const grabOffsetX = event.clientX - markerRect.left;
                        const grabOffsetY = event.clientY - markerRect.top;

                        const move = (moveEvent) => {
                            const rect = this.$refs.canvasWrap.getBoundingClientRect();
                            this.setMapping(
                                key,
                                moveEvent.clientX - rect.left - grabOffsetX,
                                moveEvent.clientY - rect.top - grabOffsetY
                            );
                        };
                        const stop = () => {
                            window.removeEventListener('pointermove', move);
                            window.removeEventListener('pointerup', stop);
                            this.isDraggingMarker = false;
                            setTimeout(() => {
                                this.suppressNextPlacement = false;
                            }, 0);
                        };

                        window.addEventListener('pointermove', move);
                        window.addEventListener('pointerup', stop);
                    },
                    startIdDrag(event, key) {
                        event.preventDefault();
                        this.selectedIdField = key;
                        this.isDraggingIdMarker = true;
                        this.suppressNextIdPlacement = true;
                        const markerRect = event.currentTarget.getBoundingClientRect();
                        const grabOffsetX = event.clientX - markerRect.left;
                        const grabOffsetY = event.clientY - markerRect.top;

                        const move = (moveEvent) => {
                            this.refreshIdCanvasSize();
                            const point = this.idPointerPoint(moveEvent);
                            this.setIdMapping(
                                key,
                                point.x - (grabOffsetX / this.idZoom),
                                point.y - (grabOffsetY / this.idZoom)
                            );
                        };
                        const stop = () => {
                            window.removeEventListener('pointermove', move);
                            window.removeEventListener('pointerup', stop);
                            this.isDraggingIdMarker = false;
                            setTimeout(() => {
                                this.suppressNextIdPlacement = false;
                            }, 0);
                        };

                        window.addEventListener('pointermove', move);
                        window.addEventListener('pointerup', stop);
                    },
                    startIdResize(event, key, direction) {
                        event.preventDefault();
                        this.selectedIdField = key;
                        this.isDraggingIdMarker = true;
                        this.suppressNextIdPlacement = true;
                        this.refreshIdCanvasSize();

                        const startX = event.clientX;
                        const startY = event.clientY;
                        const start = { ...this.idMappings[key] };
                        const minSize = 8;

                        const move = (moveEvent) => {
                            if (!this.idTemplate || !this.idCanvasWidth || !this.idCanvasHeight) return;

                            const deltaX = (((moveEvent.clientX - startX) / this.idZoom) / this.idCanvasWidth) * this.idTemplate.width;
                            const deltaY = (((moveEvent.clientY - startY) / this.idZoom) / this.idCanvasHeight) * this.idTemplate.height;
                            let nextX = Number(start.x || 0);
                            let nextY = Number(start.y || 0);
                            let nextWidth = Number(start.width || 120);
                            let nextHeight = Number(start.height || 140);

                            if (direction.includes('e')) {
                                nextWidth = Math.max(minSize, Number(start.width || 120) + deltaX);
                            }

                            if (direction.includes('s')) {
                                nextHeight = Math.max(minSize, Number(start.height || 140) + deltaY);
                            }

                            if (direction.includes('w')) {
                                nextWidth = Math.max(minSize, Number(start.width || 120) - deltaX);
                                nextX = Number(start.x || 0) + (Number(start.width || 120) - nextWidth);
                            }

                            if (direction.includes('n')) {
                                nextHeight = Math.max(minSize, Number(start.height || 140) - deltaY);
                                nextY = Number(start.y || 0) + (Number(start.height || 140) - nextHeight);
                            }

                            nextX = Math.max(0, Math.min(nextX, this.idTemplate.width - minSize));
                            nextY = Math.max(0, Math.min(nextY, this.idTemplate.height - minSize));
                            nextWidth = Math.max(minSize, Math.min(nextWidth, this.idTemplate.width - nextX));
                            nextHeight = Math.max(minSize, Math.min(nextHeight, this.idTemplate.height - nextY));

                            this.idMappings = {
                                ...this.idMappings,
                                [key]: {
                                    ...this.idMappings[key],
                                    x: Number(nextX.toFixed(2)),
                                    y: Number(nextY.toFixed(2)),
                                    width: Number(nextWidth.toFixed(2)),
                                    height: Number(nextHeight.toFixed(2)),
                                },
                            };
                        };
                        const stop = () => {
                            window.removeEventListener('pointermove', move);
                            window.removeEventListener('pointerup', stop);
                            this.isDraggingIdMarker = false;
                            setTimeout(() => {
                                this.suppressNextIdPlacement = false;
                            }, 0);
                        };

                        window.addEventListener('pointermove', move);
                        window.addEventListener('pointerup', stop);
                    },
                    setMapping(key, canvasX, canvasY) {
                        if (!this.template || !this.canvasWidth || !this.canvasHeight) return;

                        const field = this.fields.find((item) => item.key === key);
                        const clampedX = Math.max(0, Math.min(canvasX, this.canvasWidth));
                        const clampedY = Math.max(0, Math.min(canvasY, this.canvasHeight));

                        this.mappings[key] = {
                            key,
                            label: field?.label || key,
                            type: field?.type || 'text',
                            x: Number(((clampedX / this.canvasWidth) * this.template.page_width).toFixed(2)),
                            y: Number(((clampedY / this.canvasHeight) * this.template.page_height).toFixed(2)),
                            page: this.currentPage,
                            font_size: this.selectedTextSize(),
                        };
                    },
                    setIdMapping(key, canvasX, canvasY) {
                        if (!this.idTemplate || !this.idCanvasWidth || !this.idCanvasHeight) return;

                        const field = this.idFields.find((item) => item.key === key);
                        const type = field?.type || 'text';
                        const existing = this.idMappings[key] || {};
                        const width = Number(existing.width || field?.width || (type === 'image' ? 120 : 180));
                        const height = Number(existing.height || field?.height || (type === 'image' ? 140 : 28));
                        const clampedX = Math.max(0, Math.min(canvasX, this.idCanvasWidth));
                        const clampedY = Math.max(0, Math.min(canvasY, this.idCanvasHeight));

                        this.idMappings[key] = {
                            key,
                            label: field?.label || key,
                            type,
                            x: Number(((clampedX / this.idCanvasWidth) * this.idTemplate.width).toFixed(2)),
                            y: Number(((clampedY / this.idCanvasHeight) * this.idTemplate.height).toFixed(2)),
                            width,
                            height,
                            font_size: Number(existing.font_size || field?.font_size || this.idGlobalTextSize),
                            font_family: existing.font_family || field?.font_family || 'Arial',
                            font_weight: existing.font_weight || field?.font_weight || '700',
                            font_color: existing.font_color || field?.font_color || '#111827',
                            text_align: existing.text_align || field?.text_align || this.defaultIdTextAlign(key),
                            shape: field?.locked_shape ? 'rectangle' : (existing.shape || field?.shape || 'rectangle'),
                            object_fit: existing.object_fit || field?.object_fit || 'cover',
                            locked_shape: Boolean(existing.locked_shape || field?.locked_shape),
                        };
                    },
                    markerStyle(mapping) {
                        if (!this.template || !this.canvasWidth || !this.canvasHeight) return '';

                        const left = (mapping.x / this.template.page_width) * this.canvasWidth;
                        const top = (mapping.y / this.template.page_height) * this.canvasHeight;

                        const scale = this.canvasWidth / this.template.page_width;
                        const pointsToPageUnits = 25.4 / 72;
                        const fontSize = Math.max(1, Number(mapping.font_size || this.globalTextSize) * pointsToPageUnits * scale);

                        return `left: ${left}px; top: ${top}px; font-size: ${fontSize}px; line-height: 1;`;
                    },
                    idMarkerStyle(mapping) {
                        if (!this.idTemplate || !this.idCanvasWidth || !this.idCanvasHeight) return '';

                        const left = (mapping.x / this.idTemplate.width) * this.idCanvasWidth;
                        const top = (mapping.y / this.idTemplate.height) * this.idCanvasHeight;
                        const width = (mapping.width / this.idTemplate.width) * this.idCanvasWidth;
                        const height = (mapping.height / this.idTemplate.height) * this.idCanvasHeight;
                        const fontSize = (Number(mapping.font_size || this.idGlobalTextSize) / this.idTemplate.height) * this.idCanvasHeight;
                        const lineHeight = mapping.type === 'text' ? 1.12 : 1;

                        return `left: ${left}px; top: ${top}px; width: ${width}px; height: ${height}px; font-size: ${fontSize}px; line-height: ${lineHeight}; font-family: ${mapping.font_family || 'Arial'}; font-weight: ${mapping.font_weight || '700'}; color: ${mapping.font_color || '#111827'};`;
                    },
                    idTextBoxStyle(mapping) {
                        const selected = this.selectedIdField === mapping.key;

                        return [
                            'display: block',
                            'width: 100%',
                            'height: 100%',
                            'overflow: hidden',
                            'white-space: normal',
                            'overflow-wrap: anywhere',
                            'word-break: normal',
                            'box-sizing: border-box',
                            'padding: 1px 2px',
                            `color: ${mapping.font_color || '#111827'}`,
                            `text-align: ${mapping.text_align || this.defaultIdTextAlign(mapping.key)}`,
                            `border: 1px ${selected ? 'solid rgba(21, 82, 212, 0.95)' : 'dashed rgba(21, 82, 212, 0.65)'}`,
                            `background: ${selected ? 'rgba(21, 82, 212, 0.08)' : 'rgba(255, 255, 255, 0.12)'}`,
                        ].join('; ');
                    },
                    idPhotoMaskStyle(mapping) {
                        const shape = mapping.shape || 'rectangle';
                        const fit = mapping.object_fit || 'cover';
                        const styles = [`object-fit: ${fit}`];

                        if (shape === 'rounded') {
                            styles.push('border-radius: 14px');
                        }

                        if (shape === 'circle') {
                            styles.push('border-radius: 9999px');
                            styles.push('aspect-ratio: 1 / 1');
                        }

                        if (shape === 'oval') {
                            styles.push('border-radius: 9999px');
                        }

                        if (shape === 'hexagon') {
                            styles.push('clip-path: polygon(50% 0%, 93% 25%, 93% 75%, 50% 100%, 7% 75%, 7% 25%)');
                        }

                        return styles.join('; ');
                    },
                    removeMapping(key) {
                        this.mappings = Object.fromEntries(
                            Object.entries(this.mappings).filter(([mappingKey]) => mappingKey !== key)
                        );
                    },
                    removeIdMapping(key) {
                        this.idMappings = Object.fromEntries(
                            Object.entries(this.idMappings).filter(([mappingKey]) => mappingKey !== key)
                        );
                    },
                    toggleFullscreen() {
                        this.isFullscreen = !this.isFullscreen;
                        this.$nextTick(() => window.lucide?.createIcons());
                    },
                    async goToPage(page) {
                        if (!this.template) return;
                        this.currentPage = Math.min(Math.max(1, Number(page || 1)), this.pageCount || 1);
                        await this.renderPdf();
                    },
                    updateSelectedTextSize(value) {
                        const size = Math.max(4, Math.min(40, Number(value || 10)));
                        this.globalTextSize = size;

                        if (!this.selectedField || !this.mappings[this.selectedField]) {
                            return;
                        }

                        this.mappings = {
                            ...this.mappings,
                            [this.selectedField]: {
                                ...this.mappings[this.selectedField],
                                font_size: size,
                            },
                        };
                    },
                    updateSelectedIdField(property, value) {
                        if (!this.selectedIdField || !this.idMappings[this.selectedIdField]) return;
                        if (property === 'shape' && this.idMappings[this.selectedIdField].locked_shape) return;

                        const numericProperties = ['width', 'height', 'font_size'];
                        const parsedValue = numericProperties.includes(property) ? Math.max(1, Number(value || 1)) : value;

                        this.idMappings = {
                            ...this.idMappings,
                            [this.selectedIdField]: {
                                ...this.idMappings[this.selectedIdField],
                                [property]: parsedValue,
                            },
                        };
                    },
                    defaultIdTextAlign(key) {
                        return ['full_name', 'course_code', 'course_plain_name', 'course_short_name', 'course_full_name'].includes(key)
                            ? 'center'
                            : 'left';
                    },
                    async saveMappings() {
                        if (!this.template?.save_url) return;

                        this.saving = true;
                        const response = await fetch(this.template.save_url, {
                            method: 'PUT',
                            body: JSON.stringify({
                                mappings: this.mappedFields(),
                            }),
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        const data = await response.json().catch(() => ({}));
                        this.saving = false;

                        if (!response.ok) {
                            throw new Error(this.responseErrorMessage(data, 'Unable to save mappings. Please check that every mapped field is still inside the template.'));
                        }

                        this.template = data.template;
                        this.loadMappings();
                        this.captureMappingSignature();
                        window.dispatchEvent(new CustomEvent('dashboard-toast', {
                            detail: { type: 'success', title: 'Mappings saved', message: 'Template field positions were saved.' },
                        }));
                    },
                    async saveIdLayout() {
                        this.persistCurrentIdLayout();
                        const templatesToSave = this.changedIdTemplates();

                        if (Object.values(this.idTemplates || {}).filter((template) => template?.save_url).length === 0) {
                            window.dispatchEvent(new CustomEvent('dashboard-toast', {
                                detail: { type: 'error', title: 'No template uploaded', message: 'Upload a front or back ID template before saving.' },
                            }));
                            return;
                        }

                        if (templatesToSave.length === 0) {
                            window.dispatchEvent(new CustomEvent('dashboard-toast', {
                                detail: { type: 'error', title: 'No new mapping detected', message: 'Add, move, resize, or remove a field before saving.' },
                            }));
                            return;
                        }

                        this.idSaving = true;
                        const savedTemplates = [];

                        try {
                            for (const template of templatesToSave) {
                                const response = await fetch(template.save_url, {
                                    method: 'PUT',
                                    body: JSON.stringify({
                                        width: template.width,
                                        height: template.height,
                                        fields: template.fields || [],
                                    }),
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                });

                                const data = await response.json().catch(() => ({}));

                                if (!response.ok) {
                                    throw new Error(this.responseErrorMessage(data, 'Unable to save ID layouts. Please check the mapped fields and try again.'));
                                }

                                savedTemplates.push(data.template);
                            }

                            savedTemplates.forEach((template) => {
                                this.idTemplates = {
                                    ...this.idTemplates,
                                    [template.side]: template,
                                };
                                this.idSavedLayoutSignatures[template.side] = this.idLayoutSignature(template);
                            });
                            this.idTemplate = this.idTemplates[this.idTemplateSide] || null;
                            this.loadIdLayout();
                            window.dispatchEvent(new CustomEvent('dashboard-toast', {
                                detail: {
                                    type: 'success',
                                    title: templatesToSave.length > 1 ? 'ID layouts saved' : 'ID layout saved',
                                    message: `${templatesToSave.map((template) => template.side).join(' and ')} layout ${templatesToSave.length > 1 ? 'were' : 'was'} saved.`,
                                },
                            }));
                        } finally {
                            this.idSaving = false;
                        }
                    },
                };
            },
        };
    };
</script>

@vite(['resources/js/app.js'])
@endpush
