            @php
                $scheduleBatchOptions = ['Whole Class', 'Batch 1', 'Batch 2', 'Batch 3', 'Batch 4', 'Batch 5'];
            @endphp

            <div class="col-span-12 grid gap-5">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h3 class="font-extrabold text-white">Assign Schedule</h3>
                        <p class="mt-1 text-xs text-slate-300">Choose a fixed day, time, instructor, room, and batch.</p>

                        <form action="{{ route('academic.schedules.store') }}"
                              method="POST"
                              x-data="{ ...dirtyFormState(), confirmingOverwrite: false, overwriteMessage: '', confirmingAdditional: false, additionalMessage: '', confirmingMerge: false, mergeMessage: '', mergeCurrentClass: '', mergePhrase: '' }"
                              class="mt-4 grid gap-3 lg:grid-cols-12"
                              @submit.prevent="confirmingOverwrite = false; confirmingAdditional = false; confirmingMerge = false; mergePhrase = ''; submitScheduleForm($event.target)
                                  .then((data) => { applyScheduleResponse(data); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); $event.target.reset(); $nextTick(() => markClean()); showToast('success', data.overwritten ? 'Schedule replaced' : (data.merged ? 'Schedule merged' : (data.additional ? 'Meeting added' : 'Schedule assigned')), data.overwritten ? 'The previous schedule was overwritten.' : (data.merged ? 'Class was merged with the matching schedule.' : (data.additional ? 'The subject now has another weekly meeting.' : 'No conflicts detected.'))); })
                                  .catch((error) => { if (error.requiresScheduleOverwrite) { overwriteMessage = error.message; confirmingOverwrite = true; return; } if (error.requiresAdditionalSchedule) { additionalMessage = error.message; confirmingAdditional = true; return; } if (error.requiresScheduleMerge) { mergeMessage = error.message; mergeCurrentClass = error.currentScheduledClass; mergePhrase = ''; confirmingMerge = true; return; } showToast('error', 'Schedule conflict', error.message); })">
                            @csrf
                            <div class="lg:col-span-4">
                                <input type="hidden"
                                       name="subject_id"
                                       required
                                       x-ref="scheduleSubjectId">
                                <input type="hidden"
                                       name="schedule_type"
                                       required
                                       x-ref="scheduleType">
                                <input type="search"
                                       list="schedule-subject-options"
                                       required
                                       placeholder="Search or select subject"
                                       autocomplete="off"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                       @input="$refs.scheduleSubjectId.value = resolveScheduleSubjectId($event.target.value); $refs.scheduleType.value = resolveScheduleType($event.target.value)"
                                       @change="$refs.scheduleSubjectId.value = resolveScheduleSubjectId($event.target.value); $refs.scheduleType.value = resolveScheduleType($event.target.value)">
                            </div>
                            <select name="instructor"
                                    required
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm lg:col-span-4">
                                <option value="">Select instructor</option>
                                <template x-for="instructor in scheduleInstructorOptions" :key="`schedule-instructor-select-${instructor}`">
                                    <option :value="instructor" x-text="instructor"></option>
                                </template>
                            </select>
                            <select name="room_name"
                                    required
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm lg:col-span-4">
                                <option value="">Select room</option>
                                <template x-for="room in addedRooms" :key="`room-select-${room.id}`">
                                    <option :value="room.name" x-text="room.name"></option>
                                </template>
                            </select>
                            <label class="grid gap-1 lg:col-span-3">
                                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-300">Batch</span>
                                <select name="schedule_for"
                                        required
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    @foreach($scheduleBatchOptions as $option)
                                        <option value="{{ $option }}" @selected($option === 'Whole Class')>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 lg:col-span-2">
                                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-300">From</span>
                                <input type="time" name="start_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </label>
                            <label class="grid gap-1 lg:col-span-2">
                                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-300">To</span>
                                <input type="time" name="end_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </label>
                            <div class="grid gap-1 lg:col-span-5">
                                <span class="text-[11px] font-bold uppercase tracking-wide text-slate-300">Days</span>
                                <div class="grid grid-cols-7 gap-2">
                                    @foreach($days as $day)
                                        @php
                                            $dayCode = match (strtolower($day->name)) {
                                                'monday' => 'M',
                                                'tuesday' => 'T',
                                                'wednesday' => 'W',
                                                'thursday' => 'TH',
                                                'friday' => 'FRI',
                                                'saturday' => 'SAT',
                                                'sunday' => 'SUN',
                                                default => strtoupper($day->name),
                                            };
                                        @endphp
                                        <label class="group relative">
                                            <input type="checkbox"
                                                   name="day_ids[]"
                                                   value="{{ $day->id }}"
                                                   class="peer sr-only">
                                            <span class="flex h-10 cursor-pointer items-center justify-center rounded-lg border border-white/10 bg-white/10 text-xs font-extrabold text-slate-300 transition peer-checked:border-blue-300/60 peer-checked:bg-[#1552d4] peer-checked:text-white group-hover:bg-white/15">
                                                {{ $dayCode }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <datalist id="schedule-subject-options">
                                <template x-for="subject in allScheduleSubjectOptions()" :key="`schedule-subject-option-${subject.id}-${subject.schedule_type}`">
                                    <option :value="subject.label"></option>
                                </template>
                            </datalist>
                            <div x-show="confirmingOverwrite"
                                 x-cloak
                                 class="lg:col-span-12 rounded-2xl border border-amber-300/20 bg-amber-500/10 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-extrabold text-amber-100">Replace existing schedule?</p>
                                        <p class="mt-1 text-xs text-amber-100/80" x-text="overwriteMessage"></p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button type="button"
                                                @click="confirmingOverwrite = false"
                                                class="rounded-lg border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 hover:bg-white/15">
                                            Cancel
                                        </button>
                                        <button type="button"
                                                @click="submitScheduleForm($el.closest('form'), true)
                                                    .then((data) => { applyScheduleResponse(data); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); confirmingOverwrite = false; $el.closest('form').reset(); $nextTick(() => markClean()); showToast('success', 'Schedule replaced', 'The previous schedule was overwritten.'); })
                                                    .catch((error) => showToast('error', 'Schedule conflict', error.message))"
                                                class="rounded-lg bg-amber-400 px-4 py-2 text-xs font-extrabold text-slate-950 hover:bg-amber-300">
                                            Replace
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="confirmingAdditional"
                                 x-cloak
                                 class="lg:col-span-12 rounded-2xl border border-emerald-300/20 bg-emerald-500/10 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-extrabold text-emerald-100">Add another meeting?</p>
                                        <p class="mt-1 text-xs text-emerald-100/80" x-text="additionalMessage"></p>
                                    </div>
                                    <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                        <button type="button"
                                                @click="confirmingAdditional = false"
                                                class="rounded-lg border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 hover:bg-white/15">
                                            Cancel
                                        </button>
                                        <button type="button"
                                                @click="submitScheduleForm($el.closest('form'), true)
                                                    .then((data) => { applyScheduleResponse(data); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); confirmingAdditional = false; $el.closest('form').reset(); $nextTick(() => markClean()); showToast('success', 'Schedule replaced', 'The previous schedule was overwritten.'); })
                                                    .catch((error) => showToast('error', 'Schedule conflict', error.message))"
                                                class="rounded-lg border border-amber-300/40 bg-amber-400/15 px-4 py-2 text-xs font-extrabold text-amber-100 hover:bg-amber-400/25">
                                            Replace
                                        </button>
                                        <button type="button"
                                                @click="submitScheduleForm($el.closest('form'), false, false, true)
                                                    .then((data) => { applyScheduleResponse(data); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); confirmingAdditional = false; $el.closest('form').reset(); $nextTick(() => markClean()); showToast('success', 'Meeting added', 'The subject now has another weekly meeting.'); })
                                                    .catch((error) => showToast('error', 'Schedule conflict', error.message))"
                                                class="rounded-lg bg-emerald-400 px-4 py-2 text-xs font-extrabold text-slate-950 hover:bg-emerald-300">
                                            Add Meeting
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div x-show="confirmingMerge"
                                 x-cloak
                                 class="lg:col-span-12 rounded-2xl border border-cyan-300/20 bg-cyan-500/10 p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                                    <div class="max-w-3xl">
                                        <p class="text-sm font-extrabold text-cyan-100">Merge with current scheduled class?</p>
                                        <p class="mt-1 text-xs text-cyan-100/80" x-text="mergeMessage"></p>
                                        <p class="mt-2 text-xs font-bold text-cyan-50">
                                            Current scheduled class: <span x-text="mergeCurrentClass"></span>
                                        </p>
                                    </div>
                                    <div class="grid gap-2 sm:min-w-[260px]">
                                        <input type="text"
                                               x-model="mergePhrase"
                                               @keydown.enter.prevent.stop="if (! $refs.mergeConfirmButton.disabled) $refs.mergeConfirmButton.click()"
                                               placeholder="Type merge"
                                               autocomplete="off"
                                               class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                        <div class="flex justify-end gap-2">
                                            <button type="button"
                                                    @click="confirmingMerge = false; mergePhrase = ''"
                                                    class="rounded-lg border border-white/10 bg-white/10 px-4 py-2 text-xs font-bold text-slate-200 hover:bg-white/15">
                                                Cancel
                                            </button>
                                            <button type="button"
                                                    x-ref="mergeConfirmButton"
                                                    :disabled="mergePhrase.trim().toLowerCase() !== 'merge'"
                                                    @click="submitScheduleForm($el.closest('form'), false, true)
                                                        .then((data) => { applyScheduleResponse(data); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); confirmingMerge = false; mergePhrase = ''; $el.closest('form').reset(); $nextTick(() => markClean()); showToast('success', 'Schedule merged', 'Class was merged with the matching schedule.'); })
                                                        .catch((error) => showToast('error', 'Schedule conflict', error.message))"
                                                    class="rounded-lg bg-cyan-400 px-4 py-2 text-xs font-extrabold text-slate-950 hover:bg-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                                                Confirm Merge
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button :disabled="!dirty || !requiredComplete()" class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60 lg:col-span-12">Assign Schedule</button>
                        </form>

                    </div>

                    <div class="h-[575px] overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        <div class="border-b border-white/10 p-4">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <h3 class="font-extrabold text-white">Live Schedule View</h3>
                                    <p class="mt-1 text-xs text-slate-300"><span x-text="scheduleCount"></span> entries. Review and remove schedules from this table.</p>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-6">
                                    <input type="search" x-model="scheduleLiveSearch" placeholder="Search" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                    <select x-model="scheduleArchiveYear" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="">Current A.Y.</option>
                                        <template x-for="year in archivedScheduleYears" :key="`schedule-archive-${year}`">
                                            <option :value="year" x-text="`Archive ${year}`"></option>
                                        </template>
                                    </select>
                                    <select x-model="scheduleCourseFilter" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="">All Courses</option>
                                        @foreach($subjects->pluck('course_code')->filter()->unique()->sort()->values() as $courseCode)
                                            <option value="{{ $courseCode }}">{{ $courseCode }}</option>
                                        @endforeach
                                    </select>
                                    <select x-model="scheduleYearFilter" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="">All Years</option>
                                        @foreach($subjects->pluck('year_level')->filter()->unique()->sort()->values() as $yearLevel)
                                            <option value="{{ $yearLevel }}">{{ $yearLevel }}</option>
                                        @endforeach
                                    </select>
                                    <select x-model="scheduleSemesterFilter" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="">All Semesters</option>
                                        <option value="1st">1st</option>
                                        <option value="2nd">2nd</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                    <select x-model="scheduleDayFilter" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                        <option value="">All Days</option>
                                        @foreach($days as $day)
                                            <option value="{{ $day->name }}">{{ $day->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="h-[470px] overflow-auto">
                            <table class="w-full min-w-[1180px] text-sm">
                                <thead class="sticky top-0 z-10 bg-[#101a2d] text-xs uppercase tracking-wide text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-bold">Code</th>
                                        <th class="px-4 py-3 text-left font-bold">Subject</th>
                                        <th class="px-4 py-3 text-left font-bold">Group</th>
                                        <th class="px-4 py-3 text-left font-bold">For</th>
                                        <th class="px-4 py-3 text-left font-bold">Day</th>
                                        <th class="px-4 py-3 text-left font-bold">Time</th>
                                        <th class="px-4 py-3 text-left font-bold">Room</th>
                                        <th class="px-4 py-3 text-left font-bold">Instructor</th>
                                        <th class="w-44 px-4 py-3 text-right font-bold">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/10">
                                    <template x-for="schedule in filteredScheduleRows()" :key="`live-${schedule.id}`">
                                        <tr class="hover:bg-white/5" :class="confirmingScheduleRemoval === schedule.id ? 'bg-red-500/10' : (editingSchedule === schedule.id ? 'bg-blue-500/10' : '')">
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 font-mono text-xs font-bold text-blue-100" x-text="schedule.subject?.code || 'N/A'"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3">
                                                    <p class="font-semibold text-white" x-text="schedule.subject_display_name || schedule.subject?.name || 'No subject'"></p>
                                                </td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs text-slate-300" x-text="`${schedule.subject?.course_code || ''} / ${schedule.subject?.year_level || ''} / ${schedule.subject?.semester || ''}`"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs font-semibold text-blue-100" x-text="schedule.schedule_for || 'Whole Class'"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs font-bold text-white" x-text="schedule.day_label || schedule.day"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs text-slate-300" x-text="schedule.time"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs font-semibold text-white" x-text="schedule.room"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs text-slate-300" x-text="schedule.instructor"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                                    <template x-if="!scheduleArchiveYear">
                                                        <div class="inline-flex items-center gap-2">
                                                            <button type="button"
                                                            @click="editingSchedule = schedule.id; confirmingScheduleRemoval = null"
                                                            class="mr-2 inline-flex items-center gap-2 rounded-lg border border-blue-300/20 bg-blue-500/10 px-3 py-2 text-xs font-bold text-blue-100 transition hover:bg-blue-500/20">
                                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                                Edit
                                                            </button>
                                                            <button type="button"
                                                            @click="confirmingScheduleRemoval = schedule.id; editingSchedule = null"
                                                            class="inline-flex items-center gap-2 rounded-lg border border-red-300/20 bg-red-500/10 px-3 py-2 text-xs font-bold text-red-100 transition hover:bg-red-500/20">
                                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                                                Remove
                                                            </button>
                                                        </div>
                                                    </template>
                                                    <span x-show="scheduleArchiveYear" class="inline-flex rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-300">
                                                        Archived
                                                    </span>
                                                </td>
                                            </template>
                                            <template x-if="editingSchedule === schedule.id">
                                                <td colspan="9" class="px-4 py-3">
                                                    <form :action="schedule.update_url"
                                                          method="POST"
                                                          x-data="{ ...dirtyFormState(), subjectQuery: scheduleSubjectLabel(schedule.subject?.id, schedule.schedule_type), selectedSubjectId: schedule.subject?.id, selectedScheduleType: schedule.schedule_type, confirmingMerge: false, mergeMessage: '', mergeCurrentClass: '', mergePhrase: '' }"
                                                          class="grid gap-3 rounded-2xl border border-blue-300/20 bg-blue-500/10 p-3 lg:grid-cols-6"
                                                          @submit.prevent="confirmingMerge = false; mergePhrase = ''; submitScheduleForm($event.target)
                                                              .then((data) => { applyScheduleResponse(data); editingSchedule = null; showToast('success', data.merged ? 'Schedule merged' : 'Schedule updated', data.merged ? 'Class was merged with the matching schedule.' : 'No conflicts detected.'); })
                                                              .catch((error) => { if (error.requiresScheduleMerge) { mergeMessage = error.message; mergeCurrentClass = error.currentScheduledClass; mergePhrase = ''; confirmingMerge = true; return; } showToast('error', 'Update failed', error.message); })">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="lg:col-span-2">
                                                            <input type="hidden" name="subject_id" x-model="selectedSubjectId">
                                                            <input type="hidden" name="schedule_type" x-model="selectedScheduleType">
                                                            <input type="search"
                                                                   list="schedule-subject-options"
                                                                   required
                                                                   x-model="subjectQuery"
                                                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs"
                                                                   @input="selectedSubjectId = resolveScheduleSubjectId(subjectQuery); selectedScheduleType = resolveScheduleType(subjectQuery)"
                                                                   @change="selectedSubjectId = resolveScheduleSubjectId(subjectQuery); selectedScheduleType = resolveScheduleType(subjectQuery)">
                                                        </div>
                                                        <select name="schedule_for"
                                                                required
                                                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                            @foreach($scheduleBatchOptions as $option)
                                                                <option value="{{ $option }}" :selected="(schedule.schedule_for || 'Whole Class') === '{{ $option }}'">{{ $option }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select name="instructor"
                                                                required
                                                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                            <template x-for="instructor in scheduleInstructorOptions" :key="`edit-schedule-instructor-${schedule.id}-${instructor}`">
                                                                <option :value="instructor" :selected="schedule.instructor === instructor" x-text="instructor"></option>
                                                            </template>
                                                        </select>
                                                        <div class="grid grid-cols-7 gap-2 lg:col-span-2">
                                                            @foreach($days as $day)
                                                                @php
                                                                    $dayCode = match (strtolower($day->name)) {
                                                                        'monday' => 'M',
                                                                        'tuesday' => 'T',
                                                                        'wednesday' => 'W',
                                                                        'thursday' => 'TH',
                                                                        'friday' => 'FRI',
                                                                        'saturday' => 'SAT',
                                                                        'sunday' => 'SUN',
                                                                        default => strtoupper($day->name),
                                                                    };
                                                                @endphp
                                                                <label class="group relative">
                                                                    <input type="checkbox"
                                                                           name="day_ids[]"
                                                                           value="{{ $day->id }}"
                                                                           :checked="(schedule.day_ids || [schedule.day_id]).map(Number).includes({{ $day->id }})"
                                                                           class="peer sr-only">
                                                                    <span class="flex h-9 cursor-pointer items-center justify-center rounded-lg border border-white/10 bg-white/10 text-[11px] font-extrabold text-slate-300 transition peer-checked:border-blue-300/60 peer-checked:bg-[#1552d4] peer-checked:text-white group-hover:bg-white/15">
                                                                        {{ $dayCode }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                        <input type="time" name="start_time" required :value="schedule.start_time" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                                        <input type="time" name="end_time" required :value="schedule.end_time" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                                        <select name="room_name"
                                                                required
                                                                class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                            <template x-for="room in addedRooms" :key="`edit-schedule-room-${schedule.id}-${room.id}`">
                                                                <option :value="room.name" :selected="schedule.room === room.name" x-text="room.name"></option>
                                                            </template>
                                                        </select>
                                                        <div x-show="confirmingMerge"
                                                             x-cloak
                                                             class="rounded-2xl border border-cyan-300/20 bg-cyan-500/10 p-3 lg:col-span-6">
                                                            <div class="grid gap-3 lg:grid-cols-[1fr_260px] lg:items-end">
                                                                <div>
                                                                    <p class="text-xs font-extrabold text-cyan-100">Merge with current scheduled class?</p>
                                                                    <p class="mt-1 text-xs text-cyan-100/80" x-text="mergeMessage"></p>
                                                                    <p class="mt-2 text-xs font-bold text-cyan-50">
                                                                        Current scheduled class: <span x-text="mergeCurrentClass"></span>
                                                                    </p>
                                                                </div>
                                                                <div class="grid gap-2">
                    <input type="text"
                           x-model="mergePhrase"
                           @keydown.enter.prevent.stop="if (! $refs.mergeConfirmButton.disabled) $refs.mergeConfirmButton.click()"
                           placeholder="Type merge"
                           autocomplete="off"
                           class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                                                    <div class="flex justify-end gap-2">
                                                                        <button type="button"
                                                                                @click="confirmingMerge = false; mergePhrase = ''"
                                                                                class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                                            Cancel
                                                                        </button>
                        <button type="button"
                                x-ref="mergeConfirmButton"
                                :disabled="mergePhrase.trim().toLowerCase() !== 'merge'"
                                @click="submitScheduleForm($el.closest('form'), false, true)
                                    .then((data) => { applyScheduleResponse(data); confirmingMerge = false; mergePhrase = ''; editingSchedule = null; showToast('success', 'Schedule merged', 'Class was merged with the matching schedule.'); })
                                                                                    .catch((error) => showToast('error', 'Update failed', error.message))"
                                                                                class="rounded-lg bg-cyan-400 px-3 py-2 text-xs font-extrabold text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                                                                            Confirm Merge
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="flex justify-end gap-2 lg:col-span-2">
                                                            <button type="button"
                                                                    @click="editingSchedule = null"
                                                                    class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                                Cancel
                                                            </button>
                                                            <button :disabled="!dirty || !requiredComplete()" class="rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0] disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60">
                                                                Save
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval === schedule.id">
                                                <td colspan="9" class="px-4 py-3">
                                                    <form :action="schedule.delete_url"
                                                          method="POST"
                                                          class="flex flex-col gap-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                          @submit.prevent="deleteAcademicItem($event.target)
                                                              .then((data) => { const ids = data.schedule_ids || [data.schedule_id || schedule.id]; addedSchedules = addedSchedules.filter((item) => !ids.includes(item.id)); scheduleRows = scheduleRows.filter((item) => !ids.includes(item.id)); scheduleCount = Math.max(0, scheduleCount - ids.length); confirmingScheduleRemoval = null; showToast('success', 'Schedule removed', 'Schedule was removed.'); })
                                                              .catch(() => showToast('error', 'Remove failed', 'Unable to remove schedule.'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <div>
                                                            <p class="text-xs font-bold text-red-50">Remove this schedule?</p>
                                                            <p class="mt-1 text-xs text-red-100/80" x-text="`${schedule.subject?.code || 'No code'} / ${schedule.schedule_for || 'Whole Class'} / ${schedule.day_label || schedule.day} / ${schedule.time} / ${schedule.room}`"></p>
                                                        </div>
                                                        <div class="flex justify-end gap-2">
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
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                    <tr x-show="filteredScheduleRows().length === 0">
                                        <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-300">No schedule matches the current filters.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
