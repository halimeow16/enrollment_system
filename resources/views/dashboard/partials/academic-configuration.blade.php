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

<div x-data="{ section: 'subjects', course: '', year: '', semester: '', editingSubject: null, confirmingSubjectRemoval: null, confirmingScheduleRemoval: null }"
     class="academic-config-frame overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
    <div class="border-b border-white/10 px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Academic Setup</p>
                <h2 class="mt-1 text-xl font-extrabold text-white">Configuration</h2>
                <p class="mt-1 text-xs text-slate-300">Manage curriculum, schedules, rooms, and department heads in one workspace.</p>
            </div>

            <div class="grid grid-cols-4 gap-2 rounded-2xl border border-white/10 bg-white/5 p-1">
                <button type="button"
                        @click="section = 'subjects'"
                        :class="section === 'subjects' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Subjects
                </button>
                <button type="button"
                        @click="section = 'scheduling'"
                        :class="section === 'scheduling' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Scheduling
                </button>
                <button type="button"
                        @click="section = 'heads'"
                        :class="section === 'heads' ? 'bg-white text-[#1552d4] shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white'"
                        class="rounded-xl px-3 py-2 text-xs font-bold transition">
                    Dept. Heads
                </button>
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
        <section x-show="section === 'subjects'" x-cloak class="grid grid-cols-12 gap-5">
            <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-4">
                <div class="mb-4">
                    <h3 class="font-extrabold text-white">Add Subject</h3>
                    <p class="mt-1 text-xs text-slate-300">Set the course, year, semester, type, and units.</p>
                </div>

                <form action="{{ route('academic.subjects.store') }}"
                      method="POST"
                      @submit.prevent="submitSubjectForm($event.target)
                          .then((subject) => { addLiveSubject(subject); subjectCount++; $event.target.reset(); showToast('success', 'Subject added', 'Subject was saved and added to the list.'); })
                          .catch(() => showToast('error', 'Save failed', 'Unable to add subject. Please check the fields.'))"
                      class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
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
                            @foreach(['BSIT', 'BSCS', 'ACT', 'BSHM', 'BSOM', 'BSA'] as $courseCode)
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
                        <input type="number" step="0.1" min="0" name="lecture_units" value="3" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-300">Lab Units</label>
                        <input type="number" step="0.1" min="0" name="laboratory_units" value="0" required class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2 xl:col-span-1 2xl:col-span-2">
                        <label class="text-xs font-semibold text-slate-300">Description</label>
                        <input name="description" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-2 xl:col-span-1 2xl:col-span-2">
                        <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0f43b0]">
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
                                        <span x-text="`${Number(subject.total_units || 0).toFixed(1)} total`"></span>
                                        <p class="text-slate-500" x-text="`${Number(subject.lecture_units || 0).toFixed(1)} LEC / ${Number(subject.laboratory_units || 0).toFixed(1)} LAB`"></p>
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
                                                      @submit.prevent="submitSubjectForm($event.target)
                                                          .then((updatedSubject) => { Object.assign(subject, updatedSubject); editingSubject = null; showToast('success', 'Subject updated', 'Subject changes were saved.'); })
                                                          .catch(() => showToast('error', 'Update failed', 'Unable to update subject. Please check the fields.'))"
                                                      class="grid grid-cols-2 gap-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="code" :value="subject.code" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="name" :value="subject.name" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <select name="course_code" :value="subject.course_code" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSHM', 'BSOM', 'BSA'] as $courseCode)
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
                                                    <input type="number" step="0.1" min="0" name="lecture_units" :value="subject.lecture_units" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input type="number" step="0.1" min="0" name="laboratory_units" :value="subject.laboratory_units" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="description" :value="subject.description" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
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
                                                        <button class="rounded-lg bg-[#1552d4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Update Subject</button>
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
                                        lectureUnits: @js((float) $subject->lecture_units),
                                        laboratoryUnits: @js((float) $subject->laboratory_units),
                                        totalUnits: @js((float) $subject->total_units),
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
                                        <span x-text="`${totalUnits.toFixed(1)} total`"></span>
                                        <p class="text-slate-500" x-text="`${lectureUnits.toFixed(1)} LEC / ${laboratoryUnits.toFixed(1)} LAB`"></p>
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
                                                      @submit.prevent="submitSubjectForm($event.target)
                                                          .then((subject) => { applySubject(subject); editingSubject = null; showToast('success', 'Subject updated', 'Subject changes were saved.'); })
                                                          .catch(() => showToast('error', 'Update failed', 'Unable to update subject. Please check the fields.'))"
                                                      class="grid grid-cols-2 gap-3">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="code" value="{{ $subject->code }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="name" value="{{ $subject->name }}" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <select name="course_code" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSHM', 'BSOM', 'BSA'] as $courseCode)
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
                                                    <input type="number" step="0.1" min="0" name="lecture_units" value="{{ $subject->lecture_units }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input type="number" step="0.1" min="0" name="laboratory_units" value="{{ $subject->laboratory_units }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    <input name="description" value="{{ $subject->description }}" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
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
                                                        <button class="rounded-lg bg-[#1552d4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Update Subject</button>
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

        <section x-show="section === 'scheduling'" x-cloak class="grid grid-cols-12 gap-5">
            <div class="col-span-12 h-[430px] overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-4">
                <h3 class="font-extrabold text-white">Schedule Options</h3>
                <p class="mt-1 text-xs text-slate-300">Add the day, time, and room choices used in subject schedules.</p>

                <form action="{{ route('academic.days.store') }}"
                      method="POST"
                      class="mt-4 flex gap-2"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addedDays.push(data.day); $event.target.reset(); showToast('success', 'Day saved', 'Schedule day was saved.'); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    <input name="name" placeholder="Day, e.g. Monday" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-[#1552d4] px-3 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Add</button>
                </form>

                <form action="{{ route('academic.time-slots.store') }}"
                      method="POST"
                      class="mt-3 grid grid-cols-2 gap-2"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addedTimeSlots.push(data.time_slot); $event.target.reset(); showToast('success', 'Time saved', 'Time slot was saved.'); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    <input type="time" name="start_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input type="time" name="end_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input name="label" placeholder="Label" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="col-span-2 rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Add Time Slot</button>
                </form>

                <form action="{{ route('academic.rooms.store') }}"
                      method="POST"
                      class="mt-3 grid grid-cols-2 gap-2"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addedRooms.push(data.room); $event.target.reset(); showToast('success', 'Room saved', 'Room was saved.'); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    <input name="name" placeholder="Room" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input name="building" placeholder="Building" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input type="number" min="1" name="capacity" placeholder="Capacity" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="col-span-2 rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Add Room</button>
                </form>
            </div>

            <div class="col-span-12 h-[430px] overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-4">
                <h3 class="font-extrabold text-white">Assign Schedule</h3>
                <p class="mt-1 text-xs text-slate-300">Saving is blocked when a room already has the selected day and time.</p>

                <form action="{{ route('academic.schedules.store') }}"
                      method="POST"
                      class="mt-4 space-y-3"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addedSchedules.unshift(data.schedule); scheduleCount++; $event.target.reset(); showToast('success', 'Schedule assigned', 'Subject schedule was saved.'); })
                          .catch((error) => showToast('error', 'Schedule failed', error.message))">
                    @csrf
                    <select name="subject_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                        @endforeach
                        <template x-for="subject in addedSubjects" :key="`subject-option-${subject.id}`">
                            <option :value="subject.id" x-text="`${subject.code} - ${subject.name}`"></option>
                        </template>
                    </select>
                    <select name="day_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select day</option>
                        @foreach($days as $day)
                            <option value="{{ $day->id }}">{{ $day->name }}</option>
                        @endforeach
                        <template x-for="day in addedDays" :key="`day-option-${day.id}`">
                            <option :value="day.id" x-text="day.name"></option>
                        </template>
                    </select>
                    <select name="time_slot_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select time</option>
                        @foreach($timeSlots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->label ?? ($slot->start_time . ' - ' . $slot->end_time) }}</option>
                        @endforeach
                        <template x-for="slot in addedTimeSlots" :key="`slot-option-${slot.id}`">
                            <option :value="slot.id" x-text="slot.label"></option>
                        </template>
                    </select>
                    <select name="room_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select room</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                        <template x-for="room in addedRooms" :key="`room-option-${room.id}`">
                            <option :value="room.id" x-text="room.name"></option>
                        </template>
                    </select>
                    <button class="w-full rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white">Assign Schedule</button>
                </form>
            </div>

            <div class="col-span-12 h-[430px] overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-4">
                <h3 class="font-extrabold text-white">Assigned Schedules</h3>
                <p class="mt-1 text-xs text-slate-300"><span x-text="scheduleCount"></span> current schedule entries.</p>

                <div class="mt-4 h-[390px] space-y-2 overflow-y-auto pr-1">
                    <template x-for="schedule in addedSchedules" :key="`schedule-${schedule.id}`">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 text-sm">
                            <p class="font-bold text-white" x-text="`${schedule.subject.code} - ${schedule.subject.name}`"></p>
                            <p class="mt-1 text-xs text-slate-300" x-text="`${schedule.day} / ${schedule.time} / ${schedule.room}`"></p>
                            <button type="button"
                                    @click="confirmingScheduleRemoval = schedule.id"
                                    class="mt-2 text-xs font-bold text-red-300 hover:text-red-100">
                                Remove schedule
                            </button>
                            <form :action="`{{ url('/academic-configuration/schedules') }}/${schedule.id}`"
                                  method="POST"
                                  x-show="confirmingScheduleRemoval === schedule.id"
                                  x-transition
                                  class="mt-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3"
                                  @submit.prevent="deleteAcademicItem($event.target)
                                      .then(() => { addedSchedules = addedSchedules.filter((item) => item.id !== schedule.id); scheduleCount = Math.max(0, scheduleCount - 1); confirmingScheduleRemoval = null; showToast('success', 'Schedule removed', 'Schedule was removed.'); })
                                      .catch(() => showToast('error', 'Remove failed', 'Unable to remove schedule.'))">
                                @csrf
                                @method('DELETE')
                                <p class="text-xs font-semibold text-red-100">Remove this schedule?</p>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button"
                                            @click="confirmingScheduleRemoval = null"
                                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                        Cancel
                                    </button>
                                    <button class="rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-600">
                                        Confirm
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                    @forelse($subjectSchedules as $schedule)
                        <div x-data="{ removed: false }" x-show="!removed" class="rounded-2xl border border-white/10 bg-white/5 p-3 text-sm">
                            <p class="font-bold text-white">{{ $schedule->subject->code }} - {{ $schedule->subject->name }}</p>
                            <p class="mt-1 text-xs text-slate-300">{{ $schedule->day->name }} / {{ $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time) }} / {{ $schedule->room->name }}</p>
                            <button type="button"
                                    @click="confirmingScheduleRemoval = {{ $schedule->id }}"
                                    class="mt-2 text-xs font-bold text-red-300 hover:text-red-100">
                                Remove schedule
                            </button>
                            <form action="{{ route('academic.schedules.destroy', $schedule) }}"
                                  method="POST"
                                  x-show="confirmingScheduleRemoval === {{ $schedule->id }}"
                                  x-transition
                                  class="mt-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3"
                                  @submit.prevent="deleteAcademicItem($event.target)
                                      .then(() => { removed = true; scheduleCount = Math.max(0, scheduleCount - 1); confirmingScheduleRemoval = null; showToast('success', 'Schedule removed', 'Schedule was removed.'); })
                                      .catch(() => showToast('error', 'Remove failed', 'Unable to remove schedule.'))">
                                @csrf
                                @method('DELETE')
                                <p class="text-xs font-semibold text-red-100">Remove this schedule?</p>
                                <div class="mt-3 flex justify-end gap-2">
                                    <button type="button"
                                            @click="confirmingScheduleRemoval = null"
                                            class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                        Cancel
                                    </button>
                                    <button class="rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-600">
                                        Confirm
                                    </button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <p x-show="addedSchedules.length === 0" class="text-sm text-slate-300">No schedules assigned yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section x-show="section === 'heads'" x-cloak class="grid grid-cols-12 gap-5">
            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-5">
                <h3 class="font-extrabold text-white">Department Head</h3>
                <p class="mt-1 text-xs text-slate-300">The active name auto-fills in enrollment forms and outputs.</p>

                <form action="{{ route('academic.department-heads.store') }}"
                      method="POST"
                      class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-1"
                      @submit.prevent="submitAcademicForm($event.target)
                          .then((data) => { addDepartmentHead(data.department_head); $event.target.reset(); showToast('success', 'Department head saved', 'Department head was updated.'); })
                          .catch((error) => showToast('error', 'Save failed', error.message))">
                    @csrf
                    <select name="course_code" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        @foreach(['BSIT', 'BSCS', 'ACT', 'BSHM', 'BSOM', 'BSA'] as $courseCode)
                            <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                        @endforeach
                    </select>
                    <input name="name" placeholder="Department Head Name" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input name="title" placeholder="Title, optional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm sm:col-span-2 lg:col-span-1">
                    <button class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white sm:col-span-2 lg:col-span-1">Save Department Head</button>
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
                        { key: 'course_BSIT', label: 'BSIT Check', type: 'check' },
                        { key: 'course_BSCS', label: 'BSCS Check', type: 'check' },
                        { key: 'course_ACT', label: 'ACT Check', type: 'check' },
                        { key: 'course_BSHM', label: 'BSHM Check', type: 'check' },
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
                 })"
                 x-init="init()"
                 class="grid grid-cols-12 gap-5">
            <aside class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-3">
                <h3 class="font-extrabold text-white">Enrollment Template</h3>
                <p class="mt-1 text-xs text-slate-300">Upload the current school form, then place data fields on the PDF.</p>

                <form action="{{ route('academic.templates.store') }}"
                      method="POST"
                      enctype="multipart/form-data"
                      class="mt-4 space-y-3"
                      @submit.prevent="uploadTemplate($event.target).catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Upload failed', message: error.message } })))">
                    @csrf
                    <input name="name" placeholder="Template name" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input type="file" name="template_pdf" accept="application/pdf" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0]">
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
                            <p class="mt-2 text-xs text-slate-300">
                                <span x-text="template.page_width"></span> x <span x-text="template.page_height"></span> PDF units
                            </p>
                        </div>
                    </template>
                    <p x-show="!template" class="mt-2 text-xs text-slate-400">No PDF uploaded yet.</p>
                </div>

                <div class="mt-4">
                    <div class="mb-2 flex items-center justify-between">
                        <h4 class="text-sm font-extrabold text-white">Fields</h4>
                        <span class="text-xs text-slate-400" x-text="`${mappedFields().length}/${fields.length}`"></span>
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

            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 xl:col-span-6">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="font-extrabold text-white">PDF Mapper</h3>
                        <p class="mt-1 text-xs text-slate-300">Select a field, then click or drag it onto the form.</p>
                    </div>
                    <button type="button"
                            @click="saveMappings().catch((error) => window.dispatchEvent(new CustomEvent('dashboard-toast', { detail: { type: 'error', title: 'Save failed', message: error.message } })))"
                            :disabled="!template || saving"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:opacity-50">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        <span x-text="saving ? 'Saving...' : 'Save Mapping'"></span>
                    </button>
                </div>

                <div x-show="!template" class="flex h-[560px] items-center justify-center rounded-2xl border border-dashed border-white/15 bg-white/5 text-center">
                    <div>
                        <i data-lucide="file-up" class="mx-auto h-10 w-10 text-blue-200"></i>
                        <p class="mt-3 text-sm font-bold text-white">Upload a PDF template first</p>
                        <p class="mt-1 text-xs text-slate-400">The mapper will render page 1 here.</p>
                    </div>
                </div>

                <div x-show="template" class="h-[560px] overflow-auto rounded-2xl border border-white/10 bg-slate-950/50 p-4">
                    <div x-show="loadingPdf" class="mb-3 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200">
                        Rendering PDF...
                    </div>
                    <div x-ref="canvasWrap"
                         @click="placeSelected($event)"
                         class="relative mx-auto w-max cursor-crosshair overflow-hidden rounded-xl bg-white shadow-2xl shadow-black/30">
                        <canvas x-ref="pdfCanvas"></canvas>
                        <template x-for="mapping in mappedFields()" :key="mapping.key">
                            <button type="button"
                                    @pointerdown.stop="startDrag($event, mapping.key)"
                                    :style="markerStyle(mapping)"
                                    :class="mapping.type === 'check' ? 'border-emerald-300/50 bg-emerald-500/90' : 'border-blue-200/60 bg-[#1552d4]/95'"
                                    class="absolute z-10 -translate-x-1 -translate-y-1 rounded-lg border px-2 py-1 text-[10px] font-extrabold text-white shadow-lg">
                                <span x-text="mapping.label"></span>
                            </button>
                        </template>
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
                                        X: <span x-text="mapping.x"></span>
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
        </section>
    </div>
</div>
