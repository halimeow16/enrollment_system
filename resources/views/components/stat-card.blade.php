@props([
    'label',
    'value',
    'icon',
    'color' => 'blue',
    'trend' => null,
    'trendLabel' => null,
])

@php
    $colorMap = [
        'blue'    => ['bg' => 'bg-blue-50',   'icon' => 'text-blue-500',   'badge' => 'bg-blue-100 text-blue-600'],
        'amber'   => ['bg' => 'bg-amber-50',  'icon' => 'text-amber-500',  'badge' => 'bg-amber-100 text-amber-600'],
        'emerald' => ['bg' => 'bg-emerald-50','icon' => 'text-emerald-500','badge' => 'bg-emerald-100 text-emerald-600'],
        'violet'  => ['bg' => 'bg-violet-50', 'icon' => 'text-violet-500', 'badge' => 'bg-violet-100 text-violet-600'],
    ];
    $c = $colorMap[$color] ?? $colorMap['blue'];
@endphp

<div class="bg-white rounded-3xl border border-slate-100 shadow-sm px-6 py-5 flex items-center gap-5">

    <div class="w-12 h-12 rounded-2xl {{ $c['bg'] }} flex items-center justify-center shrink-0">
        <i data-lucide="{{ $icon }}" class="w-5 h-5 {{ $c['icon'] }}"></i>
    </div>

    <div>
        <p class="text-xs text-slate-400 font-medium">{{ $label }}</p>
        <p class="text-2xl font-bold text-slate-800 mt-0.5">{{ number_format($value) }}</p>

        @if($trend !== null)
            <p class="text-xs mt-1">
                <span class="{{ $c['badge'] }} px-1.5 py-0.5 rounded-full font-semibold">+{{ $trend }}</span>
                <span class="text-slate-400 ml-1">{{ $trendLabel }}</span>
            </p>
        @endif
    </div>

</div>