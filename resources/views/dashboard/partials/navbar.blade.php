<header class="bg-white border-b border-slate-100 sticky top-0 z-30" x-data="{ mobileOpen: false }">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex items-center justify-between h-16">

            {{-- Brand --}}
            <div class="flex items-center gap-3 shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="COMTEQ" class="w-48 object-contain">
            </div>

            {{-- Desktop Nav Links --}}
            <nav class="hidden md:flex items-center gap-1">
                @php
                    $navItems = [
                        ['route' => 'dashboard',         'label' => 'Dashboard'],
                        ['route' => 'enrollment.create', 'label' => 'Enrollments'],
                        ['href'  => '/courses',          'label' => 'Course Management'],
                        ['href'  => '/reports',          'label' => 'Reports'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $routeName = $item['route'] ?? null;
                        $href = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route'])
                            ? route($item['route'])
                            : ($item['href'] ?? '#');
                        $isActive = $routeName
                            ? request()->routeIs($routeName) || request()->routeIs($routeName . '.*')
                            : false;
                    @endphp
                    <a href="{{ $href }}"
                       class="relative flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors duration-150
                              {{ $isActive
                                  ? 'text-[#1a52f4] bg-blue-50'
                                  : 'text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
                        {{ $item['label'] }}
                        @if($isActive)
                            <span class="absolute bottom-0 left-3 right-3 h-0.5 bg-[#1a52f4] rounded-full"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            {{-- Right: Badge + User --}}
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-[#1a52f4]">
                    A.Y. 2026–2027
                </span>

                {{-- User Dropdown --}}
                <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                    <button @click="open = !open"
                            class="flex items-center gap-2.5 px-3 py-1.5 rounded-2xl hover:bg-slate-50 transition-colors duration-150">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#1a52f4] to-blue-700 flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-xs font-semibold text-slate-700 leading-tight">{{ auth()->user()->name ?? 'Admin' }}</p>
                            <p class="text-[10px] text-slate-400 capitalize leading-tight">{{ auth()->user()->user_type ?? 'registrar' }}</p>
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
                         class="absolute right-0 mt-2 w-44 bg-white rounded-2xl border border-slate-100 shadow-lg py-2 origin-top-right"
                         x-cloak>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400"></i> Profile
                        </a>
                        <a href="#" class="flex items-center gap-2.5 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50">
                            <i data-lucide="settings" class="w-4 h-4 text-slate-400"></i> Settings
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ url('/logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                           class="flex items-center gap-2.5 px-4 py-2 text-sm text-red-500 hover:bg-red-50">
                            <i data-lucide="log-out" class="w-4 h-4"></i> Logout
                        </a>
                        <form id="logout-form" action="{{ url('/logout') }}" method="POST" class="hidden">@csrf</form>
                    </div>
                </div>

                {{-- Mobile hamburger --}}
                <button @click="mobileOpen = !mobileOpen"
                        class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors">
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
                @php
                    $href = isset($item['route']) && \Illuminate\Support\Facades\Route::has($item['route'])
                        ? route($item['route'])
                        : ($item['href'] ?? '#');
                    $isActive = isset($item['route'])
                        ? request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*')
                        : false;
                @endphp
                <a href="{{ $href }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ $isActive ? 'text-[#1a52f4] bg-blue-50' : 'text-slate-600 hover:bg-slate-50' }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>