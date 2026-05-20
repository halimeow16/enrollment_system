<header class="sticky top-0 z-30 border-b border-white/10 bg-[#071224]/80 glass"
        x-data="navbarSettings({
            academicYear: @js($academicYear ?? '2026-2027'),
            currentUser: @js([
                'id' => auth()->id(),
                'name' => auth()->user()->name ?? '',
                'email' => auth()->user()->email ?? '',
                'user_type' => auth()->user()->user_type ?? 'registrar',
            ]),
            users: @js(($accountUsers ?? collect())->values()),
            ownUpdateUrl: @js(route('account.update')),
            accountsStoreUrl: @js(route('accounts.store')),
            accountsBaseUrl: @js(url('/accounts')),
            isAdmin: @js(auth()->user()?->user_type === 'admin'),
        })"
        @dashboard-tab-changed.window="activeTab = $event.detail.tab"
        @academic-year-updated.window="academicYear = $event.detail.academicYear"
        @open-account-settings.window="settingsOpen = true; $nextTick(() => window.lucide?.createIcons())">
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
                        ['tab' => 'id-generation', 'label' => 'Generate IDs', 'icon' => 'badge'],
                    ];

                    if (auth()->user()?->user_type === 'admin') {
                        $navItems[] = ['tab' => 'configuration', 'label' => 'Configurations', 'icon' => 'sliders-horizontal'];
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
                    A.Y. <span class="ml-1" x-text="academicYear"></span>
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
                        <button type="button"
                                @click="open = false; window.dispatchEvent(new CustomEvent('open-account-settings'))"
                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5">
                            <i data-lucide="settings" class="h-4 w-4"></i> Settings
                        </button>
                        <button type="button"
                                class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5">
                            <i data-lucide="clipboard-list" class="h-4 w-4"></i> Logs
                        </button>
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

    <template x-teleport="body">
    <div x-show="settingsOpen"
         x-transition.opacity
         x-cloak
         class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
         @click.self="settingsOpen = false"
         @keydown.escape.window="settingsOpen = false">
        <div class="flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#101a2d] shadow-2xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <div>
                    <h2 class="text-lg font-extrabold text-white">Account Settings</h2>
                    <p class="mt-1 text-xs text-slate-400">Manage your profile, password, and system accounts.</p>
                </div>
                <button type="button"
                        @click="settingsOpen = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="grid min-h-0 flex-1 grid-cols-12 overflow-hidden">
                <aside class="col-span-12 border-b border-white/10 p-4 md:col-span-3 md:border-b-0 md:border-r">
                    <div class="space-y-2">
                        <button type="button"
                                @click="settingsTab = 'profile'"
                                :class="settingsTab === 'profile' ? 'bg-blue-500/20 text-blue-100 border-blue-300/20' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                class="flex w-full items-center gap-2 rounded-2xl border px-4 py-3 text-left text-sm font-bold transition">
                            <i data-lucide="user-round" class="h-4 w-4"></i>
                            My Account
                        </button>
                        <button type="button"
                                x-show="isAdmin"
                                @click="settingsTab = 'accounts'"
                                :class="settingsTab === 'accounts' ? 'bg-blue-500/20 text-blue-100 border-blue-300/20' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                class="flex w-full items-center gap-2 rounded-2xl border px-4 py-3 text-left text-sm font-bold transition">
                            <i data-lucide="users-round" class="h-4 w-4"></i>
                            Accounts
                        </button>
                    </div>
                </aside>

                <div class="col-span-12 max-h-[72vh] overflow-y-auto p-5 md:col-span-9">
                    <section x-show="settingsTab === 'profile'" x-cloak>
                        <form @submit.prevent="saveOwnAccount($event.target)"
                              class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            @csrf
                            @method('PUT')
                            <div class="mb-5">
                                <h3 class="font-extrabold text-white">My Account</h3>
                                <p class="mt-1 text-xs text-slate-400">Update your account details. Enter current password only when changing password.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="text-xs font-semibold text-slate-300">
                                    Name
                                    <input name="name" x-model="currentUser.name" required class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                                <label class="text-xs font-semibold text-slate-300">
                                    Email
                                    <input type="email" name="email" x-model="currentUser.email" required class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                                <label class="text-xs font-semibold text-slate-300">
                                    Current Password
                                    <input type="password" name="current_password" autocomplete="current-password" class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                                <label class="text-xs font-semibold text-slate-300">
                                    New Password
                                    <input type="password" name="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                                <label class="text-xs font-semibold text-slate-300 md:col-span-2">
                                    Confirm New Password
                                    <input type="password" name="password_confirmation" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                            </div>

                            <button type="submit"
                                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#1552d4] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f43b0]">
                                <i data-lucide="save" class="h-4 w-4"></i>
                                Save Changes
                            </button>
                        </form>
                    </section>

                    <section x-show="settingsTab === 'accounts' && isAdmin" x-cloak class="space-y-5">
                        <form @submit.prevent="createAccount($event.target)"
                              class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            @csrf
                            <div class="mb-5">
                                <h3 class="font-extrabold text-white">Create Account</h3>
                                <p class="mt-1 text-xs text-slate-400">Create registrar/staff and department head accounts.</p>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-3">
                                <input name="name" required placeholder="Name" class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-300/40">
                                <input type="email" name="email" required placeholder="Email" class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-300/40">
                                <select name="user_type" required class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm font-semibold text-white outline-none focus:border-blue-300/40">
                                    <option class="text-slate-900" value="registrar">Staff / Registrar</option>
                                    <option class="text-slate-900" value="department_head">Department Head</option>
                                </select>
                                <input type="password" name="password" required placeholder="Password" class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-300/40">
                                <input type="password" name="password_confirmation" required placeholder="Confirm password" class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-300/40">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1552d4] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#0f43b0]">
                                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                                    Create
                                </button>
                            </div>
                        </form>

                        <div class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            <div class="mb-4">
                                <h3 class="font-extrabold text-white">Manage Accounts</h3>
                                <p class="mt-1 text-xs text-slate-400">Edit account details, reset passwords, or remove accounts.</p>
                            </div>

                            <div class="max-h-[260px] space-y-3 overflow-y-auto pr-1">
                                <template x-for="user in users" :key="user.id">
                                    <form @submit.prevent="updateManagedAccount($event.target, user.id, $data)"
                                          x-data="{
                                              passwordOpen: false,
                                              password: '',
                                              passwordConfirmation: '',
                                              saved: { name: user.name, email: user.email, user_type: user.user_type },
                                              isDirty() {
                                                  return user.name !== this.saved.name
                                                      || user.email !== this.saved.email
                                                      || user.user_type !== this.saved.user_type
                                                      || this.password.length > 0
                                                      || this.passwordConfirmation.length > 0;
                                              },
                                              closePassword() {
                                                  this.passwordOpen = false;
                                                  this.password = '';
                                                  this.passwordConfirmation = '';
                                              },
                                          }"
                                          class="grid gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 lg:grid-cols-[minmax(150px,1fr)_minmax(190px,1fr)_150px_auto]">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" x-model="user.name" required class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white outline-none focus:border-blue-300/40">
                                        <input type="email" name="email" x-model="user.email" required class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white outline-none focus:border-blue-300/40">
                                        <select name="user_type" x-model="user.user_type" required class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white outline-none focus:border-blue-300/40">
                                            <option class="text-slate-900" value="admin">Admin</option>
                                            <option class="text-slate-900" value="registrar">Staff</option>
                                            <option class="text-slate-900" value="department_head">Dept. Head</option>
                                        </select>
                                        <div class="flex justify-end gap-2">
                                            <button type="button" title="Reset password" @click="passwordOpen ? closePassword() : passwordOpen = true" :class="passwordOpen ? 'border-violet-300/30 bg-violet-500/20 text-violet-100' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border transition">
                                                <i data-lucide="key-round" class="h-4 w-4"></i>
                                            </button>
                                            <button type="submit" title="Save account" :disabled="!isDirty()" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-300/20 bg-blue-500/15 text-blue-100 hover:bg-blue-500/25 disabled:cursor-not-allowed disabled:border-white/10 disabled:bg-white/5 disabled:text-slate-500">
                                                <i data-lucide="save" class="h-4 w-4"></i>
                                            </button>
                                            <button type="button" title="Remove account" @click="removeManagedAccount(user.id)" :disabled="user.id === currentUser.id" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-red-300/20 bg-red-500/10 text-red-200 hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-40">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        <div x-show="passwordOpen"
                                             x-transition
                                             class="grid gap-2 rounded-2xl border border-violet-300/15 bg-violet-500/10 p-3 lg:col-span-4 lg:grid-cols-2">
                                            <input type="password" name="password" x-model="password" placeholder="New password" autocomplete="new-password" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white placeholder:text-slate-500 outline-none focus:border-violet-300/40">
                                            <input type="password" name="password_confirmation" x-model="passwordConfirmation" placeholder="Confirm new password" autocomplete="new-password" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white placeholder:text-slate-500 outline-none focus:border-violet-300/40">
                                        </div>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    </template>
