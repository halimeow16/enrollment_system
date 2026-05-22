<style>
    .academic-config-frame input,
    .academic-config-frame select {
        border-color: rgba(255, 255, 255, 0.12);
        background: rgba(255, 255, 255, 0.08);
        color: #f8fafc;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        transition: border-color 160ms ease, background-color 160ms ease, box-shadow 160ms ease;
    }

    .academic-config-frame input::placeholder {
        color: #94a3b8;
    }

    .academic-config-frame input:focus,
    .academic-config-frame select:focus {
        border-color: rgba(147, 197, 253, 0.45);
        background: rgba(255, 255, 255, 0.12);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        outline: none;
    }

    .academic-config-frame option {
        background: #ffffff;
        color: #0f172a;
    }

    .academic-config-frame input[type="time"] {
        color-scheme: dark;
    }
</style>

@php
    $canManageAdminConfiguration = in_array(auth()->user()?->user_type, ['admin', 'registrar'], true);
@endphp

<div x-data="{ section: 'settings', course: '', year: '', semester: '', scheduleSearch: '', feeCourse: @js($feeRows->first()['course_code'] ?? ''), editingSubject: null, confirmingSubjectRemoval: null, confirmingScheduleRemoval: null, academicYear: @js($academicYear ?? '2026-2027') }"
     class="academic-config-frame overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
    <div class="border-b border-white/10 px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Academic Setup</p>
                <h2 class="mt-1 text-xl font-extrabold text-white">Configuration</h2>
                <p class="mt-1 text-xs text-slate-300">Manage curriculum, schedules, templates, and permitted school settings in one workspace.</p>
            </div>

            <div class="grid w-full grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-white/5 p-1 xl:w-auto {{ $canManageAdminConfiguration ? 'md:grid-cols-5 xl:min-w-[580px]' : 'md:grid-cols-3 xl:min-w-[380px]' }}">
                <button type="button"
                        @click="section = 'settings'"
                        :class="section === 'settings' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Settings
                </button>
                <button type="button"
                        @click="section = 'subjects'"
                        :class="section === 'subjects' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Subjects
                </button>
                @if($canManageAdminConfiguration)
                    <button type="button"
                            @click="section = 'heads'"
                            :class="section === 'heads' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                            class="rounded-xl px-3 py-2 text-xs font-bold transition">
                        Dept. Heads
                    </button>
                    <button type="button"
                            @click="section = 'fees'"
                            :class="section === 'fees' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                            class="rounded-xl px-3 py-2 text-xs font-bold transition">
                        Fees
                    </button>
                @endif
                <button type="button"
                        @click="section = 'templates'"
                        :class="section === 'templates' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Templates
                </button>
            </div>
        </div>
    </div>

    <div class="max-h-[calc(100vh-245px)] overflow-y-auto p-5">
        <section x-show="section === 'settings'" x-cloak class="grid grid-cols-12 gap-5">
            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-5 xl:col-span-5">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">School Term</p>
                    <h3 class="mt-1 text-lg font-extrabold text-white">Academic Year</h3>
                    <p class="mt-1 text-xs leading-5 text-slate-300">This value is used on the dashboard and locked into new enrollment forms.</p>
                </div>

                <form action="{{ route('academic.academic-year.update') }}"
                      method="POST"
                      x-data="dirtyForm()"
                      class="mt-5 space-y-4"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { academicYear = data.academic_year; setAcademicYear(data.academic_year); markClean(); showToast('success', 'Academic year updated', `A.Y. ${data.academic_year} is now active.`); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    @method('PUT')

                    <label class="block text-xs font-semibold text-slate-300">
                        Active Academic Year
                        <input name="academic_year"
                               x-model="academicYear"
                               required
                               pattern="\d{4}-\d{4}"
                               placeholder="2026-2027"
                               class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">
                    </label>

                    <button type="submit"
                            :disabled="!dirty"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#1552d4] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Save Academic Year
                    </button>
                </form>
            </div>

            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-5 xl:col-span-7">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Current Behavior</p>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-bold text-slate-300">Dashboard</p>
                        <p class="mt-2 text-lg font-extrabold text-white">A.Y. <span x-text="academicYear"></span></p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs font-bold text-slate-300">Enrollment Form</p>
                        <p class="mt-2 text-sm font-semibold text-slate-200">School year is read-only.</p>
                    </div>
                </div>
            </div>
        </section>

        <section x-show="section === 'subjects'" x-cloak class="grid grid-cols-12 gap-5">
            <aside class="col-span-12 flex h-[535px] flex-col rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-4">
                <div class="mb-4">
                    <h3 class="font-extrabold text-white">Add Subject</h3>
                    <p class="mt-1 text-xs text-slate-300">Set the course, year, semester, type, and units.</p>
                </div>

                <form action="{{ route('academic.subjects.store') }}"
                      method="POST"
                      x-data="dirtyForm()"
                      @submit.prevent="submitSubjectForm($event.target)
                          .then((subject) => { addLiveSubject(subject); subjectCount++; $event.target.reset(); $nextTick(() => markClean()); showToast('success', 'Subject added', 'Subject was saved and added to the list.'); })
                          .catch(() => showToast('error', 'Save failed', 'Unable to add subject. Please check the fields.'))"
                      class="grid flex-1 grid-cols-1 content-between gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    @csrf
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Code</label>
                        <input name="code" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Subject Name</label>
                        <input name="name" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Course</label>
                        <select name="course_code" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            @foreach(['BSIT', 'BSCS', 'ACT', 'BSBA', 'BSOM', 'BSA'] as $courseCode)
                                <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Year Level</label>
                        <select name="year_level" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            @foreach(['1', '2', '3', '4'] as $yearOption)
                                <option value="{{ $yearOption }}">{{ $yearOption }} Year</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Semester</label>
                        <select name="semester" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Type</label>
                        <select name="type" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            <option value="LEC">Lecture</option>
                            <option value="LAB">Laboratory</option>
                            <option value="BOTH">Both</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Lecture Units</label>
                        <input type="number" step="1" min="0" name="lecture_units" value="3" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-300">
                        <p class="font-semibold text-slate-200">Lab Units</p>
                        <p class="mt-1">Auto: 1 for Lab/Both, 0 for Lecture.</p>
                    </div>
                    <div class="sm:col-span-2 xl:col-span-1 2xl:col-span-2">
                        <button :disabled="!dirty" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Save Subject
                        </button>
                    </div>
                </form>
            </aside>

            <div class="col-span-12 max-h-[535px] overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-8">
                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="font-extrabold text-white">Subjects</h3>
                        <p class="mt-1 text-xs text-slate-300"><span x-text="subjectCount"></span> configured subjects.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[520px]">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Course</label>
                            <select x-model="course" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white outline-none focus:border-blue-300/40">
                                <option class="text-slate-900" value="">All</option>
                                @foreach($subjects->pluck('course_code')->unique()->sort()->values() as $courseCode)
                                    <option class="text-slate-900" value="{{ $courseCode }}">{{ $courseCode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Year</label>
                            <select x-model="year" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white outline-none focus:border-blue-300/40">
                                <option class="text-slate-900" value="">All</option>
                                @foreach(['1', '2', '3', '4'] as $yearLevel)
                                    <option class="text-slate-900" value="{{ $yearLevel }}">Year {{ $yearLevel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Semester</label>
                            <select x-model="semester" class="mt-1 w-full rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white outline-none focus:border-blue-300/40">
                                <option class="text-slate-900" value="">All</option>
                                @foreach(['1st', '2nd', 'Summer'] as $semesterName)
                                    <option class="text-slate-900" value="{{ $semesterName }}">{{ $semesterName }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="button"
                                @click="course = ''; year = ''; semester = ''"
                                class="mt-4 rounded-2xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-white/15 sm:mt-5">
                            Clear
                        </button>
                    </div>
                </div>

                <div class="h-[410px] overflow-auto rounded-2xl border border-white/10">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-[#142039]/95 text-xs uppercase text-slate-300 backdrop-blur">
                            <tr>
                                <th class="px-3 py-2 text-left">Subject</th>
                                <th class="px-3 py-2 text-left">Group</th>
                                <th class="px-3 py-2 text-left">Units</th>
                                <th class="px-3 py-2 text-left">Schedule</th>
                                <th class="px-3 py-2 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <template x-for="subject in addedSubjects" :key="subject.id">
                                <tr x-show="(!course || course === subject.course_code) && (!year || year === subject.year_level) && (!semester || semester === subject.semester)">
                                    <td class="px-3 py-3">
                                        <p class="font-bold text-white" x-text="subject.code"></p>
                                        <p class="text-xs text-slate-400" x-text="subject.name"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        <span x-text="`${subject.course_code} / Year ${subject.year_level} / ${subject.semester}`"></span>
                                        <p class="mt-1 font-semibold text-blue-100" x-text="subject.type"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        <span x-text="`${Number(subject.total_units || 0)} total`"></span>
                                        <p class="text-slate-500" x-text="`${Number(subject.lecture_units || 0)} LEC / ${Number(subject.laboratory_units || 0)} LAB`"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        <span class="text-slate-500">No schedule</span>
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <button type="button"
                                                @click="editingSubject = subject.id; $nextTick(() => window.lucide?.createIcons())"
                                                class="inline-flex items-center gap-1.5 rounded-2xl border border-blue-300/20 bg-blue-500/15 px-3 py-1.5 text-xs font-bold text-blue-100 transition hover:border-blue-200/40 hover:bg-blue-500/25">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </button>

                                        <div x-show="editingSubject === subject.id"
                                             x-cloak
                                             x-transition.opacity
                                             @keydown.escape.window="editingSubject = null"
                                             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm">
                                            <div @click.outside="editingSubject = null"
                                                 x-transition.scale.origin.center
                                                 class="w-full max-w-2xl rounded-3xl border border-white/10 bg-[#111c34] p-5 text-left shadow-2xl shadow-black/50">
                                                <div class="mb-4 flex items-start justify-between gap-4 border-b border-white/10 pb-4">
                                                    <div>
                                                        <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Edit Subject</p>
                                                        <p class="mt-1 text-lg font-extrabold text-white" x-text="subject.code"></p>
                                                        <p class="mt-0.5 text-xs text-slate-400" x-text="subject.name"></p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-bold text-slate-300" x-text="subject.course_code"></span>
                                                        <button type="button"
                                                                @click="editingSubject = null"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-white">
                                                            <i data-lucide="x" class="h-4 w-4"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <form :action="`{{ url('/academic-configuration/subjects') }}/${subject.id}`"
                                                      method="POST"
                                                      x-data="dirtyForm()"
                                                      @submit.prevent="submitSubjectForm($event.target)
                                                          .then((updatedSubject) => { Object.assign(subject, updatedSubject); markClean(); editingSubject = null; showToast('success', 'Subject updated', 'Subject changes were saved.'); })
                                                          .catch(() => showToast('error', 'Update failed', 'Unable to update subject. Please check the fields.'))"
                                                      class="grid grid-cols-2 gap-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="code" :value="subject.code" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="name" :value="subject.name" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <select name="course_code" :value="subject.course_code" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSBA', 'BSOM', 'BSA'] as $courseCode)
                                                            <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="year_level" :value="subject.year_level" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['1', '2', '3', '4'] as $yearOption)
                                                            <option value="{{ $yearOption }}">Year {{ $yearOption }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="semester" :value="subject.semester" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['1st', '2nd', 'Summer'] as $semesterOption)
                                                            <option value="{{ $semesterOption }}">{{ $semesterOption }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="type" :value="subject.type" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['LEC', 'LAB', 'BOTH'] as $type)
                                                            <option value="{{ $type }}">{{ $type }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="number" step="1" min="0" name="lecture_units" :value="subject.lecture_units" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-300">
                                                        <p class="font-semibold text-slate-200">Lab Units</p>
                                                        <p>Auto: 1 for Lab/Both, 0 for Lecture.</p>
                                                    </div>
                                                    <div class="col-span-2 flex justify-between gap-2">
                                                        <button type="button"
                                                                @click="confirmingSubjectRemoval = subject.id"
                                                                class="rounded-lg border border-red-300/20 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-200 transition hover:bg-red-500/20">
                                                            Remove subject
                                                        </button>
                                                        <div class="flex gap-2">
                                                        <button type="button"
                                                                @click="editingSubject = null"
                                                                class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                            Cancel
                                                        </button>
                                                        <button :disabled="!dirty" class="rounded-lg bg-[#1552d4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">Update Subject</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <form :action="`{{ url('/academic-configuration/subjects') }}/${subject.id}`"
                                                      method="POST"
                                                      x-show="confirmingSubjectRemoval === subject.id"
                                                      x-transition
                                                      class="mt-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3"
                                                      @submit.prevent="deleteSubject($event.target)
                                                          .then(() => { addedSubjects = addedSubjects.filter((item) => item.id !== subject.id); subjectCount = Math.max(0, subjectCount - 1); editingSubject = null; confirmingSubjectRemoval = null; showToast('success', 'Subject removed', 'Subject was removed.'); })
                                                          .catch(() => showToast('error', 'Remove failed', 'Unable to remove subject. Please try again.'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <p class="text-xs font-semibold text-red-100">Remove this subject? This cannot be undone.</p>
                                                    <div class="mt-3 flex justify-end gap-2">
                                                        <button type="button"
                                                                @click="confirmingSubjectRemoval = null"
                                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                            Cancel
                                                        </button>
                                                        <button class="rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-600">
                                                            Confirm
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            @forelse($subjects as $subject)
                                <tr x-data="{
                                        code: @js($subject->code),
                                        name: @js($subject->name),
                                        courseCode: @js($subject->course_code),
                                        yearLevel: @js($subject->year_level),
                                        semesterValue: @js($subject->semester),
                                        typeValue: @js($subject->type),
                                        lectureUnits: @js((int) $subject->lecture_units),
                                        laboratoryUnits: @js((int) $subject->laboratory_units),
                                        totalUnits: @js((int) $subject->total_units),
                                        removed: false,
                                        applySubject(subject) {
                                            this.code = subject.code;
                                            this.name = subject.name;
                                            this.courseCode = subject.course_code;
                                            this.yearLevel = subject.year_level;
                                            this.semesterValue = subject.semester;
                                            this.typeValue = subject.type;
                                            this.lectureUnits = Number(subject.lecture_units || 0);
                                            this.laboratoryUnits = Number(subject.laboratory_units || 0);
                                            this.totalUnits = Number(subject.total_units || 0);
                                        }
                                    }"
                                    x-show="!removed && (!course || course === courseCode) && (!year || year === yearLevel) && (!semester || semester === semesterValue)">
                                    <td class="px-3 py-3">
                                        <p class="font-bold text-white" x-text="code"></p>
                                        <p class="text-xs text-slate-400" x-text="name"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        <span x-text="`${courseCode} / Year ${yearLevel} / ${semesterValue}`"></span>
                                        <p class="mt-1 font-semibold text-blue-100" x-text="typeValue"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        <span x-text="`${totalUnits} total`"></span>
                                        <p class="text-slate-500" x-text="`${lectureUnits} LEC / ${laboratoryUnits} LAB`"></p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        @forelse($subject->schedules as $schedule)
                                            <p>{{ $schedule->day->name }} {{ $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . '-' . $schedule->timeSlot->end_time) }} / {{ $schedule->room->name }}</p>
                                        @empty
                                            <span class="text-slate-500">No schedule</span>
                                        @endforelse
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <button type="button"
                                                @click="editingSubject = {{ $subject->id }}; $nextTick(() => window.lucide?.createIcons())"
                                                class="inline-flex items-center gap-1.5 rounded-2xl border border-blue-300/20 bg-blue-500/15 px-3 py-1.5 text-xs font-bold text-blue-100 transition hover:border-blue-200/40 hover:bg-blue-500/25">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                            Edit
                                        </button>

                                        <div x-show="editingSubject === {{ $subject->id }}"
                                             x-cloak
                                             x-transition.opacity
                                             @keydown.escape.window="editingSubject = null"
                                             class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4 py-8 backdrop-blur-sm">
                                            <div @click.outside="editingSubject = null"
                                                 x-transition.scale.origin.center
                                                 class="w-full max-w-2xl rounded-3xl border border-white/10 bg-[#111c34] p-5 text-left shadow-2xl shadow-black/50">
                                                <div class="mb-4 flex items-start justify-between gap-4 border-b border-white/10 pb-4">
                                                    <div>
                                                        <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Edit Subject</p>
                                                        <p class="mt-1 text-lg font-extrabold text-white" x-text="code"></p>
                                                        <p class="mt-0.5 text-xs text-slate-400" x-text="name"></p>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-bold text-slate-300">
                                                            <span x-text="courseCode"></span>
                                                        </span>
                                                        <button type="button"
                                                                @click="editingSubject = null"
                                                                class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition hover:bg-white/10 hover:text-white">
                                                            <i data-lucide="x" class="h-4 w-4"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                                <form action="{{ route('academic.subjects.update', $subject) }}"
                                                      method="POST"
                                                      x-data="dirtyForm()"
                                                      @submit.prevent="submitSubjectForm($event.target)
                                                          .then((subject) => { applySubject(subject); markClean(); editingSubject = null; showToast('success', 'Subject updated', 'Subject changes were saved.'); })
                                                          .catch(() => showToast('error', 'Update failed', 'Unable to update subject. Please check the fields.'))"
                                                      class="grid grid-cols-2 gap-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="code" value="{{ $subject->code }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="name" value="{{ $subject->name }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <select name="course_code" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSBA', 'BSOM', 'BSA'] as $courseCode)
                                                            <option value="{{ $courseCode }}" @selected($subject->course_code === $courseCode)>{{ $courseCode }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="year_level" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['1', '2', '3', '4'] as $yearOption)
                                                            <option value="{{ $yearOption }}" @selected($subject->year_level === $yearOption)>Year {{ $yearOption }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="semester" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['1st', '2nd', 'Summer'] as $semesterOption)
                                                            <option value="{{ $semesterOption }}" @selected($subject->semester === $semesterOption)>{{ $semesterOption }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="type" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['LEC', 'LAB', 'BOTH'] as $type)
                                                            <option value="{{ $type }}" @selected($subject->type === $type)>{{ $type }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="number" step="1" min="0" name="lecture_units" value="{{ $subject->lecture_units }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <div class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs text-slate-300">
                                                        <p class="font-semibold text-slate-200">Lab Units</p>
                                                        <p>Auto: 1 for Lab/Both, 0 for Lecture.</p>
                                                    </div>
                                                    <div class="col-span-2 flex justify-between gap-2">
                                                        <button type="button"
                                                                @click="confirmingSubjectRemoval = {{ $subject->id }}"
                                                                class="rounded-lg border border-red-300/20 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-200 transition hover:bg-red-500/20">
                                                            Remove subject
                                                        </button>
                                                        <div class="flex gap-2">
                                                        <button type="button"
                                                                @click="editingSubject = null"
                                                                class="rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                            Cancel
                                                        </button>
                                                        <button :disabled="!dirty" class="rounded-lg bg-[#1552d4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">Update Subject</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <form action="{{ route('academic.subjects.destroy', $subject) }}"
                                                      method="POST"
                                                      x-show="confirmingSubjectRemoval === {{ $subject->id }}"
                                                      x-transition
                                                      class="mt-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3"
                                                      @submit.prevent="deleteSubject($event.target)
                                                          .then(() => { removed = true; subjectCount = Math.max(0, subjectCount - 1); editingSubject = null; confirmingSubjectRemoval = null; showToast('success', 'Subject removed', 'Subject was removed.'); })
                                                          .catch(() => showToast('error', 'Remove failed', 'Unable to remove subject. Please try again.'))">
                                                    @csrf
                                                    @method('DELETE')
                                                    <p class="text-xs font-semibold text-red-100">Remove this subject? This cannot be undone.</p>
                                                    <div class="mt-3 flex justify-end gap-2">
                                                        <button type="button"
                                                                @click="confirmingSubjectRemoval = null"
                                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                            Cancel
                                                        </button>
                                                        <button class="rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-600">
                                                            Confirm
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-10 text-center text-sm text-slate-300">No subjects configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if($canManageAdminConfiguration)
        <section x-show="section === 'heads'" x-cloak class="grid grid-cols-12 gap-5">
            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-5">
                <h3 class="font-extrabold text-white">Department Head</h3>
                <p class="mt-1 text-xs text-slate-300">The active name auto-fills in enrollment forms and outputs.</p>

                <form action="{{ route('academic.department-heads.store') }}"
                      method="POST"
                      x-data="dirtyForm()"
                      class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-1"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addDepartmentHead(data.department_head); $event.target.reset(); $nextTick(() => markClean()); showToast('success', 'Department head saved', 'Department head was updated.'); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    <select name="course_code" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSBA', 'BSOM', 'BSA'] as $courseCode)
                            <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                        @endforeach
                    </select>
                    <input name="name" placeholder="Department Head Name" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input name="title" placeholder="Title, optional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm sm:col-span-2 lg:col-span-1">
                    <button :disabled="!dirty" class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white sm:col-span-2 lg:col-span-1 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">Save Department Head</button>
                </form>
            </div>

            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-7">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-extrabold text-white">Active Department Heads</h3>
                        <p class="mt-1 text-xs text-slate-300"><span x-text="departmentHeadCount"></span> configured active records.</p>
                    </div>
                </div>

                <div class="mt-4 grid max-h-[430px] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                    <template x-for="head in addedDepartmentHeads" :key="`head-${head.course_code}`">
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs">
                            <p class="font-bold text-white" x-text="head.course_code"></p>
                            <p class="mt-1 text-sm font-semibold text-blue-100" x-text="head.name"></p>
                            <p class="text-slate-400" x-text="head.title || 'Department Head'"></p>
                        </div>
                    </template>
                    @forelse($departmentHeads as $head)
                        <div x-show="!addedDepartmentHeads.some((item) => item.course_code === @js($head->course_code))"
                             class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs">
                            <p class="font-bold text-white">{{ $head->course_code }}</p>
                            <p class="mt-1 text-sm font-semibold text-blue-100">{{ $head->name }}</p>
                            <p class="text-slate-400">{{ $head->title ?? 'Department Head' }}</p>
                        </div>
                    @empty
                        <p x-show="addedDepartmentHeads.length === 0" class="text-sm text-slate-300">No active department heads yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section x-show="section === 'fees'" x-cloak class="rounded-2xl border border-white/10 bg-white/5 overflow-hidden">

            <div class="grid min-h-[390px] grid-cols-12">
                <aside class="col-span-12 border-b border-white/10 p-4 lg:col-span-2 lg:border-b-0 lg:border-r lg:border-white/10">
                    <p class="mb-3 text-xs font-bold uppercase tracking-[0.16em] text-slate-400">Courses</p>
                    <div class="flex gap-2 overflow-x-auto lg:block lg:space-y-2 lg:overflow-visible">
                        @foreach($feeRows as $feeRow)
                            <button type="button"
                                    @click="feeCourse = @js($feeRow['course_code'])"
                                    :class="feeCourse === @js($feeRow['course_code']) ? 'border-blue-300/50 bg-blue-500/20 text-white' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 hover:text-white'"
                                    class="min-w-24 rounded-xl border px-4 py-3 text-center text-sm font-bold transition lg:w-full">
                                {{ $feeRow['course_code'] }}
                            </button>
                        @endforeach
                    </div>
                </aside>

                <div class="col-span-12 flex justify-center p-5 lg:col-span-10">
                    @foreach($feeRows as $feeRow)
                        <form action="{{ route('academic.fees.update') }}"
                              method="POST"
                              x-show="feeCourse === @js($feeRow['course_code'])"
                              x-data="{ saving: false, ...dirtyFormState() }"
                              @submit.prevent="saving = true; submitAcademicForm($event.target)
                                  .then(() => { markClean(); showToast('success', 'Fees updated', '{{ $feeRow['course_code'] }} fee configuration was saved.'); })
                                  .catch((error) => showToast('error', 'Save failed', error.message))
                                  .finally(() => saving = false)"
                              class="w-full max-w-4xl">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="course_code" value="{{ $feeRow['course_code'] }}">

                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-2xl font-extrabold text-white">{{ $feeRow['course_code'] }}</h4>
                                    <p class="mt-1 text-sm text-slate-300">Course fee configuration</p>
                                </div>
                                <button type="submit"
                                        :disabled="saving || !dirty"
                                        class="inline-flex items-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    <span x-text="saving ? 'Saving' : 'Save Fees'"></span>
                                </button>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($feeTypes as $feeType => $feeLabel)
                                    <label class="block rounded-2xl border border-white/10 bg-white/5 p-3">
                                        <span class="text-xs font-semibold text-slate-300">{{ $feeLabel }}</span>
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               name="fees[{{ $feeType }}]"
                                               value="{{ number_format($feeRow['fees'][$feeType] ?? 0, 2, '.', '') }}"
                                               class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    </label>
                                @endforeach
                            </div>
                        </form>
                    @endforeach
                </div>
            </div>
        </section>
        @endif
        <section x-show="section === 'templates'"
                 x-cloak
                 x-data="templateMapper({
                    template: @js($enrollmentTemplatePayload),
                    fields: [
                        { key: 'student_number', label: 'Student ID', type: 'text' },
                        { key: 'date_filed', label: 'Date Filed', type: 'text' },
                        { key: 'school_year', label: 'School Year', type: 'text' },
                        { key: 'last_name', label: 'Last Name', type: 'text' },
                        { key: 'first_name', label: 'First Name', type: 'text' },
                        { key: 'middle_name', label: 'Middle Name', type: 'text' },
                        { key: 'cellphone', label: 'Cellphone', type: 'text' },
                        { key: 'email', label: 'Email', type: 'text' },
                        { key: 'last_school', label: 'Last School', type: 'text' },
                        { key: 'present_address', label: 'Present Address', type: 'text' },
                        { key: 'barangay', label: 'Barangay', type: 'text' },
                        { key: 'city', label: 'City', type: 'text' },
                        { key: 'province', label: 'Province', type: 'text' },
                        { key: 'date_of_birth', label: 'Date of Birth', type: 'text' },
                        { key: 'age', label: 'Age', type: 'text' },
                        { key: 'civil_status', label: 'Civil Status', type: 'text' },
                        { key: 'place_of_birth', label: 'Place of Birth', type: 'text' },
                        { key: 'gender', label: 'Gender', type: 'text' },
                        { key: 'religion', label: 'Religion', type: 'text' },
                        { key: 'father_name', label: 'Father Name', type: 'text' },
                        { key: 'father_address', label: 'Father Address', type: 'text' },
                        { key: 'father_cpNumber', label: 'Father Contact', type: 'text' },
                        { key: 'mother_name', label: 'Mother Name', type: 'text' },
                        { key: 'mother_address', label: 'Mother Address', type: 'text' },
                        { key: 'mother_cpNumber', label: 'Mother Contact', type: 'text' },
                        { key: 'department_head_name', label: 'Department Head', type: 'text' },
                        { key: 'subject_code_1', label: 'Subject Code 1', type: 'text' },
                        { key: 'subject_name_1', label: 'Subject Name 1', type: 'text' },
                        { key: 'subject_units_1', label: 'Subject Units 1', type: 'text' },
                        { key: 'subject_code_2', label: 'Subject Code 2', type: 'text' },
                        { key: 'subject_name_2', label: 'Subject Name 2', type: 'text' },
                        { key: 'subject_units_2', label: 'Subject Units 2', type: 'text' },
                        { key: 'subject_code_3', label: 'Subject Code 3', type: 'text' },
                        { key: 'subject_name_3', label: 'Subject Name 3', type: 'text' },
                        { key: 'subject_units_3', label: 'Subject Units 3', type: 'text' },
                        { key: 'subject_code_4', label: 'Subject Code 4', type: 'text' },
                        { key: 'subject_name_4', label: 'Subject Name 4', type: 'text' },
                        { key: 'subject_units_4', label: 'Subject Units 4', type: 'text' },
                        { key: 'subject_code_5', label: 'Subject Code 5', type: 'text' },
                        { key: 'subject_name_5', label: 'Subject Name 5', type: 'text' },
                        { key: 'subject_units_5', label: 'Subject Units 5', type: 'text' },
                        { key: 'subject_code_6', label: 'Subject Code 6', type: 'text' },
                        { key: 'subject_name_6', label: 'Subject Name 6', type: 'text' },
                        { key: 'subject_units_6', label: 'Subject Units 6', type: 'text' },
                        { key: 'subject_code_7', label: 'Subject Code 7', type: 'text' },
                        { key: 'subject_name_7', label: 'Subject Name 7', type: 'text' },
                        { key: 'subject_units_7', label: 'Subject Units 7', type: 'text' },
                        { key: 'subject_code_8', label: 'Subject Code 8', type: 'text' },
                        { key: 'subject_name_8', label: 'Subject Name 8', type: 'text' },
                        { key: 'subject_units_8', label: 'Subject Units 8', type: 'text' },
                        { key: 'subject_code_9', label: 'Subject Code 9', type: 'text' },
                        { key: 'subject_name_9', label: 'Subject Name 9', type: 'text' },
                        { key: 'subject_units_9', label: 'Subject Units 9', type: 'text' },
                        { key: 'subject_code_10', label: 'Subject Code 10', type: 'text' },
                        { key: 'subject_name_10', label: 'Subject Name 10', type: 'text' },
                        { key: 'subject_units_10', label: 'Subject Units 10', type: 'text' },
                        { key: 'total_units', label: 'Total Units', type: 'text' },
                        { key: 'tuition_fee', label: 'Tuition Fee', type: 'text' },
                        { key: 'nstp_fee', label: 'NSTP Fee', type: 'text' },
                        { key: 'subtotal_tuition_fee', label: 'Subtotal Tuition Fee', type: 'text' },
                        { key: 'misc_fees', label: 'Misc. Fees', type: 'text' },
                        { key: 'hands_on_fee', label: 'Hands-on Fee', type: 'text' },
                        { key: 'lab_fee', label: 'Lab Fee', type: 'text' },
                        { key: 'total_tuition_fee', label: 'Total Tuition Fee', type: 'text' },
                        { key: 'total_account', label: 'Total Account', type: 'text' },
                        { key: 'course_BSIT', label: 'BSIT Check', type: 'check' },
                        { key: 'course_BSCS', label: 'BSCS Check', type: 'check' },
                        { key: 'course_ACT', label: 'ACT Check', type: 'check' },
                        { key: 'course_BSBA', label: 'BSBA Check', type: 'check' },
                        { key: 'course_BSOM', label: 'BSOM Check', type: 'check' },
                        { key: 'course_BSA', label: 'BSA Check', type: 'check' },
                        { key: 'year_1', label: 'Year 1 Check', type: 'check' },
                        { key: 'year_2', label: 'Year 2 Check', type: 'check' },
                        { key: 'year_3', label: 'Year 3 Check', type: 'check' },
                        { key: 'year_4', label: 'Year 4 Check', type: 'check' },
                        { key: 'semester_1st', label: '1st Sem Check', type: 'check' },
                        { key: 'semester_2nd', label: '2nd Sem Check', type: 'check' },
                        { key: 'semester_summer', label: 'Summer Check', type: 'check' },
                        { key: 'credential_form_138', label: 'Form 138 Check', type: 'check' },
                        { key: 'credential_birth_certificate', label: 'Birth Cert Check', type: 'check' },
                        { key: 'credential_good_moral', label: 'Good Moral Check', type: 'check' },
                        { key: 'credential_certificate_grades', label: 'Cert. Grades Check', type: 'check' },
                        { key: 'credential_certificate_eligibility', label: 'Eligibility Check', type: 'check' },
                        { key: 'credential_transcript', label: 'Transcript Check', type: 'check' },
                        { key: 'credential_long_folder', label: 'Long Folder Check', type: 'check' },
                        { key: 'credential_picture', label: 'Picture Check', type: 'check' },
                    ],
                    idTemplate: @js($idTemplatePayload),
                    idTemplates: @js($idTemplatePayloads),
                    idFonts: @js($idFonts ?? []),
                    idFontUploadUrl: @js(route('academic.id-templates.fonts.store')),
                    idFields: [
                        { key: 'student_photo', label: 'Student Photo', type: 'image', width: 120, height: 140, shape: 'rectangle', object_fit: 'cover' },
                        { key: 'signature', label: 'Signature', type: 'image', width: 160, height: 48, shape: 'rectangle', object_fit: 'contain', locked_shape: true },
                        { key: 'student_number', label: 'Student ID', type: 'text', width: 150, height: 24, font_size: 16, font_family: 'Arial', font_weight: '700' },
                        { key: 'full_name', label: 'Name', type: 'text', width: 260, height: 28, font_size: 20, font_family: 'Arial', font_weight: '800', text_align: 'center' },
                        { key: 'present_address', label: 'Address', type: 'text', width: 260, height: 44, font_size: 12, font_family: 'Arial', font_weight: '600' },
                        { key: 'course_code', label: 'Course Code', type: 'text', width: 120, height: 24, font_size: 15, font_family: 'Arial', font_weight: '700', text_align: 'center' },
                        { key: 'course_plain_name', label: 'Course Plain Name', type: 'text', width: 180, height: 26, font_size: 13, font_family: 'Arial', font_weight: '700', text_align: 'center' },
                        { key: 'course_short_name', label: 'Course Short Name', type: 'text', width: 220, height: 28, font_size: 13, font_family: 'Arial', font_weight: '700', text_align: 'center' },
                        { key: 'course_full_name', label: 'Course Full Name', type: 'text', width: 280, height: 42, font_size: 12, font_family: 'Arial', font_weight: '600', text_align: 'center' },
                        { key: 'year_level', label: 'Year Level', type: 'text', width: 110, height: 22, font_size: 14, font_family: 'Arial', font_weight: '700' },
                        { key: 'date_of_birth', label: 'Birthday', type: 'text', width: 150, height: 22, font_size: 14, font_family: 'Arial', font_weight: '600' },
                        { key: 'school_year', label: 'School Year', type: 'text', width: 140, height: 22, font_size: 13, font_family: 'Arial', font_weight: '600' },
                        { key: 'emergency_contact_name', label: 'Emergency Name', type: 'text', width: 180, height: 24, font_size: 13, font_family: 'Arial', font_weight: '700' },
                        { key: 'emergency_contact_relationship', label: 'Emergency Relation', type: 'text', width: 160, height: 22, font_size: 12, font_family: 'Arial', font_weight: '600' },
                        { key: 'emergency_contact_number', label: 'Emergency Contact', type: 'text', width: 170, height: 22, font_size: 12, font_family: 'Arial', font_weight: '600' },
                    ],
                 })"
                 x-init="init()"
                 class="space-y-5">
            <div class="flex flex-col gap-3 rounded-2xl border border-white/10 bg-white/5 p-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="font-extrabold text-white">Templates</h3>
                    <p class="mt-1 text-xs text-slate-300">Manage document layouts used by the system.</p>
                </div>

                <div class="grid grid-cols-2 gap-2 rounded-2xl border border-white/10 bg-white/5 p-1">
                    <button type="button"
                            @click="templateSection = 'enrollment'"
                            :class="templateSection === 'enrollment' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition">
                        Enrollment Template
                    </button>
                    <button type="button"
                            @click="templateSection = 'id'"
                            :class="templateSection === 'id' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                            class="rounded-xl px-4 py-2 text-xs font-bold transition">
                        ID Template
                    </button>
                </div>
            </div>

            <div x-show="templateSection === 'enrollment'" x-cloak class="grid grid-cols-12 gap-5">
            <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-3">
                <h3 class="font-extrabold text-white">Enrollment Template</h3>
                <p class="mt-1 text-xs text-slate-300">Upload the current school form, then place data fields on the PDF.</p>

                <form action="{{ route('academic.templates.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      x-data="dirtyForm()"
                      class="mt-4 space-y-3"
                      @submit.prevent="uploadTemplate($event.target).then(() => { $event.target.reset(); $nextTick(() => markClean()); }).catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Upload failed', message: error.message } })))">
                    @csrf
                    <input name="name" placeholder="Template name" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input type="file" name="template_pdf" accept="application/pdf" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button :disabled="!dirty" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                        <i data-lucide="upload" class="h-4 w-4"></i>
                        Upload PDF
                    </button>
                </form>

                <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Active File</p>
                    <template x-if="template">
                        <div class="mt-2">
                            <p class="text-sm font-bold text-white" x-text="template.name"></p>
                            <p class="mt-1 break-all text-xs text-slate-400" x-text="template.original_filename"></p>
                        </div>
                    </template>
                    <p x-show="!template" class="mt-2 text-xs text-slate-400">No PDF uploaded yet.</p>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <h4 class="text-sm font-extrabold text-white">Fields</h4>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1.5 text-xs font-bold text-slate-300">
                                Size
                                <input type="number"
                                       min="4"
                                       max="40"
                                       step="0.5"
                                       :value="selectedTextSize()"
                                       @input="updateSelectedTextSize($event.target.value)"
                                       class="h-8 w-16 rounded-lg border border-slate-200 px-2 text-xs">
                            </label>
                            <span class="text-xs text-slate-400" x-text="`${mappedFields().length}/${fields.length}`"></span>
                        </div>
                    </div>
                    <div class="max-h-[300px] space-y-2 overflow-y-auto pr-1">
                        <template x-for="field in fields" :key="field.key">
                            <button type="button"
                                    @click="selectedField = field.key"
                                    :class="selectedField === field.key ? 'border-blue-300/40 bg-blue-500/20 text-blue-50' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                    class="flex w-full items-center justify-between rounded-2xl border px-3 py-2 text-left text-xs font-bold transition">
                                <span x-text="field.label"></span>
                                <span :class="mappings[field.key] ? 'bg-emerald-400/20 text-emerald-100' : 'bg-white/10 text-slate-400'"
                                      class="rounded-full px-2 py-0.5 text-[10px]"
                                      x-text="mappings[field.key] ? 'Mapped' : field.type"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </aside>

            <div @keydown.escape.window="isFullscreen = false"
                 :class="isFullscreen ? 'fixed inset-4 z-[80] flex flex-col rounded-3xl border border-white/10 bg-[#071224]/95 p-5 shadow-2xl shadow-black/60 backdrop-blur' : 'col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-6'"
                 class="transition">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="font-extrabold text-white">PDF Mapper</h3>
                        <p class="mt-1 text-xs text-slate-300">Select a field, then click it onto the form.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div x-show="template" class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/10 px-2 py-1.5 text-xs font-bold text-white">
                            <button type="button"
                                    @click="goToPage(currentPage - 1)"
                                    :disabled="currentPage <= 1"
                                    class="rounded-md px-2 py-1 transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40">Prev</button>
                            <span>Page <span x-text="currentPage"></span>/<span x-text="pageCount"></span></span>
                            <button type="button"
                                    @click="goToPage(currentPage + 1)"
                                    :disabled="currentPage >= pageCount"
                                    class="rounded-md px-2 py-1 transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40">Next</button>
                        </div>
                        <button type="button"
                                @click="toggleFullscreen()"
                                :disabled="!template"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50">
                            <i :data-lucide="isFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                            <span x-text="isFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"></span>
                        </button>
                        <button type="button"
                                @click="saveMappings().catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Save failed', message: error.message } })))"
                                :disabled="!template || saving || !isMappingDirty()"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:opacity-50">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            <span x-text="saving ? 'Saving...' : 'Save Mapping'"></span>
                        </button>
                    </div>
                </div>

                <div x-show="!template" class="flex h-[560px] items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 text-center">
                    <div>
                        <i data-lucide="file-up" class="mx-auto h-10 w-10 text-blue-200"></i>
                        <p class="mt-3 text-sm font-bold text-white">Upload a PDF template first</p>
                        <p class="mt-1 text-xs text-slate-400">The mapper will render page 1 here.</p>
                    </div>
                </div>

                <div x-show="template"
                     :class="isFullscreen ? 'grid min-h-0 flex-1 grid-cols-[260px_minmax(0,1fr)] gap-4 overflow-hidden border-0 bg-transparent p-0' : 'h-[560px] overflow-auto border border-white/10 bg-slate-950/50 p-4'"
                     class="rounded-2xl">
                    <aside x-show="isFullscreen" class="min-h-0 overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-3">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-extrabold text-white">Fields</h4>
                            <div class="flex items-center gap-2">
                                <label class="flex items-center gap-1.5 text-xs font-bold text-slate-300">
                                    Size
                                    <input type="number"
                                           min="4"
                                           max="40"
                                           step="0.5"
                                           :value="selectedTextSize()"
                                           @input="updateSelectedTextSize($event.target.value)"
                                           class="h-8 w-16 rounded-lg border border-slate-200 px-2 text-xs">
                                </label>
                                <span class="text-xs text-slate-400" x-text="`${mappedFields().length}/${fields.length}`"></span>
                            </div>
                        </div>
                        <div class="h-[calc(100%-2rem)] space-y-2 overflow-y-auto pr-1">
                            <template x-for="field in fields" :key="`fullscreen-field-${field.key}`">
                                <button type="button"
                                        @click="selectedField = field.key"
                                        :class="selectedField === field.key ? 'border-blue-300/40 bg-blue-500/20 text-blue-50' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                        class="flex w-full items-center justify-between rounded-2xl border px-3 py-2 text-left text-xs font-bold transition">
                                    <span x-text="field.label"></span>
                                    <span :class="mappings[field.key] ? 'bg-emerald-400/20 text-emerald-100' : 'bg-white/10 text-slate-400'"
                                          class="rounded-full px-2 py-0.5 text-[10px]"
                                          x-text="mappings[field.key] ? 'Mapped' : field.type"></span>
                                </button>
                            </template>
                        </div>
                    </aside>

                    <div :class="isFullscreen ? 'min-h-0 overflow-auto' : 'overflow-visible'"
                         class="rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                        <div x-show="loadingPdf" class="mb-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200">
                            Rendering PDF...
                        </div>
                        <div x-ref="canvasWrap"
                             @click="placeSelected($event)"
                             class="relative mx-auto w-max cursor-crosshair overflow-hidden rounded-xl bg-white shadow-2xl shadow-black/30">
                            <canvas x-ref="pdfCanvas"></canvas>
                            <template x-for="mapping in visibleMappedFields()" :key="mapping.key">
                                <button type="button"
                                        @pointerdown.stop="startDrag($event, mapping.key)"
                                        :style="markerStyle(mapping)"
                                        :class="mapping.type === 'check' ? 'text-emerald-700' : 'text-[#1552d4]'"
                                        class="absolute z-10 -translate-x-0 -translate-y-0 whitespace-nowrap border-0 bg-transparent p-0 font-bold shadow-none outline-none">
                                    <span x-show="mapping.type !== 'check'" x-text="mapping.label"></span>
                                    <span x-show="mapping.type === 'check'" class="font-bold leading-none">&#10003;</span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-3">
                <h3 class="font-extrabold text-white">Saved Positions</h3>
                <p class="mt-1 text-xs text-slate-300">PDF Page Coordinates</p>

                <div class="mt-4 max-h-[540px] space-y-2 overflow-y-auto pr-1">
                    <template x-for="mapping in mappedFields()" :key="`position-${mapping.key}`">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-bold text-white" x-text="mapping.label"></p>
                                    <p class="mt-1 text-slate-400">
                                        Page: <span x-text="mapping.page || 1"></span><span class="mx-1 text-slate-600">/</span> X: <span x-text="mapping.x"></span>
                                        <span class="mx-1 text-slate-600">/</span>
                                        Y: <span x-text="mapping.y"></span>
                                    </p>
                                </div>
                                <button type="button"
                                        @click="removeMapping(mapping.key)"
                                        class="rounded-lg border border-red-300/20 bg-red-500/10 px-2 py-1 text-[10px] font-bold text-red-200 transition hover:bg-red-500/20">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </template>
                    <p x-show="mappedFields().length === 0" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-center text-sm text-slate-300">
                        No fields mapped yet.
                    </p>
                </div>
            </aside>
            </div>

            <section x-show="templateSection === 'id'"
                     x-cloak
                     class="grid grid-cols-12 gap-5">
                <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-3">
                    <h3 class="font-extrabold text-white">ID Template</h3>
                    <p class="mt-1 text-xs text-slate-300">Upload a blank ID background, then place text and photo fields.</p>

                    <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-2">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button"
                                    @click="switchIdTemplateSide('front')"
                                    :class="idTemplateSide === 'front' ? 'bg-white text-[#1552d4]' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                                    class="rounded-xl px-3 py-2 text-xs font-bold transition">
                                Front
                            </button>
                            <button type="button"
                                    @click="switchIdTemplateSide('back')"
                                    :class="idTemplateSide === 'back' ? 'bg-white text-[#1552d4]' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                                    class="rounded-xl px-3 py-2 text-xs font-bold transition">
                                Back
                            </button>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-[10px] font-bold uppercase tracking-wide text-slate-400">
                            <span :class="idTemplates.front ? 'text-emerald-200' : 'text-slate-500'">
                                Front <span x-text="idTemplates.front ? 'Ready' : 'Empty'"></span>
                            </span>
                            <span :class="idTemplates.back ? 'text-emerald-200' : 'text-slate-500'">
                                Back <span x-text="idTemplates.back ? 'Ready' : 'Empty'"></span>
                            </span>
                        </div>
                    </div>

                    <form action="{{ route('academic.id-templates.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          x-data="dirtyForm()"
                          class="mt-4 space-y-3"
                          @submit.prevent="uploadIdTemplate($event.target).then(() => { $nextTick(() => markClean()); }).catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Upload failed', message: error.message } })))">
                        @csrf
                        <input name="name" placeholder="Template name" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <div class="grid grid-cols-2 gap-2">
                            <select name="side" required x-model="idTemplateSide" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <option value="front">Front</option>
                                <option value="back">Back</option>
                            </select>
                            <input name="school_year" placeholder="A.Y." class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        </div>
                        <input type="file" name="background_image" accept="image/png,image/jpeg,image/webp" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <button :disabled="!dirty" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload Background
                        </button>
                    </form>

                    <form action="{{ route('academic.id-templates.fonts.store') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          x-data="dirtyForm()"
                          class="mt-4 space-y-3 rounded-2xl border border-white/10 bg-white/5 p-3"
                          @submit.prevent="uploadIdFont($event.target).then(() => { $nextTick(() => markClean()); }).catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Font upload failed', message: error.message } })))">
                        @csrf
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Font Style</p>
                            <p class="mt-1 text-xs text-slate-400">Upload TTF, OTF, WOFF, or WOFF2 files for ID text.</p>
                        </div>
                        <input type="file" name="font_file" accept=".ttf,.otf,.woff,.woff2,font/ttf,font/otf,font/woff,font/woff2" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <button :disabled="!dirty" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                            <i data-lucide="type" class="h-4 w-4"></i>
                            Upload Font
                        </button>
                    </form>

                    <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Active ID File</p>
                        <template x-if="idTemplate">
                            <div class="mt-2">
                                <p class="text-sm font-bold text-white" x-text="idTemplate.name"></p>
                                <p class="mt-1 text-xs capitalize text-slate-400">
                                    <span x-text="idTemplate.side"></span>
                                    <span x-show="idTemplate.school_year">/ <span x-text="idTemplate.school_year"></span></span>
                                </p>
                            </div>
                        </template>
                        <p x-show="!idTemplate" class="mt-2 text-xs text-slate-400">No ID background uploaded yet.</p>
                    </div>

                    <div class="mt-4">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-sm font-extrabold text-white">Fields</h4>
                            <span class="text-xs text-slate-400" x-text="`${mappedIdFieldCount()}/${idFields.length}`"></span>
                        </div>
                        <div class="max-h-[250px] space-y-2 overflow-y-auto pr-1">
                            <template x-for="field in idFields" :key="field.key">
                                <button type="button"
                                        @click="selectedIdField = field.key"
                                        :class="selectedIdField === field.key ? 'border-blue-300/40 bg-blue-500/20 text-blue-50' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                        class="flex w-full items-center justify-between rounded-2xl border px-3 py-2 text-left text-xs font-bold transition">
                                    <span x-text="field.label"></span>
                                    <span :class="isIdFieldMapped(field.key) ? 'bg-emerald-400/20 text-emerald-100' : 'bg-white/10 text-slate-400'"
                                          class="rounded-full px-2 py-0.5 text-[10px]"
                                          x-text="isIdFieldMapped(field.key) ? 'Mapped' : field.type"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                </aside>

                <div @keydown.escape.window="idFullscreen = false"
                     :class="idFullscreen ? 'fixed inset-4 z-[80] flex flex-col rounded-3xl border border-white/10 bg-[#071224]/95 p-5 shadow-2xl shadow-black/60 backdrop-blur' : 'col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-6'"
                     class="transition">
                    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h3 class="font-extrabold text-white">ID Mapper</h3>
                            <p class="mt-1 text-xs text-slate-300">Select a field, click the ID, then drag it into place.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex items-center gap-1 rounded-lg border border-white/10 bg-white/10 p-1 text-xs font-bold text-white">
                                <button type="button"
                                        @click="zoomIdStage(-0.1)"
                                        :disabled="!idTemplate || idZoom <= 0.4"
                                        class="rounded-md px-2 py-1 transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40">
                                    <i data-lucide="minus" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button"
                                        @click="resetIdZoom()"
                                        :disabled="!idTemplate"
                                        class="min-w-14 rounded-md px-2 py-1 transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                        x-text="`${Math.round(idZoom * 100)}%`"></button>
                                <button type="button"
                                        @click="zoomIdStage(0.1)"
                                        :disabled="!idTemplate || idZoom >= 3"
                                        class="rounded-md px-2 py-1 transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40">
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                            <button type="button"
                                    @click="toggleIdFullscreen()"
                                    :disabled="!idTemplate"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50">
                                <i :data-lucide="idFullscreen ? 'minimize-2' : 'maximize-2'" class="h-4 w-4"></i>
                                <span x-text="idFullscreen ? 'Exit Fullscreen' : 'Fullscreen'"></span>
                            </button>
                            <button type="button"
                                    @click="saveIdLayout().catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Save failed', message: error.message } })))"
                                    :disabled="!idTemplate || idSaving || !hasIdLayoutChanges()"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:opacity-50">
                                <i data-lucide="save" class="h-4 w-4"></i>
                                <span x-text="idSaving ? 'Saving...' : 'Save ID Layouts'"></span>
                            </button>
                        </div>
                    </div>

                    <div x-show="!idTemplate" class="flex h-[560px] items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 text-center">
                        <div>
                            <i data-lucide="image-up" class="mx-auto h-10 w-10 text-blue-200"></i>
                            <p class="mt-3 text-sm font-bold text-white">Upload an ID background first</p>
                            <p class="mt-1 text-xs text-slate-400">PNG, JPG, or WEBP works best.</p>
                        </div>
                    </div>

                    <div x-show="idTemplate"
                         x-ref="idViewport"
                         :class="idFullscreen ? 'grid min-h-0 flex-1 grid-cols-[240px_minmax(0,1fr)_280px] gap-4 overflow-hidden border-0 bg-transparent p-0' : 'h-[560px] overflow-auto rounded-2xl border border-white/10 bg-slate-950/50 p-4'"
                         @wheel="zoomIdStageAt($event)">
                        <aside x-show="idFullscreen" class="min-h-0 overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <h4 class="text-sm font-extrabold text-white">Fields</h4>
                                <span class="text-xs text-slate-400" x-text="`${mappedIdFieldCount()}/${idFields.length}`"></span>
                            </div>
                            <div class="h-[calc(100%-2rem)] space-y-2 overflow-y-auto pr-1">
                                <template x-for="field in idFields" :key="`fullscreen-id-field-${field.key}`">
                                    <button type="button"
                                            @click="selectedIdField = field.key"
                                            :class="selectedIdField === field.key ? 'border-blue-300/40 bg-blue-500/20 text-blue-50' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                            class="flex w-full items-center justify-between rounded-2xl border px-3 py-2 text-left text-xs font-bold transition">
                                        <span x-text="field.label"></span>
                                        <span :class="isIdFieldMapped(field.key) ? 'bg-emerald-400/20 text-emerald-100' : 'bg-white/10 text-slate-400'"
                                              class="rounded-full px-2 py-0.5 text-[10px]"
                                              x-text="isIdFieldMapped(field.key) ? 'Mapped' : field.type"></span>
                                    </button>
                                </template>
                            </div>
                        </aside>

                        <div :class="idFullscreen ? 'min-h-0 overflow-auto rounded-2xl border border-white/10 bg-slate-950/50 p-4' : 'contents'"
                             @wheel.stop="zoomIdStageAt($event)">
                            <div x-ref="idStage"
                                 @click="placeSelectedIdField($event)"
                                 :style="idStageZoomStyle()"
                                 class="relative mx-auto w-max cursor-crosshair overflow-hidden rounded-xl bg-white shadow-2xl shadow-black/30">
                                <img x-ref="idBackground"
                                     :src="idTemplate?.background_url"
                                     @load="refreshIdCanvasSize()"
                                     class="block max-h-[520px] max-w-full select-none"
                                     alt="ID template background">
                                <template x-for="mapping in mappedIdFields()" :key="mapping.key">
                                    <button type="button"
                                            @pointerdown.stop="startIdDrag($event, mapping.key)"
                                            :style="idMarkerStyle(mapping)"
                                            :class="mapping.type === 'image' ? 'border-blue-500/80 bg-blue-500/10 text-blue-700' : 'border-transparent bg-transparent text-[#1552d4]'"
                                            class="absolute z-10 overflow-visible rounded-none p-0 text-left font-bold outline-none">
                                        <template x-if="mapping.type === 'text'">
                                            <span :style="idTextBoxStyle(mapping)" x-text="mapping.label"></span>
                                        </template>
                                        <template x-if="mapping.type === 'image'">
                                            <span :style="idPhotoMaskStyle(mapping)"
                                                  class="flex h-full w-full items-center justify-center border-2 border-dashed border-blue-500/80 bg-blue-50/70 text-[10px] font-extrabold uppercase tracking-wide">
                                                Photo
                                            </span>
                                        </template>
                                        <template x-if="selectedIdField === mapping.key">
                                            <span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'nw')" class="absolute -left-1.5 -top-1.5 h-3 w-3 cursor-nwse-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'n')" class="absolute left-1/2 -top-1.5 h-3 w-3 -translate-x-1/2 cursor-ns-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'ne')" class="absolute -right-1.5 -top-1.5 h-3 w-3 cursor-nesw-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'e')" class="absolute -right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 cursor-ew-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'se')" class="absolute -bottom-1.5 -right-1.5 h-3 w-3 cursor-nwse-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 's')" class="absolute bottom-[-0.375rem] left-1/2 h-3 w-3 -translate-x-1/2 cursor-ns-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'sw')" class="absolute -bottom-1.5 -left-1.5 h-3 w-3 cursor-nesw-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                                <span @pointerdown.stop="startIdResize($event, mapping.key, 'w')" class="absolute -left-1.5 top-1/2 h-3 w-3 -translate-y-1/2 cursor-ew-resize rounded-full border border-white bg-[#1552d4] shadow"></span>
                                            </span>
                                        </template>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <aside x-show="idFullscreen" class="min-h-0 overflow-y-auto rounded-2xl border border-white/10 bg-white/5 p-3">
                            <h4 class="text-sm font-extrabold text-white">Inspector</h4>
                            <p class="mt-1 text-xs text-slate-300">Selected field settings stay available while mapping.</p>
                            <div x-show="selectedIdMapping()" x-cloak class="mt-3 space-y-3 rounded-2xl border border-white/10 bg-white/5 p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Selected Field</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="text-xs font-semibold text-slate-300">
                                        Width
                                        <input type="number" min="1" :value="selectedIdMapping()?.width" @input="updateSelectedIdField('width', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                    </label>
                                    <label class="text-xs font-semibold text-slate-300">
                                        Height
                                        <input type="number" min="1" :value="selectedIdMapping()?.height" @input="updateSelectedIdField('height', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                    </label>
                                    <label x-show="selectedIdMapping()?.type === 'text'" class="text-xs font-semibold text-slate-300">
                                        Text Size
                                        <input type="number" min="4" max="80" :value="selectedIdMapping()?.font_size" @input="updateSelectedIdField('font_size', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                    </label>
                                    <label x-show="selectedIdMapping()?.type === 'text'" class="text-xs font-semibold text-slate-300">
                                        Color
                                        <input type="color" :value="selectedIdMapping()?.font_color || '#111827'" @input="updateSelectedIdField('font_color', $event.target.value)" class="mt-1 h-8 w-full rounded-lg border border-slate-200 bg-white px-1 py-1">
                                    </label>
                                    <label x-show="selectedIdMapping()?.type === 'image' && !selectedIdMapping()?.locked_shape" class="text-xs font-semibold text-slate-300">
                                        Shape
                                        <select :value="selectedIdMapping()?.shape || 'rectangle'" @change="updateSelectedIdField('shape', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                            <option value="rectangle">Rectangle</option>
                                            <option value="rounded">Rounded</option>
                                            <option value="circle">Circle</option>
                                            <option value="oval">Oval</option>
                                            <option value="hexagon">Hexagon</option>
                                        </select>
                                    </label>
                                </div>
                                <label x-show="selectedIdMapping()?.type === 'text'" class="block text-xs font-semibold text-slate-300">
                                    Font
                                    <select :value="selectedIdMapping()?.font_family" @change="updateSelectedIdField('font_family', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                        <option value="Arial">Arial</option>
                                        <option value="Plus Jakarta Sans">Plus Jakarta Sans</option>
                                        <option value="Times New Roman">Times New Roman</option>
                                        <option value="Courier New">Courier New</option>
                                        <template x-for="font in idFonts" :key="`fullscreen-font-${font.family}`">
                                            <option :value="font.family" x-text="font.family"></option>
                                        </template>
                                    </select>
                                </label>
                                <label x-show="selectedIdMapping()?.type === 'image'" class="block text-xs font-semibold text-slate-300">
                                    Photo Fit
                                    <select :value="selectedIdMapping()?.object_fit || 'cover'" @change="updateSelectedIdField('object_fit', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                        <option value="cover">Crop to Fill</option>
                                        <option value="contain">Fit Inside</option>
                                    </select>
                                </label>
                            </div>
                        </aside>
                    </div>
                </div>

                <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-3">
                    <div x-show="selectedIdMapping()" x-cloak class="mb-4 space-y-3 rounded-2xl border border-white/10 bg-white/5 p-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-blue-200">Selected Field</p>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="text-xs font-semibold text-slate-300">
                                Width
                                <input type="number" min="1" :value="selectedIdMapping()?.width" @input="updateSelectedIdField('width', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                            </label>
                            <label class="text-xs font-semibold text-slate-300">
                                Height
                                <input type="number" min="1" :value="selectedIdMapping()?.height" @input="updateSelectedIdField('height', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                            </label>
                            <label x-show="selectedIdMapping()?.type === 'text'" class="text-xs font-semibold text-slate-300">
                                Text Size
                                <input type="number" min="4" max="80" :value="selectedIdMapping()?.font_size" @input="updateSelectedIdField('font_size', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                            </label>
                            <label x-show="selectedIdMapping()?.type === 'text'" class="text-xs font-semibold text-slate-300">
                                Color
                                <input type="color" :value="selectedIdMapping()?.font_color || '#111827'" @input="updateSelectedIdField('font_color', $event.target.value)" class="mt-1 h-8 w-full rounded-lg border border-slate-200 bg-white px-1 py-1">
                            </label>
                            <label x-show="selectedIdMapping()?.type === 'image' && !selectedIdMapping()?.locked_shape" class="text-xs font-semibold text-slate-300">
                                Shape
                                <select :value="selectedIdMapping()?.shape || 'rectangle'" @change="updateSelectedIdField('shape', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                    <option value="rectangle">Rectangle</option>
                                    <option value="rounded">Rounded</option>
                                    <option value="circle">Circle</option>
                                    <option value="oval">Oval</option>
                                    <option value="hexagon">Hexagon</option>
                                </select>
                            </label>
                            <label x-show="selectedIdMapping()?.type === 'text'" class="text-xs font-semibold text-slate-300">
                                Weight
                                <select :value="selectedIdMapping()?.font_weight" @change="updateSelectedIdField('font_weight', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                    <option value="400">Regular</option>
                                    <option value="600">Semi Bold</option>
                                    <option value="700">Bold</option>
                                    <option value="800">Extra Bold</option>
                                </select>
                            </label>
                        </div>
                        <label x-show="selectedIdMapping()?.type === 'text'" class="block text-xs font-semibold text-slate-300">
                            Font
                            <select :value="selectedIdMapping()?.font_family" @change="updateSelectedIdField('font_family', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                <option value="Arial">Arial</option>
                                <option value="Plus Jakarta Sans">Plus Jakarta Sans</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <template x-for="font in idFonts" :key="`font-${font.family}`">
                                    <option :value="font.family" x-text="font.family"></option>
                                </template>
                            </select>
                        </label>
                        <label x-show="selectedIdMapping()?.type === 'image'" class="block text-xs font-semibold text-slate-300">
                            Photo Fit
                            <select :value="selectedIdMapping()?.object_fit || 'cover'" @change="updateSelectedIdField('object_fit', $event.target.value)" class="mt-1 w-full rounded-lg border border-slate-200 px-2 py-1.5 text-xs">
                                <option value="cover">Crop to Fill</option>
                                <option value="contain">Fit Inside</option>
                            </select>
                        </label>
                    </div>

                    <h3 class="font-extrabold text-white">Layout JSON</h3>
                    <p class="mt-1 text-xs text-slate-300">Coordinates, sizes, and font settings saved for generation.</p>

                    <div class="mt-4 max-h-[540px] space-y-2 overflow-y-auto pr-1">
                        <template x-for="mapping in mappedIdFields()" :key="`id-position-${mapping.key}`">
                            <div class="rounded-2xl border border-white/10 bg-white/5 p-3 text-xs">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="font-bold text-white" x-text="mapping.label"></p>
                                        <p class="mt-1 text-slate-400">
                                            X: <span x-text="mapping.x"></span>
                                            <span class="mx-1 text-slate-600">/</span>
                                            Y: <span x-text="mapping.y"></span>
                                        </p>
                                        <p class="mt-1 text-slate-500">
                                            <span x-text="mapping.width"></span>x<span x-text="mapping.height"></span>
                                            <span x-show="mapping.type === 'text'"> / <span x-text="mapping.font_size"></span>px</span>
                                            <span x-show="mapping.type === 'image'"> / <span x-text="mapping.shape || 'rectangle'"></span></span>
                                        </p>
                                    </div>
                                    <button type="button"
                                            @click="removeIdMapping(mapping.key)"
                                            class="rounded-lg border border-red-300/20 bg-red-500/10 px-2 py-1 text-[10px] font-bold text-red-200 transition hover:bg-red-500/20">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </template>
                        <p x-show="mappedIdFields().length === 0" class="rounded-2xl border border-white/10 bg-white/5 p-4 text-center text-sm text-slate-300">
                            No ID fields mapped yet.
                        </p>
                    </div>
                </aside>
            </section>
        </section>
    </div>
</div>
