<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        .form-input {
            transition: all 0.2s;
        }
        .form-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e40af;
        }
    </style>
</head>
<body class="bg-slate-50">

    @yield('content')

    @stack('scripts')
    @vite(['resources/js/app.js'])

</body>
</html>
