            <div class="col-span-12 grid gap-5">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                        <h3 class="font-extrabold text-white">Assign Schedule</h3>
                        <p class="mt-1 text-xs text-slate-300">Choose a fixed day, enter From/To time, and type or select a room. New rooms are saved automatically.</p>

                        <form action="{{ route('academic.schedules.store') }}"
                              method="POST"
                              x-data="dirtyForm()"
                              class="mt-4 grid gap-3 lg:grid-cols-6"
                              @submit.prevent="submitAcademicForm($event.target)
                                  .then((data) => { addedSchedules.unshift(data.schedule); if (data.room && !addedRooms.some((room) => room.id === data.room.id)) addedRooms.push(data.room); if (data.time_slot && !addedTimeSlots.some((slot) => slot.id === data.time_slot.id)) addedTimeSlots.push(data.time_slot); scheduleCount++; $event.target.reset(); $nextTick(() => markClean()); showToast('success', 'Schedule assigned', 'No conflicts detected.'); })
                                  .catch((error) => showToast('error', 'Schedule conflict', error.message))">
                            @csrf
                            <div class="lg:col-span-2">
                                <input type="hidden"
                                       name="subject_id"
                                       required
                                       x-ref="scheduleSubjectId">
                                <input type="search"
                                       list="schedule-subject-options"
                                       required
                                       placeholder="Search or select subject"
                                       autocomplete="off"
                                       class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                                       @input="$refs.scheduleSubjectId.value = resolveScheduleSubjectId($event.target.value)"
                                       @change="$refs.scheduleSubjectId.value = resolveScheduleSubjectId($event.target.value)">
                            </div>
                            <input name="instructor" required placeholder="Instructor, e.g. Ms. Reyes" class="rounded-lg border border-slate-200 px-3 py-2 text-sm lg:col-span-2">
                            <label class="grid grid-cols-2 gap-2 lg:col-span-2">
                                <input type="time" name="start_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <input type="time" name="end_time" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                            </label>
                            <select name="day_id" required class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm lg:col-span-4">
                                <option value="">Select day</option>
                                @foreach($days as $day)
                                    <option value="{{ $day->id }}">{{ $day->name }}</option>
                                @endforeach
                            </select>
                            <input name="room_name"
                                   list="schedule-room-options"
                                   required
                                   placeholder="Room, e.g. 203"
                                   autocomplete="off"
                                   class="rounded-lg border border-slate-200 px-3 py-2 text-sm lg:col-span-2">
                            <datalist id="schedule-room-options">
                                @foreach($rooms as $room)
                                    <option value="{{ $room->name }}"></option>
                                @endforeach
                                <template x-for="room in addedRooms" :key="`room-datalist-${room.id}`">
                                    <option :value="room.name"></option>
                                </template>
                            </datalist>
                            <datalist id="schedule-subject-options">
                                <template x-for="subject in allScheduleSubjectOptions()" :key="`schedule-subject-option-${subject.id}`">
                                    <option :value="subject.label"></option>
                                </template>
                            </datalist>
                            <button :disabled="!dirty" class="rounded-lg bg-[#1552d4] px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:bg-slate-600 disabled:text-slate-300 disabled:opacity-60 lg:col-span-6">Assign Schedule</button>
                        </form>

                        <form action="{{ route('academic.schedules.pdf') }}"
                              method="GET"
                              target="_blank"
                              class="mt-4 grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 lg:grid-cols-[1fr_1fr_1fr_auto]">
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
                            <button class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-300/20 bg-blue-500/15 px-4 py-2 text-sm font-bold text-blue-100 transition hover:bg-blue-500/25">
                                <i data-lucide="file-down" class="h-4 w-4"></i>
                                PDF
                            </button>
                        </form>
                    </div>

                    <div class="h-[575px] overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                        <div class="border-b border-white/10 p-4">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <h3 class="font-extrabold text-white">Live Schedule View</h3>
                                    <p class="mt-1 text-xs text-slate-300"><span x-text="scheduleCount"></span> entries. Review and remove schedules from this table.</p>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                                    <input type="search" x-model="scheduleLiveSearch" placeholder="Search" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
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
                            <table class="w-full min-w-[1080px] text-sm">
                                <thead class="sticky top-0 z-10 bg-[#101a2d] text-xs uppercase tracking-wide text-slate-300">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-bold">Code</th>
                                        <th class="px-4 py-3 text-left font-bold">Subject</th>
                                        <th class="px-4 py-3 text-left font-bold">Group</th>
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
                                                    <p class="font-semibold text-white" x-text="schedule.subject?.name || 'No subject'"></p>
                                                </td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs text-slate-300" x-text="`${schedule.subject?.course_code || ''} / ${schedule.subject?.year_level || ''} / ${schedule.subject?.semester || ''}`"></td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval !== schedule.id && editingSchedule !== schedule.id">
                                                <td class="px-4 py-3 text-xs font-bold text-white" x-text="schedule.day"></td>
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
                                                </td>
                                            </template>
                                            <template x-if="editingSchedule === schedule.id">
                                                <td colspan="8" class="px-4 py-3">
                                                    <form :action="schedule.update_url"
                                                          method="POST"
                                                          x-data="{ subjectQuery: scheduleSubjectLabel(schedule.subject?.id), selectedSubjectId: schedule.subject?.id }"
                                                          class="grid gap-3 rounded-2xl border border-blue-300/20 bg-blue-500/10 p-3 lg:grid-cols-6"
                                                          @submit.prevent="submitAcademicForm($event.target)
                                                              .then((data) => { upsertSchedule(data.schedule); if (data.room && !addedRooms.some((room) => room.id === data.room.id)) addedRooms.push(data.room); editingSchedule = null; showToast('success', 'Schedule updated', 'No conflicts detected.'); })
                                                              .catch((error) => showToast('error', 'Update failed', error.message))">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="lg:col-span-2">
                                                            <input type="hidden" name="subject_id" x-model="selectedSubjectId">
                                                            <input type="search"
                                                                   list="schedule-subject-options"
                                                                   required
                                                                   x-model="subjectQuery"
                                                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-xs"
                                                                   @input="selectedSubjectId = resolveScheduleSubjectId(subjectQuery)"
                                                                   @change="selectedSubjectId = resolveScheduleSubjectId(subjectQuery)">
                                                        </div>
                                                        <input name="instructor" required :value="schedule.instructor" class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                        <select name="day_id" required x-init="$el.value = schedule.day_id" class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                            @foreach($days as $day)
                                                                <option value="{{ $day->id }}">{{ $day->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <input type="time" name="start_time" required :value="schedule.start_time" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                                        <input type="time" name="end_time" required :value="schedule.end_time" class="rounded-lg border border-slate-200 px-3 py-2 text-xs">
                                                        <input name="room_name"
                                                               list="schedule-room-options"
                                                               required
                                                               :value="schedule.room"
                                                               class="rounded-lg border border-slate-200 px-3 py-2 text-xs lg:col-span-2">
                                                        <div class="flex justify-end gap-2 lg:col-span-2">
                                                            <button type="button"
                                                                    @click="editingSchedule = null"
                                                                    class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                                Cancel
                                                            </button>
                                                            <button class="rounded-lg bg-[#1552d4] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#0f43b0]">
                                                                Save
                                                            </button>
                                                        </div>
                                                    </form>
                                                </td>
                                            </template>
                                            <template x-if="confirmingScheduleRemoval === schedule.id">
                                                <td colspan="8" class="px-4 py-3">
                                                    <form :action="schedule.delete_url"
                                                          method="POST"
                                                          class="flex flex-col gap-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-3 sm:flex-row sm:items-center sm:justify-between"
                                                          @submit.prevent="deleteAcademicItem($event.target)
                                                              .then(() => { addedSchedules = addedSchedules.filter((item) => item.id !== schedule.id); scheduleRows = scheduleRows.filter((item) => item.id !== schedule.id); scheduleCount = Math.max(0, scheduleCount - 1); confirmingScheduleRemoval = null; showToast('success', 'Schedule removed', 'Schedule was removed.'); })
                                                              .catch(() => showToast('error', 'Remove failed', 'Unable to remove schedule.'))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <div>
                                                            <p class="text-xs font-bold text-red-50">Remove this schedule?</p>
                                                            <p class="mt-1 text-xs text-red-100/80" x-text="`${schedule.subject?.code || 'No code'} / ${schedule.day} / ${schedule.time} / ${schedule.room}`"></p>
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
                                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-300">No schedule matches the current filters.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
            </div>
