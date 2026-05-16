<header class="bg-white border-b border-slate-200 sticky top-0 z-30"
        x-data="{ mobileOpen: false, activeTab: 'overview' }"
        @dashboard-tab-changed.window="activeTab = $event.detail.tab">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-16">

            {{-- Brand --}}
            <div class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="COMTEQ" class="w-44 object-contain">
            </div>

            {{-- Desktop Nav Links --}}
            <nav class="hidden md:flex items-center gap-1">
                @php
                    $navItems = [
                        ['tab' => 'overview',    'label' => 'Dashboard',         'icon' => 'layout-dashboard'],
                        ['tab' => 'enrollments', 'label' => 'Enrollments',       'icon' => 'users'],
                    ];

                    if (auth()->user()?->user_type === 'admin') {
                        $navItems[] = ['tab' => 'configuration', 'label' => 'Configuration', 'icon' => 'sliders-horizontal'];
                    }
                @endphp

                @foreach($navItems as $item)
                    <button type="button"
                            @click="activeTab = '{{ $item['tab'] }}'; $dispatch('dashboard-tab-selected', { tab: '{{ $item['tab'] }}' })"
                            :class="activeTab === '{{ $item['tab'] }}'
                                ? 'text-[#1552d4] bg-blue-50 border-blue-100'
                                : 'text-slate-600 border-transparent hover:text-slate-900 hover:bg-slate-50'"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border text-sm font-medium transition-colors duration-150">
                        <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i>
                        {{ $item['label'] }}
                    </button>
                @endforeach
            </nav>

            {{-- Right: Badge + User --}}
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                    A.Y. 2026-2027
                </span>

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-lg hover:bg-slate-50 transition-colors duration-150">
                        <div class="w-8 h-8 rounded-lg bg-[#1552d4] flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-xs font-semibold text-slate-800 leading-tight">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-[10px] text-slate-500 capitalize leading-tight">{{ str_replace('_', ' ', auth()->user()->user_type ?? 'registrar') }}</p>
                        </div>
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-slate-400 hidden md:block"></i>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-44 bg-white rounded-lg border border-slate-200 shadow-lg py-2 origin-top-right"
                         x-cloak>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Profile
                        </a>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i data-lucide="settings" class="w-4 h-4 text-slate-400"></i> Settings
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen"
                        class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 transition-colors">
                    <i x-show="!mobileOpen" data-lucide="menu" class="w-5 h-5"></i>
                    <i x-show="mobileOpen" data-lucide="x" class="w-5 h-5" x-cloak></i>
                </button>
            </div>
        </div>

        {{-- Mobile Nav Menu --}}
        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-slate-100 py-3 space-y-1"
             x-cloak>
            @foreach($navItems as $item)
                <button type="button"
                        @click="activeTab = '{{ $item['tab'] }}'; mobileOpen = false; $dispatch('dashboard-tab-selected', { tab: '{{ $item['tab'] }}' })"
                        :class="activeTab === '{{ $item['tab'] }}' ? 'text-[#1552d4] bg-blue-50' : 'text-slate-600 hover:bg-slate-50'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors">
                    <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i>
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </div>
</header>
