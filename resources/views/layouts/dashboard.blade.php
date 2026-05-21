<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'COMTEQ | Dashboard')</title>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }

        .glass {
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 999px; }
    </style>
</head>

<body class="min-h-screen bg-[#071224] text-slate-900 antialiased relative overflow-x-hidden">

    <div class="fixed top-[-120px] left-[-120px] h-[320px] w-[320px] rounded-full bg-blue-600/20 blur-3xl"></div>
    <div class="fixed bottom-[-140px] right-[-140px] h-[340px] w-[340px] rounded-full bg-red-500/20 blur-3xl"></div>

    <div class="fixed inset-0 opacity-[0.04]"
         style="background-image: linear-gradient(to right, white 1px, transparent 1px),
                                 linear-gradient(to bottom, white 1px, transparent 1px);
                background-size: 40px 40px;">
    </div>

    <div class="relative z-10">
        @include('dashboard.partials.navbar')

        <main class="max-w-7xl mx-auto px-6 py-6">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
