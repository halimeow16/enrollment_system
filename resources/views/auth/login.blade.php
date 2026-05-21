<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>COMTEQ | Login</title>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .glass {
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }
    </style>
</head>

<body class="min-h-screen overflow-hidden bg-[#071224] relative">

    <!-- Background Glow -->
    <div class="absolute top-[-120px] left-[-120px] h-[320px] w-[320px] rounded-full bg-blue-600/20 blur-3xl"></div>
    <div class="absolute bottom-[-140px] right-[-140px] h-[340px] w-[340px] rounded-full bg-red-500/20 blur-3xl"></div>

    <!-- Grid Overlay -->
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image: linear-gradient(to right, white 1px, transparent 1px),
                                 linear-gradient(to bottom, white 1px, transparent 1px);
                background-size: 40px 40px;">
    </div>

    <main class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">

        <div class="grid w-full max-w-6xl overflow-hidden rounded-[32px] border border-white/10 bg-white/5 glass shadow-2xl lg:grid-cols-2">

            <!-- Left Side -->
            <section class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-[#0f1f3d] via-[#0b172f] to-[#071224] p-14 text-white relative overflow-hidden">

                <!-- Decorative -->
                <div class="absolute top-0 right-0 h-64 w-64 rounded-full bg-blue-500/10 blur-3xl"></div>
                <div class="absolute bottom-0 left-0 h-72 w-72 rounded-full bg-red-500/10 blur-3xl"></div>

                <div class="relative z-10">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="COMTEQ"
                         class="h-42 w-auto">
                </div>

                <div class="relative z-10 max-w-lg">
                    <p class="mb-4 inline-flex rounded-full border border-blue-400/20 bg-blue-400/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-200">
                        Enrollment Management System
                    </p>

                    <h1 class="text-5xl font-extrabold leading-tight">
                        Process and Approve
                        <span class="text-blue-400">Enrollments</span>
                    </h1>

                    <p class="mt-6 text-base leading-8 text-slate-300">
                        Authorized personnel portal for the COMTEQ Enrollment Management System.
                        <br>
                        <br>
                    </p>
                </div>

                <!-- Stats -->
                <div class="relative z-10 grid grid-cols-3 gap-4">

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h3 class="text-2xl font-bold">Local</h3>
                        <p class="mt-1 text-xs text-slate-300">
                            Campus Network
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h3 class="text-2xl font-bold">Instant</h3>
                        <p class="mt-1 text-xs text-slate-300">
                            Live Updates
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h3 class="text-2xl font-bold">Fast</h3>
                        <p class="mt-1 text-xs text-slate-300">
                            Quick Review
                        </p>
                    </div>

                </div>

            </section>

            <!-- Right Side -->
            <section class="flex items-center justify-center bg-white p-8 sm:p-12 lg:p-16">

                <div class="w-full max-w-md">

                    <!-- Mobile Logo -->
                    <div class="mb-8 flex justify-center lg:hidden">
                        <img src="{{ asset('images/logo.png') }}"
                             alt="COMTEQ"
                             class="h-20 w-auto">
                    </div>

                    <!-- Header -->
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#1552d4]">
                            Welcome Back
                        </p>

                        <h2 class="mt-3 text-4xl font-extrabold text-slate-900">
                            Sign In
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Access the COMTEQ Enrollment Management Dashboard.
                        </p>
                    </div>

                    <!-- Form -->
                    <form action="{{ route('login.store') }}"
                          method="POST"
                          class="mt-10 space-y-6">

                        @csrf

                        <!-- Email -->
                        <div>
                            <label for="email"
                                   class="mb-2 block text-sm font-semibold text-slate-700">
                                Email Address
                            </label>

                            <input id="email"
                                   name="email"
                                   type="email"
                                   value="{{ old('email') }}"
                                   autocomplete="email"
                                   required
                                   autofocus
                                   placeholder="Enter your email"
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm outline-none transition duration-200 focus:border-[#1552d4] focus:bg-white focus:ring-4 focus:ring-blue-100">

                            @error('email')
                                <p class="mt-2 text-xs font-semibold text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password"
                                   class="mb-2 block text-sm font-semibold text-slate-700">
                                Password
                            </label>

                            <input id="password"
                                   name="password"
                                   type="password"
                                   autocomplete="current-password"
                                   required
                                   placeholder="Enter your password"
                                   class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm outline-none transition duration-200 focus:border-[#1552d4] focus:bg-white focus:ring-4 focus:ring-blue-100">
                        </div>

                        <!-- Button -->
                        <button type="submit"
                                class="w-full rounded-2xl bg-gradient-to-r from-[#071224] to-[#0f43b0] px-5 py-4 text-sm font-bold tracking-wide text-white shadow-xl transition duration-300 hover:scale-[1.01] hover:shadow-blue-200">
                            Sign In
                        </button>

                    </form>

                </div>

            </section>

        </div>

    </main>

    @vite(['resources/js/app.js'])
</body>
</html>
