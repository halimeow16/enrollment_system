<header class="sticky top-0 z-30 border-b border-white/10 bg-[#071224]/80 glass"
        x-data="{ mobileOpen: false, activeTab: 'overview' }"
        @dashboard-tab-changed.window="activeTab = $event.detail.tab">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex h-20 items-center justify-between">

            <div class="flex items-center gap-3">
                <div class="rounded-2xl px-4 py-2 shadow-lg">
                    <img src="{{ asset('images/logo.png') }}" alt="COMTEQ" class="h-20 w-auto">
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-2">
                @php
                    $navItems = [
                        ['tab' => 'overview', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
                        ['tab' => 'enrollments', 'label' => 'Enrollments', 'icon' => 'users'],
                    ];

                    if (auth()->user()?->user_type === 'admin') {
                        $navItems[] = ['tab' => 'configuration', 'label' => 'Configuration', 'icon' => 'sliders-horizontal'];
                    }
                @endphp

                @foreach($navItems as $item)
                    <button type="button"
                            @click="activeTab = '{{ $item['tab'] }}'; $dispatch('dashboard-tab-selected', { tab: '{{ $item['tab'] }}' })"
                            :class="activeTab === '{{ $item['tab'] }}'
                                ? 'bg-blue-500/15 text-blue-200 border-blue-400/20'
                                : 'text-slate-300 border-transparent hover:bg-white/5 hover:text-white'"
                            class="inline-flex items-center gap-2 rounded-2xl border px-4 py-2.5 text-sm font-semibold transition">
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex rounded-full border border-blue-400/20 bg-blue-400/10 px-4 py-2 text-xs font-bold text-blue-200">
                    A.Y. 2026-2027
                </span>

                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 text-white transition hover:bg-white/10">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#1552d4] text-sm font-bold">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>

                        <div class="hidden text-left md:block">
                            <p class="text-xs font-bold leading-tight">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-[10px] capitalize leading-tight text-slate-400">
                                {{ str_replace('_', ' ', auth()->user()->user_type ?? 'registrar') }}
                            </p>
                        </div>

                        <i data-lucide="chevron-down" class="hidden h-4 w-4 text-slate-400 md:block"></i>
                    </button>

                    <div x-show="open"
                         x-transition
                         x-cloak
                         class="absolute right-0 mt-3 w-48 overflow-hidden rounded-2xl border border-white/10 bg-[#0b172f] shadow-2xl">
                        <a href="#" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-300 hover:bg-white/5">
                            <i data-lucide="user" class="h-4 w-4"></i> Profile
                        </a>
                        <a href="#" class="flex items-center gap-2 px-4 py-3 text-sm text-slate-300 hover:bg-white/5">
                            <i data-lucide="settings" class="h-4 w-4"></i> Settings
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="flex w-full items-center gap-2 px-4 py-3 text-sm font-semibold text-red-300 hover:bg-red-500/10">
                                <i data-lucide="log-out" class="h-4 w-4"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>

                <button @click="mobileOpen = !mobileOpen"
                        class="md:hidden rounded-xl border border-white/10 bg-white/5 p-2 text-white">
                    <i x-show="!mobileOpen" data-lucide="menu" class="h-5 w-5"></i>
                    <i x-show="mobileOpen" data-lucide="x" class="h-5 w-5" x-cloak></i>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-transition x-cloak class="md:hidden border-t border-white/10 py-3 space-y-2">
            @foreach($navItems as $item)
                <button type="button"
                        @click="activeTab = '{{ $item['tab'] }}'; mobileOpen = false; $dispatch('dashboard-tab-selected', { tab: '{{ $item['tab'] }}' })"
                        :class="activeTab === '{{ $item['tab'] }}' ? 'bg-blue-500/15 text-blue-200' : 'text-slate-300 hover:bg-white/5'"
                        class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold">
                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</header>
