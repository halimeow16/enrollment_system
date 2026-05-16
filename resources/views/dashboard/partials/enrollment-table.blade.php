@php
    $compact = $compact ?? false;
@endphp

<div class="{{ $compact ? 'max-h-[374px]' : 'h-[calc(100vh-270px)] min-h-[450px]' }} overflow-auto">
    <table class="w-full text-sm">
        <thead class="sticky top-0 z-10">
            <tr class="bg-white/5 text-slate-300 text-xs uppercase tracking-wide">
                <th class="px-5 py-3 text-left font-semibold">Student</th>
                <th class="px-5 py-3 text-left font-semibold">Program</th>
                <th class="px-5 py-3 text-left font-semibold">Year/Sem</th>
                <th class="px-5 py-3 text-left font-semibold">Status</th>
                <th class="px-5 py-3 text-left font-semibold">Date Filed</th>
                @unless($compact)
                    <th class="px-5 py-3 text-left font-semibold">Contact</th>
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
                @endphp
                <tr class="hover:bg-white/5 transition-colors duration-100"
                    @unless($compact) x-show="matchesEnrollment(@js($rowText), @js($enrollment->enrollment_status ?? 'pending'))" @endunless>
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-500/20 border border-blue-300/20 flex items-center justify-center text-blue-100 text-xs font-bold shrink-0">
                                {{ strtoupper(substr($enrollment->first_name ?? $enrollment->last_name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-white text-xs">{{ $name }}</p>
                                <p class="text-slate-400 text-xs font-mono">{{ $enrollment->student_number ?? 'No student no.' }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3.5">
                        <p class="text-blue-100 text-xs font-semibold">{{ $enrollment->course_code ?? 'Not set' }}</p>
                        @unless($compact)
                            <p class="text-slate-400 text-xs max-w-52 truncate">{{ $enrollment->course_name ?? 'Course name unavailable' }}</p>
                        @endunless
                    </td>
                    <td class="px-5 py-3.5 text-slate-300 text-xs">
                        {{ $enrollment->year_level ? $enrollment->year_level . 'yr' : 'Year not set' }}
                        <span class="text-slate-500 mx-1">/</span>
                        {{ $enrollment->semester ?? 'Sem not set' }}
                    </td>
                    <td class="px-5 py-3.5">
                        <x-enrollment-badge :status="$enrollment->enrollment_status ?? 'pending'" />
                    </td>
                    <td class="px-5 py-3.5 text-slate-400 text-xs">
                        {{ $enrollment->date_filed ? $enrollment->date_filed->format('M d, Y') : 'Not filed' }}
                    </td>
                    @unless($compact)
                        <td class="px-5 py-3.5 text-slate-400 text-xs">
                            <p>{{ $enrollment->cellphone ?? 'No phone' }}</p>
                            <p class="max-w-48 truncate">{{ $enrollment->email ?? 'No email' }}</p>
                        </td>
                    @endunless
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $compact ? 5 : 6 }}" class="px-5 py-12 text-center text-slate-300">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                        <p class="text-sm">No enrollments yet.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
