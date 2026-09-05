@extends('layouts.app')

@section('title', 'Sign In | JHN Drive')

@section('content')
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8 bg-[#0d1117] text-[#f0f6fc]">
    
    <!-- Ambient Glow -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden flex items-center justify-center">
        <div class="h-96 w-96 rounded-full bg-sky-500/5 blur-3xl"></div>
    </div>

    <!-- Login Card -->
    <div class="relative w-full max-w-md rounded-3xl border border-[#3b4b66] bg-[#161d2a] p-8 shadow-2xl shadow-black/80">
        
        <!-- Brand Header -->
        <div class="flex flex-col items-center text-center mb-8">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-sky-600 text-slate-950 shadow-lg shadow-sky-500/30 mb-3">
                <svg class="h-7 w-7 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h10a4 4 0 004-4 4 4 0 00-3-3.87 5 5 0 00-9.6-1.5A4 4 0 003 15z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white">Welcome to JHN Drive</h1>
            <p class="mt-1 text-xs font-medium text-slate-300">Self-hosted minimalist cloud storage • 20 GB per user</p>
        </div>

        <!-- Session & Error Alerts -->
        @if ($errors->any())
            <div class="mb-6 rounded-2xl border border-rose-500/40 bg-rose-500/15 p-4 text-xs font-semibold text-rose-300 shadow-sm">
                <div class="flex items-start gap-2.5">
                    <svg class="h-4 w-4 shrink-0 text-rose-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-6 rounded-2xl border border-emerald-500/40 bg-emerald-500/15 p-3 text-xs font-semibold text-emerald-300 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        <!-- Form -->
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-slate-200 mb-1.5">Email address</label>
                <input id="email" 
                       name="email" 
                       type="email" 
                       autocomplete="email" 
                       required 
                       value="{{ old('email') }}"
                       placeholder="you@example.com"
                       class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-200 mb-1.5">Password</label>
                <input id="password" 
                       name="password" 
                       type="password" 
                       autocomplete="current-password" 
                       required 
                       placeholder="••••••••"
                       class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center gap-2 text-xs text-slate-300 font-medium cursor-pointer">
                    <input type="checkbox" 
                           name="remember" 
                           class="rounded border-[#3b4b66] bg-[#0e1420] text-sky-500 focus:ring-sky-400 focus:ring-offset-0">
                    <span>Remember this device</span>
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" 
                    class="mt-2 w-full rounded-xl bg-sky-500 py-3 text-sm font-extrabold text-slate-950 shadow-lg shadow-sky-500/25 hover:bg-sky-400 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-[#161d2a]">
                Sign In
            </button>
        </form>

        <!-- Request Access Link -->
        <div class="mt-6 border-t border-[#2d3a50] pt-6 text-center text-xs text-slate-300">
            Don't have an account yet?
            <a href="{{ route('request-access') }}" class="font-bold text-sky-400 hover:text-sky-300 ml-1 transition-colors hover:underline">
                Request access →
            </a>
        </div>
    </div>

    <!-- Subdomain / Host info -->
    <div class="mt-6 text-center text-[11px] text-slate-400 font-medium">
        {{ config('app.url') }} • Secured with Superadmin Approval
    </div>

</div>
@endsection
