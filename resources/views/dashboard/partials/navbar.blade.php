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
            logs: @js(($activityLogs ?? collect())->values()),
            logsUrl: @js(route('activity-logs.index')),
            ownUpdateUrl: @js(route('account.update')),
            accountsStoreUrl: @js(route('accounts.store')),
            accountsBaseUrl: @js(url('/accounts')),
            databaseExportUrl: @js(route('account.database.export')),
            databaseImportUrl: @js(route('account.database.import')),
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
                        ['tab' => 'scheduling', 'label' => 'Scheduling', 'icon' => 'calendar-days'],
                    ];

                    if (in_array(auth()->user()?->user_type, ['admin', 'registrar', 'department_head'], true)) {
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
                        @if(auth()->user()?->user_type === 'admin')
                            <button type="button"
                                    @click="open = false; openLogs()"
                                    class="flex w-full items-center gap-2 px-4 py-3 text-left text-sm text-slate-300 hover:bg-white/5">
                                <i data-lucide="clipboard-list" class="h-4 w-4"></i> Logs
                            </button>
                        @endif
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
                        <button type="button"
                                x-show="isAdmin"
                                @click="settingsTab = 'database'"
                                :class="settingsTab === 'database' ? 'bg-blue-500/20 text-blue-100 border-blue-300/20' : 'border-white/10 bg-white/5 text-slate-300 hover:bg-white/10'"
                                class="flex w-full items-center gap-2 rounded-2xl border px-4 py-3 text-left text-sm font-bold transition">
                            <i data-lucide="database" class="h-4 w-4"></i>
                            Database
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
                                            <button type="button" title="Remove account" @click="confirmingAccountRemoval = user.id" :disabled="user.id === currentUser.id" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-red-300/20 bg-red-500/10 text-red-200 hover:bg-red-500/20 disabled:cursor-not-allowed disabled:opacity-40">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        <div x-show="passwordOpen"
                                             x-transition
                                             class="grid gap-2 rounded-2xl border border-violet-300/15 bg-violet-500/10 p-3 lg:col-span-4 lg:grid-cols-2">
                                            <input type="password" name="password" x-model="password" placeholder="New password" autocomplete="new-password" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white placeholder:text-slate-500 outline-none focus:border-violet-300/40">
                                            <input type="password" name="password_confirmation" x-model="passwordConfirmation" placeholder="Confirm new password" autocomplete="new-password" class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs text-white placeholder:text-slate-500 outline-none focus:border-violet-300/40">
                                        </div>
                                        <div x-show="confirmingAccountRemoval === user.id"
                                             x-transition
                                             class="rounded-2xl border border-red-300/20 bg-red-500/10 p-3 lg:col-span-4">
                                            <p class="text-xs font-semibold text-red-100">Remove this account?</p>
                                            <p class="mt-1 text-xs text-red-100/70" x-text="user.name"></p>
                                            <div class="mt-3 flex justify-end gap-2">
                                                <button type="button"
                                                        @click="confirmingAccountRemoval = null"
                                                        class="rounded-lg border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 transition hover:bg-white/10">
                                                    Cancel
                                                </button>
                                                <button type="button"
                                                        @click="removeManagedAccount(user.id)"
                                                        class="rounded-lg bg-red-500 px-3 py-2 text-xs font-bold text-white transition hover:bg-red-600">
                                                    Confirm
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section x-show="settingsTab === 'database' && isAdmin" x-cloak class="space-y-5">
                        <form @submit.prevent="exportDatabase($event.target)"
                              class="rounded-3xl border border-white/10 bg-white/5 p-5">
                            @csrf
                            <div class="mb-5">
                                <h3 class="font-extrabold text-white">Database Export</h3>
                                <p class="mt-1 text-xs text-slate-400">Download an encrypted backup file for this system.</p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                <label class="text-xs font-semibold text-slate-300">
                                    Confirm Password
                                    <input type="password"
                                           name="password"
                                           required
                                           autocomplete="current-password"
                                           class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-blue-300/40">
                                </label>
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1552d4] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#0f43b0]">
                                    <i data-lucide="download" class="h-4 w-4"></i>
                                    Export
                                </button>
                            </div>
                        </form>

                        <form @submit.prevent="restoreDatabase($event.target)"
                              class="rounded-3xl border border-red-300/20 bg-red-500/10 p-5">
                            @csrf
                            <div class="mb-5">
                                <h3 class="font-extrabold text-red-100">Database Upload</h3>
                                <p class="mt-1 text-xs text-red-100/75">Uploading a backup will replace all current application data with the uploaded data.</p>
                            </div>

                            <div class="grid gap-4">
                                <label class="text-xs font-semibold text-slate-300">
                                    Encrypted Backup File
                                    <input type="file"
                                           name="backup_file"
                                           required
                                           accept=".esbackup,application/octet-stream"
                                           class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white file:mr-4 file:rounded-lg file:border-0 file:bg-white file:px-3 file:py-2 file:text-xs file:font-bold file:text-slate-900">
                                </label>
                                <label class="text-xs font-semibold text-slate-300">
                                    Confirm Password
                                    <input type="password"
                                           name="password"
                                           required
                                           autocomplete="current-password"
                                           class="mt-1 w-full rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white outline-none focus:border-red-300/40">
                                </label>
                                <label class="flex items-start gap-3 rounded-2xl border border-red-300/20 bg-red-500/10 p-4 text-xs font-semibold text-red-100">
                                    <input type="checkbox"
                                           name="replace_confirmation"
                                           value="1"
                                           required
                                           class="mt-0.5 h-4 w-4 rounded border-red-200 text-red-500">
                                    <span>I understand that all current data will be replaced with the uploaded backup data.</span>
                                </label>
                                <button type="submit"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-500 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-600">
                                    <i data-lucide="upload" class="h-4 w-4"></i>
                                    Upload and Replace
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
    </template>

    <template x-teleport="body">
    <div x-show="logsOpen"
         x-transition.opacity
         x-cloak
         class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 px-4 py-8 backdrop-blur-sm"
         @click.self="logsOpen = false"
         @keydown.escape.window="logsOpen = false">
        <div class="flex max-h-full w-full max-w-5xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#101a2d] shadow-2xl shadow-black/40">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                <div>
                    <h2 class="text-lg font-extrabold text-white">Activity Logs</h2>
                    <p class="mt-1 text-xs text-slate-400">Track sign-ins, account changes, enrollments, templates, ID actions, and academic setup updates.</p>
                </div>
                <button type="button"
                        @click="logsOpen = false"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-slate-200 transition hover:bg-white/10">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="border-b border-white/10 p-4">
                <div class="grid gap-3 md:grid-cols-[1fr_180px_180px]">
                    <div class="relative">
                        <i data-lucide="search" class="absolute left-3 top-3 h-4 w-4 text-slate-400"></i>
                        <input type="search"
                               x-model="logFilters.search"
                               placeholder="Search activity, user, model, IP..."
                               class="w-full rounded-2xl border border-white/10 bg-white/10 py-2.5 pl-9 pr-3 text-sm text-white placeholder:text-slate-500 outline-none focus:border-blue-300/40">
                    </div>
                    <select x-model="logFilters.type"
                            class="rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 text-sm font-semibold text-white outline-none focus:border-blue-300/40">
                        <option class="text-slate-900" value="all">All Areas</option>
                        <option class="text-slate-900" value="auth">Auth</option>
                        <option class="text-slate-900" value="account">Accounts</option>
                        <option class="text-slate-900" value="enrollment">Enrollments</option>
                        <option class="text-slate-900" value="academic">Academic Setup</option>
                        <option class="text-slate-900" value="template">Templates</option>
                        <option class="text-slate-900" value="id">ID Generation</option>
                    </select>
                    <select x-model="logFilters.date"
                            class="rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 text-sm font-semibold text-white outline-none focus:border-blue-300/40">
                        <option class="text-slate-900" value="all">All Dates</option>
                        <option class="text-slate-900" value="today">Today</option>
                        <option class="text-slate-900" value="week">Last 7 Days</option>
                    </select>
                </div>
            </div>

            <div class="max-h-[68vh] overflow-y-auto p-4">
                <div x-show="logsLoading" x-cloak class="mb-3 rounded-2xl border border-blue-300/20 bg-blue-500/10 px-4 py-3 text-xs font-bold text-blue-100">
                    Loading latest activity...
                </div>

                <template x-if="filteredLogs().length === 0">
                    <div class="rounded-3xl border border-white/10 bg-white/5 px-5 py-10 text-center">
                        <i data-lucide="clipboard-list" class="mx-auto h-8 w-8 text-slate-500"></i>
                        <p class="mt-3 text-sm font-bold text-white">No logs found</p>
                        <p class="mt-1 text-xs text-slate-400">Try changing the filters or search text.</p>
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="log in filteredLogs()" :key="log.id">
                        <article class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span :class="logBadgeClass(log)"
                                              class="inline-flex items-center rounded-full border px-2.5 py-1 text-[11px] font-bold capitalize">
                                            <span x-text="logArea(log)"></span>
                                        </span>
                                        <h3 class="text-sm font-extrabold text-white" x-text="log.label"></h3>
                                    </div>
                                    <p class="mt-2 text-xs text-slate-400">
                                        <span class="font-semibold text-slate-200" x-text="log.user"></span>
                                        <span> updated </span>
                                        <span x-text="log.model_type"></span>
                                        <template x-if="log.model_id">
                                            <span>#<span x-text="log.model_id"></span></span>
                                        </template>
                                    </p>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        <span x-text="log.created_at"></span>
                                        <span class="mx-1">/</span>
                                        <span x-text="log.ip_address || 'No IP'"></span>
                                    </p>
                                </div>
                                <button type="button"
                                        @click="log.open = !log.open; $nextTick(() => window.lucide?.createIcons())"
                                        class="inline-flex items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-bold text-slate-200 hover:bg-white/10">
                                    <i data-lucide="list-tree" class="h-4 w-4"></i>
                                    Details
                                </button>
                            </div>

                            <div x-show="log.open" x-transition class="mt-4 grid gap-3 md:grid-cols-2">
                                <div class="rounded-2xl border border-white/10 bg-[#071224]/60 p-3">
                                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">Before</p>
                                    <pre class="max-h-40 overflow-auto whitespace-pre-wrap text-xs text-slate-300" x-text="formatLogValues(log.old_values)"></pre>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-[#071224]/60 p-3">
                                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-400">After</p>
                                    <pre class="max-h-40 overflow-auto whitespace-pre-wrap text-xs text-slate-300" x-text="formatLogValues(log.new_values)"></pre>
                                </div>
                            </div>
                        </article>
                    </template>
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
            logsOpen: false,
            logsLoading: false,
            settingsTab: 'profile',
            confirmingAccountRemoval: null,
            currentUser: config.currentUser,
            users: config.users || [],
            logs: (config.logs || []).map((log) => ({ ...log, open: false })),
            logFilters: {
                search: '',
                type: 'all',
                date: 'all',
            },
            ownUpdateUrl: config.ownUpdateUrl,
            accountsStoreUrl: config.accountsStoreUrl,
            accountsBaseUrl: config.accountsBaseUrl,
            databaseExportUrl: config.databaseExportUrl,
            databaseImportUrl: config.databaseImportUrl,
            logsUrl: config.logsUrl,
            isAdmin: config.isAdmin,
            responseErrorMessage(data, fallback) {
                if (window.dashboardResponseErrorMessage) {
                    return window.dashboardResponseErrorMessage(data, fallback);
                }

                const messages = [];

                if (data?.message) {
                    messages.push(data.message);
                }

                if (data?.errors && typeof data.errors === 'object') {
                    Object.values(data.errors).forEach((fieldErrors) => {
                        const entries = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];
                        entries.filter(Boolean).forEach((entry) => messages.push(entry));
                    });
                }

                return [...new Set(messages)].join(' ') || fallback;
            },
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
                    throw new Error(this.responseErrorMessage(data, 'Unable to save account changes. Please check the required fields and try again.'));
                }

                return data;
            },
            toast(type, title, message) {
                window.dispatchEvent(new CustomEvent('dashboard-toast', {
                    detail: { type, title, message },
                }));
            },
            async openLogs() {
                this.logsOpen = true;
                await this.loadLogs();
                this.$nextTick(() => window.lucide?.createIcons());
            },
            async loadLogs() {
                this.logsLoading = true;

                try {
                    const response = await fetch(this.logsUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json().catch(() => ({}));

                    if (! response.ok) {
                        throw new Error(this.responseErrorMessage(data, 'Unable to load logs. Please refresh the page or try again later.'));
                    }

                    this.logs = (data.logs || []).map((log) => ({ ...log, open: false }));
                } catch (error) {
                    this.toast('error', 'Logs unavailable', error.message);
                } finally {
                    this.logsLoading = false;
                }
            },
            logArea(log) {
                const action = log.action || '';
                if (action.includes('login') || action.includes('logout')) return 'auth';
                if (action.includes('account')) return 'account';
                if (action.includes('enrollment')) return 'enrollment';
                if (action.includes('template') || action.includes('font')) return 'template';
                if (action.includes('student_id') || action.includes('student_photo') || action.includes('student_signature') || action.includes('requirements')) return 'id';
                return 'academic';
            },
            logBadgeClass(log) {
                const area = this.logArea(log);
                return {
                    auth: 'border-violet-300/20 bg-violet-500/15 text-violet-100',
                    account: 'border-blue-300/20 bg-blue-500/15 text-blue-100',
                    enrollment: 'border-emerald-300/20 bg-emerald-500/15 text-emerald-100',
                    template: 'border-amber-300/20 bg-amber-500/15 text-amber-100',
                    id: 'border-cyan-300/20 bg-cyan-500/15 text-cyan-100',
                    academic: 'border-slate-300/20 bg-slate-500/15 text-slate-100',
                }[area];
            },
            filteredLogs() {
                const search = this.logFilters.search.trim().toLowerCase();
                const now = new Date();

                return this.logs.filter((log) => {
                    if (this.logFilters.type !== 'all' && this.logArea(log) !== this.logFilters.type) {
                        return false;
                    }

                    if (this.logFilters.date !== 'all') {
                        const created = log.created_date ? new Date(`${log.created_date}T00:00:00`) : null;
                        if (! created) return false;

                        if (this.logFilters.date === 'today' && created.toDateString() !== now.toDateString()) {
                            return false;
                        }

                        if (this.logFilters.date === 'week') {
                            const weekAgo = new Date(now);
                            weekAgo.setDate(now.getDate() - 7);
                            if (created < new Date(weekAgo.toDateString())) return false;
                        }
                    }

                    if (! search) return true;

                    const haystack = [
                        log.label,
                        log.action,
                        log.model_type,
                        log.model_id,
                        log.user,
                        log.user_role,
                        log.ip_address,
                        JSON.stringify(log.old_values || {}),
                        JSON.stringify(log.new_values || {}),
                    ].join(' ').toLowerCase();

                    return haystack.includes(search);
                });
            },
            formatLogValues(values) {
                if (! values || Object.keys(values).length === 0) {
                    return 'No captured values';
                }

                return Object.entries(values)
                    .map(([key, value]) => `${key}: ${value}`)
                    .join('\n');
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
                if (id === this.currentUser.id) return;

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
                        throw new Error(this.responseErrorMessage(data, 'Unable to remove account. Make sure at least one admin account remains.'));
                    }

                    this.users = this.users.filter((user) => user.id !== id);
                    this.confirmingAccountRemoval = null;
                    this.toast('success', 'Account removed', 'The account was removed.');
                } catch (error) {
                    this.toast('error', 'Remove failed', error.message);
                }
            },
            async exportDatabase(form) {
                try {
                    const response = await fetch(this.databaseExportUrl, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/octet-stream,application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (! response.ok) {
                        const data = await response.json().catch(() => ({}));
                        throw new Error(this.responseErrorMessage(data, 'Unable to export database. Please confirm your password and try again.'));
                    }

                    const blob = await response.blob();
                    const disposition = response.headers.get('content-disposition') || '';
                    const fileName = disposition.match(/filename="?([^"]+)"?/i)?.[1] || 'enrollment-system.esbackup';
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    URL.revokeObjectURL(url);
                    form.reset();
                    this.toast('success', 'Database exported', 'Encrypted backup downloaded.');
                } catch (error) {
                    this.toast('error', 'Export failed', error.message);
                }
            },
            async restoreDatabase(form) {
                try {
                    const data = await this.requestJson(this.databaseImportUrl, form, 'POST');
                    form.reset();
                    this.toast('success', 'Database restored', data.message || 'The encrypted backup was restored.');
                    window.setTimeout(() => window.location.reload(), 1200);
                } catch (error) {
                    this.toast('error', 'Restore failed', error.message);
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
