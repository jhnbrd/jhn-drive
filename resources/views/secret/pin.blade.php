<!DOCTYPE html>
<html lang="en" style="background-color: #070a0f; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Explicitly block all crawlers --}}
    <meta name="robots" content="noindex, nofollow, noarchive, noimageindex, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">

    {{-- Generic non-identifying title --}}
    <title>Authentication Required</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; height: 100%;
            background-color: #070a0f;
            color: #e2e8f0;
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        [x-cloak] { display: none !important; }

        .pin-grid {
            background: radial-gradient(ellipse at 50% 0%, #0d1a2a 0%, #070a0f 70%);
        }

        /* Animated noise overlay */
        @keyframes grain {
            0%, 100% { transform: translate(0, 0); }
            10%       { transform: translate(-2%, -3%); }
            30%       { transform: translate(3%, 2%); }
            50%       { transform: translate(-1%, 4%); }
            70%       { transform: translate(2%, -1%); }
            90%       { transform: translate(-3%, 1%); }
        }

        .noise::before {
            content: '';
            position: fixed;
            inset: -50%;
            width: 200%;
            height: 200%;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
            opacity: 0.04;
            animation: grain 8s steps(10) infinite;
            pointer-events: none;
            z-index: 0;
        }

        /* PIN dots */
        .pin-dot {
            width: 14px; height: 14px;
            border-radius: 50%;
            background: #1e293b;
            border: 2px solid #334155;
            transition: all 0.2s ease;
        }
        .pin-dot.filled {
            background: #38bdf8;
            border-color: #38bdf8;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.6);
        }
        .pin-dot.error {
            background: #ef4444;
            border-color: #ef4444;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%       { transform: translateX(-5px); }
            40%       { transform: translateX(5px); }
            60%       { transform: translateX(-4px); }
            80%       { transform: translateX(4px); }
        }

        /* Keypad buttons */
        .key-btn {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid #2d3748;
            color: #e2e8f0;
            font-size: 1.4rem;
            font-weight: 600;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.12s ease;
            -webkit-user-select: none; user-select: none;
            backdrop-filter: blur(10px);
        }
        .key-btn:hover  { background: rgba(56, 189, 248, 0.15); border-color: #38bdf8; color: #38bdf8; }
        .key-btn:active { background: rgba(56, 189, 248, 0.3); transform: scale(0.93); }

        .key-btn-del {
            background: rgba(239, 68, 68, 0.12);
            border-color: #7f1d1d;
            color: #f87171;
        }
        .key-btn-del:hover { background: rgba(239, 68, 68, 0.25); border-color: #ef4444; color: #ef4444; }

        .key-btn-submit {
            background: rgba(56, 189, 248, 0.2);
            border-color: #0e7490;
            color: #38bdf8;
        }
        .key-btn-submit:hover { background: rgba(56, 189, 248, 0.35); border-color: #38bdf8; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="noise">

<div class="pin-grid min-h-screen flex items-center justify-center p-6" 
     x-data="pinApp()"
     @keydown.window="handleKeyboard($event)">

    <div class="relative z-10 w-full max-w-xs">

        {{-- Lock icon --}}
        <div class="flex justify-center mb-8">
            <div class="h-20 w-20 rounded-3xl flex items-center justify-center"
                 style="background: linear-gradient(135deg, rgba(14,116,144,0.3), rgba(30,41,59,0.8)); border: 1px solid rgba(56,189,248,0.25); box-shadow: 0 0 40px rgba(56,189,248,0.1);">
                <svg class="h-10 w-10 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                          d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-center text-xl font-bold text-white mb-1">Restricted Access</h1>
        <p class="text-center text-sm text-slate-400 mb-8">Enter PIN to continue</p>

        {{-- Server-side error (form fallback) --}}
        @if ($errors->has('pin'))
            <div class="mb-4 rounded-xl border border-red-500/40 bg-red-950/40 px-4 py-3 text-xs text-red-300 text-center">
                {{ $errors->first('pin') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-xl border border-red-500/40 bg-red-950/40 px-4 py-3 text-xs text-red-300 text-center">
                {{ session('error') }}
            </div>
        @endif

        {{-- PIN dots indicator --}}
        <div class="flex justify-center gap-4 mb-8">
            <template x-for="i in 4" :key="i">
                <div class="pin-dot"
                     :class="{
                         'filled': i <= pin.length && !pinError,
                         'error':  i <= pin.length && pinError
                     }">
                </div>
            </template>
        </div>

        {{-- Error message --}}
        <div x-show="pinError" x-cloak class="mb-5 rounded-xl border border-red-500/30 bg-red-950/40 px-4 py-2.5 text-xs text-red-300 text-center">
            Incorrect PIN. Please try again.
        </div>

        {{-- Numeric keypad --}}
        <div class="grid grid-cols-3 gap-3 place-items-center mb-4">
            <template x-for="key in ['1','2','3','4','5','6','7','8','9']" :key="key">
                <button type="button" class="key-btn" @click="addDigit(key)" x-text="key"></button>
            </template>
            {{-- Row 4: del, 0, submit --}}
            <button type="button" class="key-btn key-btn-del" @click="deleteDigit()" title="Delete">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l6.414 6.414a2 2 0 001.414.586H19a2 2 0 002-2V7a2 2 0 00-2-2h-8.172a2 2 0 00-1.414.586L3 12z"/>
                </svg>
            </button>
            <button type="button" class="key-btn" @click="addDigit('0')">0</button>
            <button type="button" class="key-btn key-btn-submit" @click="submitPin()" title="Enter">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>

        {{-- Hidden form --}}
        <form id="pin-form" method="POST" action="{{ route('secret.verify') }}" style="display:none;">
            @csrf
            <input type="hidden" name="pin" id="pin-hidden">
        </form>

    </div>
</div>

<script>
function pinApp() {
    return {
        pin: '',
        pinError: false,
        maxLen: 4,

        addDigit(d) {
            if (this.pin.length >= this.maxLen) return;
            this.pinError = false;
            this.pin += d;
            if (this.pin.length === this.maxLen) {
                // Brief pause then submit for UX
                setTimeout(() => this.submitPin(), 150);
            }
        },

        deleteDigit() {
            this.pinError = false;
            this.pin = this.pin.slice(0, -1);
        },

        submitPin() {
            if (this.pin.length < this.maxLen) return;
            document.getElementById('pin-hidden').value = this.pin;
            document.getElementById('pin-form').submit();
        },

        handleKeyboard(e) {
            if (e.key >= '0' && e.key <= '9') {
                this.addDigit(e.key);
            } else if (e.key === 'Backspace' || e.key === 'Delete') {
                this.deleteDigit();
            } else if (e.key === 'Enter') {
                this.submitPin();
            }
        }
    }
}
</script>

</body>
</html>
