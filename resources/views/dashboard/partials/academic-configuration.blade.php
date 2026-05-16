<div class="space-y-5">
    <div class="grid grid-cols-12 gap-5">
        <div class="col-span-12 xl:col-span-5 rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
            <div class="mb-4">
                <h2 class="font-extrabold text-white">Add Subject</h2>
                <p class="mt-1 text-xs text-slate-300">Subjects are assigned by course, year level, and semester.</p>
            </div>

            <form action="{{ route('academic.subjects.store') }}" method="POST" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
                        @foreach(['1', '2', '3', '4'] as $year)
                            <option value="{{ $year }}">{{ $year }} Year</option>
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
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-slate-300">Description</label>
                    <input name="description" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <button class="inline-flex items-center gap-2 rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0f43b0]">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        Save Subject
                    </button>
                </div>
            </form>
        </div>

        <div class="col-span-12 xl:col-span-7 rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-extrabold text-white">Subjects</h2>
                    <p class="mt-1 text-xs text-slate-300">{{ $subjects->count() }} configured subjects.</p>
                </div>
            </div>

            <div class="max-h-[520px] overflow-auto rounded-2xl border border-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-xs uppercase text-slate-300">
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
                            <tr>
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
                                                    @foreach(['1', '2', '3', '4'] as $year)
                                                        <option value="{{ $year }}" @selected($subject->year_level === $year)>Year {{ $year }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="semester" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                                    @foreach(['1st', '2nd', 'Summer'] as $semester)
                                                        <option value="{{ $semester }}" @selected($subject->semester === $semester)>{{ $semester }}</option>
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
    </div>

    <div class="grid grid-cols-12 gap-5">
        <div class="col-span-12 lg:col-span-4 rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
            <h2 class="font-extrabold text-white">Schedule Options</h2>
            <p class="mt-1 text-xs text-slate-300">Add the day, time, and room choices used in subject schedules.</p>

            <form action="{{ route('academic.days.store') }}" method="POST" class="mt-4 flex gap-2">
                @csrf
                <input name="name" placeholder="Day, e.g. Monday" class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <button class="rounded-lg border border-slate-200 px-3 text-xs font-bold text-slate-700">Add</button>
            </form>

            <form action="{{ route('academic.time-slots.store') }}" method="POST" class="mt-3 grid grid-cols-2 gap-2">
                @csrf
                <input type="time" name="start_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input type="time" name="end_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input name="label" placeholder="Label" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <button class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Add Time Slot</button>
            </form>

            <form action="{{ route('academic.rooms.store') }}" method="POST" class="mt-3 grid grid-cols-2 gap-2">
                @csrf
                <input name="name" placeholder="Room" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input name="building" placeholder="Building" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input type="number" min="1" name="capacity" placeholder="Capacity" class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <button class="col-span-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700">Add Room</button>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-4 rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
            <h2 class="font-extrabold text-white">Assign Schedule</h2>
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
                <button class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white">Assign Schedule</button>
            </form>
        </div>

        <div class="col-span-12 lg:col-span-4 rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
            <h2 class="font-extrabold text-white">Department Heads</h2>
            <p class="mt-1 text-xs text-slate-300">The active name auto-fills in enrollment forms.</p>
            <form action="{{ route('academic.department-heads.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <select name="course_code" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    @foreach(['BSIT', 'BSCS', 'ACT', 'BSHM', 'BSOM', 'BSA'] as $courseCode)
                        <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                    @endforeach
                </select>
                <input name="name" placeholder="Department Head Name" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <input name="title" placeholder="Title, optional" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <button class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white">Save Department Head</button>
            </form>
            <div class="mt-4 space-y-2">
                @forelse($departmentHeads as $head)
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-xs">
                        <p class="font-bold text-white">{{ $head->course_code }} - {{ $head->name }}</p>
                        <p class="text-slate-400">{{ $head->title ?? 'Department Head' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-300">No active department heads yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-white/10 bg-gradient-to-br from-[#17213a]/95 to-[#071224]/95 p-5 shadow-2xl shadow-black/30">
        <h2 class="font-extrabold text-white">Assigned Schedules</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse($subjectSchedules as $schedule)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-3 text-sm">
                    <p class="font-bold text-white">{{ $schedule->subject->code }} - {{ $schedule->subject->name }}</p>
                    <p class="mt-1 text-xs text-slate-300">{{ $schedule->day->name }} / {{ $schedule->timeSlot->label ?? ($schedule->timeSlot->start_time . ' - ' . $schedule->timeSlot->end_time) }} / {{ $schedule->room->name }}</p>
                    <form action="{{ route('academic.schedules.destroy', $schedule) }}" method="POST" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button class="text-xs font-bold text-red-600">Remove schedule</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-slate-300">No schedules assigned yet.</p>
            @endforelse
        </div>
    </div>
</div>
