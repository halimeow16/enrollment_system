@extends('layouts.dashboard')

@section('title', 'COMTEQ | Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div x-data="dashboardFrame()" x-init="init()" @dashboard-tab-selected.window="switchTab($event.detail.tab)" class="space-y-5">
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

    <div class="fixed bottom-6 right-6 z-[60] w-[min(360px,calc(100vw-2rem))] space-y-3">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-2 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-2 opacity-0"
                 :class="toast.type === 'success' ? 'border-emerald-300/20 bg-emerald-500/15 text-emerald-50' : 'border-red-300/20 bg-red-500/15 text-red-50'"
                 class="rounded-2xl border px-4 py-3 shadow-2xl shadow-black/30 backdrop-blur">
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

    {{-- Shell header --}}
    <div class="rounded-[28px] border border-white/10 bg-white/10 glass px-6 py-5 shadow-2xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-200">Registrar Workspace</p>
                <h1 class="mt-2 text-3xl font-extrabold text-white" x-text="titles[activeTab]"></h1>
                <p class="mt-1 text-sm text-slate-300">A.Y. 2026-2027 - {{ now()->format('l, F j') }}</p>

            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()->user_type !== 'department_head')
                    <button type="button"
                            @click="openFormFrame()"
                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-[#1552d4] to-[#0f43b0] px-5 py-3 text-sm font-bold text-white shadow-xl shadow-blue-950/20 transition hover:scale-[1.01]">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        New Enrollment
                    </button>
                @endif
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
        @if(auth()->user()->user_type !== 'department_head')
            <iframe title="Enrollment form"
                    src="{{ route('enrollment.create') }}"
                    class="h-[calc(100vh-220px)] min-h-[680px] w-full bg-white"></iframe>
        @else
            <div class="p-10 text-center text-sm text-slate-500">
                Department heads can review enrollment records but cannot create new enrollment forms.
            </div>
        @endif
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
                        <p class="text-xs text-blue-100/70 mt-0.5">Courses</p>
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
                @include('dashboard.partials.enrollment-table', ['enrollments' => $recentEnrollments, 'compact' => true])
            </div>
        </div>
    </section>

    {{-- All enrollments --}}
    <section x-show="activeTab === 'enrollments'" x-cloak class="h-[calc(100vh-170px)] min-h-[560px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
        <div class="px-5 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-white/10">
            <div>
                <h2 class="font-extrabold text-white">All Enrollments</h2>
                <p class="text-xs text-slate-300 mt-0.5">{{ $allEnrollments->count() }} total records from the enrollment table.</p>
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
            </div>
        </div>
        @include('dashboard.partials.enrollment-table', ['enrollments' => $allEnrollments, 'compact' => false])
    </section>

    {{-- ID generation --}}
    <section x-show="activeTab === 'id-generation'" x-cloak class="h-[calc(100vh-170px)] min-h-[520px] overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-6 shadow-2xl shadow-black/30">
        <div class="flex h-full items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-center">
            <div>
                <i data-lucide="badge" class="mx-auto h-10 w-10 text-blue-200"></i>
                <h2 class="mt-4 text-xl font-extrabold text-white">ID Generation</h2>
                <p class="mt-2 text-sm text-slate-300">No ID generation tools yet.</p>
            </div>
        </div>
    </section>

    @if(auth()->user()->user_type === 'admin')
        <section x-show="activeTab === 'configuration'" x-cloak>
            @include('dashboard.partials.academic-configuration')
        </section>
    @endif
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    window.appData = {
        chartData: @json($chartData),
        user: @json(auth()->user()),
    };

    window.dashboardFrame = function () {
        return {
            activeTab: 'overview',
            previousTab: 'overview',
            search: '',
            statusFilter: '',
            stats: @json($stats),
            subjectCount: {{ $subjects->count() }},
            addedSubjects: [],
            addedDays: [],
            addedRooms: [],
            addedTimeSlots: [],
            addedSchedules: [],
            scheduleCount: {{ $subjectSchedules->count() }},
            addedDepartmentHeads: [],
            activeDepartmentHeadCourses: @json($departmentHeads->pluck('course_code')->values()),
            departmentHeadCount: {{ $departmentHeads->count() }},
            toasts: [],
            titles: {
                overview: 'Dashboard',
                enrollments: 'Enrollments',
                'id-generation': 'ID Generation',
                configuration: 'Academic Configuration',
                form: 'New Enrollment',
            },
            init() {
                this.$watch('activeTab', (tab) => {
                    window.dispatchEvent(new CustomEvent('dashboard-tab-changed', { detail: { tab } }));
                    this.$nextTick(() => window.lucide?.createIcons());
                });
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
            showToast(type, title, message) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, type, title, message, visible: true });
                this.$nextTick(() => window.lucide?.createIcons());

                setTimeout(() => this.dismissToast(id), 3200);
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

                if (!response.ok) {
                    throw new Error('Unable to update enrollment status.');
                }

                const data = await response.json();
                return data.status;
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

                if (!response.ok) {
                    throw new Error('Unable to save subject.');
                }

                const data = await response.json();
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

                if (!response.ok) {
                    throw new Error('Unable to remove subject.');
                }

                return response.json();
            },
            async submitAcademicForm(form) {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message || 'Unable to save changes.');
                }

                return response.json();
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
        };
    };
</script>

@vite(['resources/js/app.js'])
@endpush
