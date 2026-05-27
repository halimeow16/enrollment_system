@php
    $compact = $compact ?? false;
@endphp

<div class="{{ $compact ? 'max-h-[374px]' : 'h-[calc(100vh-270px)] min-h-[450px]' }} overflow-y-auto overflow-x-hidden">
    <table class="w-full table-fixed text-sm">
        <thead class="sticky top-0 z-10">
            <tr class="bg-white/5 text-slate-300 text-xs uppercase tracking-wide">
                <th class="px-4 py-3 text-left font-semibold {{ $compact ? 'w-[30%]' : 'w-[15%]' }}">Student</th>
                <th class="px-4 py-3 text-left font-semibold {{ $compact ? 'w-[20%]' : 'w-[16%]' }}">Program</th>
                <th class="px-4 py-3 text-left font-semibold {{ $compact ? 'w-[16%]' : 'w-[9%]' }}">Year/Sem</th>
                <th class="px-4 py-3 text-left font-semibold {{ $compact ? 'w-[16%]' : 'w-[10%]' }}">Status</th>
                <th class="px-4 py-3 text-left font-semibold {{ $compact ? 'w-[18%]' : 'w-[10%]' }}">Date Filed</th>
                @unless($compact)
                    <th class="px-4 py-3 text-left font-semibold w-[17%]">Contact</th>
                    <th class="px-4 py-3 text-left font-semibold w-[14%]">Update</th>
                    <th class="px-4 py-3 text-right font-semibold w-[9%]">Actions</th>
                @endunless
            </tr>
        </thead>
        <tbody class="divide-y divide-white/10">
            @forelse ($enrollments as $enrollment)
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
                        $enrollment->enrollment_status,
                        $enrollment->email,
                            $enrollment->cellphone,
                        ]));
                    $editPayload = [
                        'id' => $enrollment->id,
                        'student_number' => $enrollment->student_number,
                        'date_filed' => $enrollment->date_filed?->format('Y-m-d'),
                        'school_year' => $enrollment->school_year,
                        'first_name' => $enrollment->first_name,
                        'middle_name' => $enrollment->middle_name,
                        'last_name' => $enrollment->last_name,
                        'cellphone' => $enrollment->cellphone,
                        'email' => $enrollment->email,
                        'last_school' => $enrollment->last_school,
                        'present_address' => $enrollment->present_address,
                        'barangay' => $enrollment->barangay,
                        'city' => $enrollment->city,
                        'province' => $enrollment->province,
                        'date_of_birth' => $enrollment->date_of_birth?->format('Y-m-d'),
                        'age' => $enrollment->age,
                        'place_of_birth' => $enrollment->place_of_birth,
                        'civil_status' => $enrollment->civil_status,
                        'gender' => $enrollment->gender,
                        'religion' => $enrollment->religion,
                        'father_name' => $enrollment->father_name,
                        'father_address' => $enrollment->father_address,
                        'father_cpNumber' => $enrollment->father_cpNumber,
                        'mother_name' => $enrollment->mother_name,
                        'mother_address' => $enrollment->mother_address,
                        'mother_cpNumber' => $enrollment->mother_cpNumber,
                        'course_code' => $enrollment->course_code,
                        'course_name' => $enrollment->course_name,
                        'year_level' => $enrollment->year_level,
                        'semester' => $enrollment->semester,
                        'department_head_name' => $enrollment->department_head_name,
                        'credentials' => $enrollment->credentials ?? [],
                    ];
                @endphp
                <tr class="hover:bg-white/5 transition-colors duration-100"
                    x-data="{ rowStatus: @js($enrollment->enrollment_status ?? 'pending'), selectedStatus: @js($enrollment->enrollment_status ?? 'pending'), savingStatus: false }"
                    @unless($compact) x-show="matchesEnrollment(@js($rowText), rowStatus)" @endunless>
                    <td class="px-4 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-300/20 flex items-center justify-center text-blue-100 text-xs font-bold shrink-0">
                                {{ strtoupper(substr($enrollment->first_name ?? $enrollment->last_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-white text-xs">{{ $name }}</p>
                                <p class="truncate text-slate-400 text-xs font-mono">{{ $enrollment->student_number ?? 'No student no.' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3.5">
                        <p class="truncate text-blue-100 text-xs font-semibold">{{ $enrollment->course_code ?? 'Not set' }}</p>
                        @unless($compact)
                            <p class="truncate text-slate-400 text-xs">{{ $enrollment->course_name ?? 'Course name unavailable' }}</p>
                        @endunless
                    </td>
                    <td class="px-4 py-3.5 text-slate-300 text-xs">
                        {{ $enrollment->year_level ? $enrollment->year_level . 'yr' : 'Year not set' }}
                        <span class="text-slate-500 mx-1">/</span>
                        {{ $enrollment->semester ?? 'Sem not set' }}
                    </td>
                    <td class="px-4 py-3.5">
                        @if($compact)
                            <span x-show="statusFor({{ $enrollment->id }}, @js($enrollment->enrollment_status ?? 'pending')) === 'pending'"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-amber-100 text-amber-700">
                                Pending
                            </span>
                            <span x-show="statusFor({{ $enrollment->id }}, @js($enrollment->enrollment_status ?? 'pending')) === 'enrolled'"
                                  x-cloak
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-emerald-100 text-emerald-700">
                                Enrolled
                            </span>
                            <span x-show="statusFor({{ $enrollment->id }}, @js($enrollment->enrollment_status ?? 'pending')) === 'cancelled'"
                                  x-cloak
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-slate-100 text-slate-500">
                                Cancelled
                            </span>
                        @else
                            <span x-show="rowStatus === 'pending'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-amber-100 text-amber-700">
                                Pending
                            </span>
                            <span x-show="rowStatus === 'enrolled'" x-cloak class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-emerald-100 text-emerald-700">
                                Enrolled
                            </span>
                            <span x-show="rowStatus === 'cancelled'" x-cloak class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize bg-slate-100 text-slate-500">
                                Cancelled
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-slate-400 text-xs">
                        {{ $enrollment->date_filed ? $enrollment->date_filed->format('M d, Y') : 'Not filed' }}
                    </td>
                    @unless($compact)
                        <td class="px-4 py-3.5 text-slate-400 text-xs">
                            <p class="truncate">{{ $enrollment->cellphone ?? 'No phone' }}</p>
                            <p class="truncate">{{ $enrollment->email ?? 'No email' }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <form action="{{ route('enrollments.status.update', $enrollment) }}"
                                  method="POST"
                                  @submit.prevent="savingStatus = true; updateEnrollmentStatus($event.target, selectedStatus)
                                      .then((status) => { setEnrollmentStatus({{ $enrollment->id }}, rowStatus, status); rowStatus = status; selectedStatus = status; showToast('success', 'Status updated', 'Enrollment status was saved.'); })
                                      .catch(() => { selectedStatus = rowStatus; showToast('error', 'Update failed', 'Unable to update status. Please try again.'); })
                                      .finally(() => { savingStatus = false; })"
                                  class="flex items-center gap-1.5">
                                @csrf
                                @method('PATCH')
                                <select name="enrollment_status"
                                        x-model="selectedStatus"
                                        class="w-full min-w-0 rounded-xl border border-white/10 bg-white/10 px-2.5 py-2 text-xs font-semibold text-white outline-none focus:border-blue-300/40 focus:ring-2 focus:ring-blue-400/20">
                                    <option class="text-slate-900" value="pending" @selected(($enrollment->enrollment_status ?? 'pending') === 'pending')>Pending</option>
                                    <option class="text-slate-900" value="enrolled" @selected(($enrollment->enrollment_status ?? 'pending') === 'enrolled')>Enrolled</option>
                                    <option class="text-slate-900" value="cancelled" @selected(($enrollment->enrollment_status ?? 'pending') === 'cancelled')>Cancelled</option>
                                </select>
                                <button x-show="selectedStatus !== rowStatus"
                                        x-cloak
                                        x-transition
                                        :disabled="savingStatus"
                                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#1552d4] text-white transition hover:bg-[#0f43b0]"
                                        title="Update status"
                                        type="submit">
                                    <i x-show="!savingStatus" data-lucide="check" class="h-4 w-4"></i>
                                    <i x-show="savingStatus" data-lucide="loader-2" class="h-4 w-4 animate-spin" x-cloak></i>
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="$dispatch('edit-enrollment', { url: @js(route('enrollments.update', $enrollment)), enrollment: @js($editPayload) })"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-emerald-300/20 bg-emerald-500/10 text-emerald-100 transition hover:bg-emerald-500/20"
                                        title="Edit student data">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </button>
                                <a href="{{ route('enrollments.form.show', $enrollment) }}"
                                   target="_blank"
                                   rel="noopener"
                                   class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-300/20 bg-blue-500/10 text-blue-100 transition hover:bg-blue-500/20"
                                   title="View enrollment form">
                                    <i data-lucide="file-text" class="h-4 w-4"></i>
                                </a>
                            </div>
                        </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $compact ? 5 : 8 }}" class="px-5 py-12 text-center text-slate-300">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                        <p class="text-sm">No enrollments yet.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