</header>

@push('scripts')
<script>
    window.navbarSettings = function (config) {
        return {
            mobileOpen: false,
            activeTab: 'overview',
            academicYear: config.academicYear,
            settingsOpen: false,
            settingsTab: 'profile',
            currentUser: config.currentUser,
            users: config.users || [],
            ownUpdateUrl: config.ownUpdateUrl,
            accountsStoreUrl: config.accountsStoreUrl,
            accountsBaseUrl: config.accountsBaseUrl,
            isAdmin: config.isAdmin,
            async requestJson(url, form, method = 'POST') {
                const formData = new FormData(form);
                const response = await fetch(url, {
                    method,
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'Unable to save account changes.');
                }

                return data;
            },
            toast(type, title, message) {
                window.dispatchEvent(new CustomEvent('dashboard-toast', {
                    detail: { type, title, message },
                }));
            },
            async saveOwnAccount(form) {
                try {
                    const data = await this.requestJson(this.ownUpdateUrl, form, 'POST');
                    this.currentUser = data.user;
                    this.upsertUser(data.user);
                    form.querySelectorAll('input[type="password"]').forEach((input) => input.value = '');
                    this.toast('success', 'Account updated', 'Your account settings were saved.');
                } catch (error) {
                    this.toast('error', 'Save failed', error.message);
                }
            },
            async createAccount(form) {
                try {
                    const data = await this.requestJson(this.accountsStoreUrl, form, 'POST');
                    this.upsertUser(data.user);
                    form.reset();
                    this.toast('success', 'Account created', `${data.user.name} can now sign in.`);
                } catch (error) {
                    this.toast('error', 'Create failed', error.message);
                }
            },
            async updateManagedAccount(form, id, rowState = null) {
                try {
                    const data = await this.requestJson(`${this.accountsBaseUrl}/${id}`, form, 'POST');
                    this.upsertUser(data.user);
                    if (data.user.id === this.currentUser.id) {
                        this.currentUser = data.user;
                    }
                    form.querySelectorAll('input[type="password"]').forEach((input) => input.value = '');
                    if (rowState) {
                        rowState.password = '';
                        rowState.passwordConfirmation = '';
                        rowState.passwordOpen = false;
                        rowState.saved = {
                            name: data.user.name,
                            email: data.user.email,
                            user_type: data.user.user_type,
                        };
                    }
                    this.toast('success', 'Account updated', `${data.user.name} was saved.`);
                } catch (error) {
                    this.toast('error', 'Save failed', error.message);
                }
            },
            async removeManagedAccount(id) {
                if (id === this.currentUser.id || !confirm('Remove this account?')) return;

                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

                try {
                    const response = await fetch(`${this.accountsBaseUrl}/${id}`, {
                        method: 'POST',
                        body: new URLSearchParams({ _method: 'DELETE', _token: token }),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Unable to remove account.');
                    }

                    this.users = this.users.filter((user) => user.id !== id);
                    this.toast('success', 'Account removed', 'The account was removed.');
                } catch (error) {
                    this.toast('error', 'Remove failed', error.message);
                }
            },
            upsertUser(user) {
                this.users = [
                    user,
                    ...this.users.filter((item) => item.id !== user.id),
                ].sort((a, b) => a.name.localeCompare(b.name));
            },
        };
    };
</script>
@endpush
