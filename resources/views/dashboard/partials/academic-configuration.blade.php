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

<div x-data="{ section: 'subjects', course: '', year: '', semester: '' }"
     class="academic-config-frame overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 shadow-2xl shadow-black/30">
    <div class="border-b border-white/10 px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200">Academic Setup</p>
                <h2 class="mt-1 text-xl font-extrabold text-white">Configuration</h2>
                <p class="mt-1 text-xs text-slate-300">Manage curriculum, schedules, rooms, and department heads in one workspace.</p>
            </div>

            <div class="grid grid-cols-3 gap-2 rounded-2xl border border-white/10 bg-white/5 p-1">
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

                <form action="{{ route('academic.subjects.store') }}" method="POST" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
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
                        <p class="mt-1 text-xs text-slate-300">{{ $subjects->count() }} configured subjects.</p>
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
                            @forelse($subjects as $subject)
                                <tr x-show="(!course || course === @js($subject->course_code)) && (!year || year === @js($subject->year_level)) && (!semester || semester === @js($subject->semester))">
                                    <td class="px-3 py-3">
                                        <p class="font-bold text-white">{{ $subject->code }}</p>
                                        <p class="text-xs text-slate-400">{{ $subject->name }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        {{ $subject->course_code }} / Year {{ $subject->year_level }} / {{ $subject->semester }}
                                        <p class="mt-1 font-semibold text-blue-100">{{ $subject->type }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        {{ $subject->total_units }} total
                                        <p class="text-slate-500">{{ $subject->lecture_units }} LEC / {{ $subject->laboratory_units }} LAB</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-300">
                                        @forelse($subject->schedules as $schedule)
                                            <p>{{ $schedule->day->name }} {{ $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . '-' . $schedule->timeSlot->end_time) }} / {{ $schedule->room->name }}</p>
                                        @empty
                                            <span class="text-slate-500">No schedule</span>
                                        @endforelse
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <details class="inline-block text-left">
                                            <summary class="cursor-pointer rounded-2xl border border-white/10 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white">Edit</summary>
                                            <div class="absolute right-10 z-20 mt-2 w-[520px] rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
                                                <form action="{{ route('academic.subjects.update', $subject) }}" method="POST" class="grid grid-cols-2 gap-3">
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
                                                    <div class="col-span-2 flex justify-end gap-2">
                                                        <button class="rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white">Update</button>
                                                    </div>
                                                </form>
                                                <form action="{{ route('academic.subjects.destroy', $subject) }}" method="POST" class="mt-2 text-right">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="text-xs font-bold text-red-600">Remove subject</button>
                                                </form>
                                            </div>
                                        </details>
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

                <form action="{{ route('academic.days.store') }}" method="POST" class="mt-4 flex gap-2">
                    @csrf
                    <input name="name" placeholder="Day, e.g. Monday" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="rounded-lg bg-[#1552d4] px-3 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Add</button>
                </form>

                <form action="{{ route('academic.time-slots.store') }}" method="POST" class="mt-3 grid grid-cols-2 gap-2">
                    @csrf
                    <input type="time" name="start_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input type="time" name="end_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <input name="label" placeholder="Label" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <button class="col-span-2 rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">Add Time Slot</button>
                </form>

                <form action="{{ route('academic.rooms.store') }}" method="POST" class="mt-3 grid grid-cols-2 gap-2">
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

                <form action="{{ route('academic.schedules.store') }}" method="POST" class="mt-4 space-y-3">
                    @csrf
                    <select name="subject_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->name }}</option>
                        @endforeach
                    </select>
                    <select name="day_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select day</option>
                        @foreach($days as $day)
                            <option value="{{ $day->id }}">{{ $day->name }}</option>
                        @endforeach
                    </select>
                    <select name="time_slot_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select time</option>
                        @foreach($timeSlots as $slot)
                            <option value="{{ $slot->id }}">{{ $slot->label ?? ($slot->start_time . ' - ' . $slot->end_time) }}</option>
                        @endforeach
                    </select>
                    <select name="room_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Select room</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}">{{ $room->name }}</option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white">Assign Schedule</button>
                </form>
            </div>

            <div class="col-span-12 h-[430px] overflow-hidden rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-4">
                <h3 class="font-extrabold text-white">Assigned Schedules</h3>
                <p class="mt-1 text-xs text-slate-300">{{ $subjectSchedules->count() }} current schedule entries.</p>

                <div class="mt-4 h-[390px] space-y-2 overflow-y-auto pr-1">
                    @forelse($subjectSchedules as $schedule)
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-3 text-sm">
                            <p class="font-bold text-white">{{ $schedule->subject->code }} - {{ $schedule->subject->name }}</p>
                            <p class="mt-1 text-xs text-slate-300">{{ $schedule->day->name }} / {{ $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time) }} / {{ $schedule->room->name }}</p>
                            <form action="{{ route('academic.schedules.destroy', $schedule) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs font-bold text-red-300 hover:text-red-100">Remove schedule</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-300">No schedules assigned yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section x-show="section === 'heads'" x-cloak class="grid grid-cols-12 gap-5">
            <div class="col-span-12 rounded-2xl border border-white/10 bg-white/5 p-4 lg:col-span-5">
                <h3 class="font-extrabold text-white">Department Head</h3>
                <p class="mt-1 text-xs text-slate-300">The active name auto-fills in enrollment forms and outputs.</p>

                <form action="{{ route('academic.department-heads.store') }}" method="POST" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
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
                        <p class="mt-1 text-xs text-slate-300">{{ $departmentHeads->count() }} configured active records.</p>
                    </div>
                </div>

                <div class="mt-4 grid max-h-[430px] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
                    @forelse($departmentHeads as $head)
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs">
                            <p class="font-bold text-white">{{ $head->course_code }}</p>
                            <p class="mt-1 text-sm font-semibold text-blue-100">{{ $head->name }}</p>
                            <p class="text-slate-400">{{ $head->title ?? 'Department Head' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-300">No active department heads yet.</p>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
