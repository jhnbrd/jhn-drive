<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark" style="background-color: #0d1117; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'JHN Drive | Minimalist Cloud Storage')</title>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Anti-Flash & SVG Reset Styles -->
    <style>
        html, body {
            background-color: #0d1117 !important;
            color: #f0f6fc !important;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
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

    <!-- Tailwind CSS Standalone CDN with Dark Theme Configuration (Guarantees styling under all proxy/DNS conditions) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            bg: '#0d1117',
                            surface: '#161b22',
                            elevated: '#21262d',
                            border: '#30363d',
                            text: '#f0f6fc',
                            muted: '#8b949e',
                        },
                        sky: {
                            accent: '#38bdf8'
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js CDN (Guarantees reactivity under all environments) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Local Compiled Vite Assets -->
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="min-h-screen bg-[#0d1117] text-[#f0f6fc] antialiased selection:bg-sky-500/20 selection:text-sky-400 overflow-x-hidden"
      style="background-color: #0d1117; color: #f0f6fc;">
    
    @yield('content')

    @stack('scripts')
</body>
</html>
