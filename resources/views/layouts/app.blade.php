<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" style="background-color: #0d1117; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'JHN Drive | Minimalist Cloud Storage')</title>

    <!-- Anti-Flash & SVG Reset Styles -->
    <style>
        html, body {
            background-color: #0d1117 !important;
            color: #f0f6fc !important;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        [x-cloak] { display: none !important; }
        svg {
            display: inline-block;
            vertical-align: middle;
        }
    </style>
    <!-- Tailwind and Alpine are compiled once through Vite. -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#0d1117] text-[#f0f6fc] antialiased selection:bg-sky-500/20 selection:text-sky-400 overflow-x-hidden"
      style="background-color: #0d1117; color: #f0f6fc;">
    
    @yield('content')

    @stack('scripts')
</body>
</html>
