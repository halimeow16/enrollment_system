@props(['status'])

@php
    $map = [
        'pending'   => 'bg-amber-100 text-amber-700',
        'enrolled'  => 'bg-emerald-100 text-emerald-700',
        'dropped'   => 'bg-red-100 text-red-600',
        'cancelled' => 'bg-slate-100 text-slate-500',
    ];
    $classes = $map[$status] ?? 'bg-slate-100 text-slate-500';
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize {{ $classes }}">
    {{ $status }}
</span>