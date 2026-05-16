<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'COMTEQ | Dashboard')</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        [x-cloak] { display: none !important; }

        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    </style>
</head>
<body class="bg-[#f5f7fb] antialiased">

    {{-- Top Navigation --}}
    @include('dashboard.partials.navbar')

    {{-- Page Content --}}
    <main class="max-w-7xl mx-auto px-6 py-6">
        @yield('content')
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => window.lucide?.createIcons());
        document.addEventListener('alpine:initialized', () => window.lucide?.createIcons());
    </script>

    @stack('scripts')

</body>
</html>
