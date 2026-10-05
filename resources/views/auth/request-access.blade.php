@extends('layouts.app')

@section('title', 'Request Access | JHN Drive')

@section('content')
<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8 bg-[#0d1117] text-[#f0f6fc]">
    

    <!-- Request Access Card -->
    <div class="relative w-full max-w-lg rounded-3xl border border-[#3b4b66] bg-[#161d2a] p-8 shadow-2xl shadow-black/80">
        
        @if ($submitted ?? false)
            <!-- Confirmation Screen -->
            <div class="flex flex-col items-center text-center py-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-lg shadow-emerald-500/20 mb-4">
                    <svg class="h-8 w-8 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h2 class="text-2xl font-black tracking-tight text-white">Access Request Submitted!</h2>
                <p class="mt-2 text-sm text-slate-300 max-w-md">
                    Thank you, <span class="text-white font-bold">{{ $applicant->name }}</span>. Your request has been queued for review by the Superadmin.
                </p>

                <div class="mt-6 w-full rounded-2xl border border-[#3b4b66] bg-[#0e1420] p-4 text-left text-xs space-y-2.5">
                    <div class="flex justify-between">
                        <span class="text-slate-400 font-medium">Registered Email:</span>
                        <span class="font-mono text-white font-semibold">{{ $applicant->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400 font-medium">Account Status:</span>
                        <span class="font-bold text-amber-400">Pending Approval</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400 font-medium">Storage Tier:</span>
                        <span class="font-bold text-sky-400">20 GB Personal Drive</span>
                    </div>
                </div>

                <p class="mt-4 text-xs text-slate-300">
                    Once approved by the superadmin, you will be able to log in immediately using your password.
                </p>

                <div class="mt-6 w-full">
                    <a href="{{ route('login') }}" 
                       class="inline-flex w-full items-center justify-center rounded-xl bg-sky-500 py-3 text-sm font-extrabold text-slate-950 shadow-lg shadow-sky-500/25 hover:bg-sky-400 transition-all">
                        Return to Sign In
                    </a>
                </div>
            </div>

        @else
            <!-- Access Request Form -->
            <div class="flex flex-col items-center text-center mb-6">
                <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-lg bg-cyan-400 text-slate-950">
                    <svg class="h-7 w-7 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-black tracking-tight text-white">Request Drive Access</h1>
                <p class="mt-1 text-xs font-medium text-slate-300">Access is approval-only. Each approved user receives a dedicated 20 GB storage drive.</p>
                
                <!-- 20GB Quota Highlight Pill -->
                <div class="mt-3 inline-flex items-center gap-1.5 rounded-full border border-sky-500/40 bg-sky-500/15 px-3 py-1 text-xs font-bold text-sky-300">
                    <svg class="h-3.5 w-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>20 GB Private Cloud Storage Included</span>
                </div>
            </div>

            <!-- Errors -->
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

            <!-- Form -->
            <form method="POST" action="{{ route('request-access') }}" class="space-y-4">
                @csrf

                <!-- Name -->
                <div>
                    <label for="name" class="block text-xs font-bold text-slate-200 mb-1.5">Full Name</label>
                    <input id="name" 
                           name="name" 
                           type="text" 
                           required 
                           value="{{ old('name') }}"
                           placeholder="Jihan Doe"
                           class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-200 mb-1.5">Email address</label>
                    <input id="email" 
                           name="email" 
                           type="email" 
                           required 
                           value="{{ old('email') }}"
                           placeholder="jihan@example.com"
                           class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
                </div>

                <!-- Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-200 mb-1.5">Password (min 8)</label>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               placeholder="••••••••"
                               class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-slate-200 mb-1.5">Confirm Password</label>
                        <input id="password_confirmation" 
                               name="password_confirmation" 
                               type="password" 
                               required 
                               placeholder="••••••••"
                               class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">
                    </div>
                </div>

                <!-- Reason / Note for Superadmin -->
                <div>
                    <label for="request_note" class="block text-xs font-bold text-slate-200 mb-1.5">Reason / Intended Use (Optional note for admin)</label>
                    <textarea id="request_note" 
                              name="request_note" 
                              rows="3"
                              placeholder="e.g., Personal backup for client projects, video assets..."
                              class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors shadow-sm">{{ old('request_note') }}</textarea>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="mt-2 w-full rounded-xl bg-sky-500 py-3 text-sm font-extrabold text-slate-950 shadow-lg shadow-sky-500/25 hover:bg-sky-400 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-[#161d2a]">
                    Submit Registration Request
                </button>
            </form>

            <!-- Return to Login Link -->
            <div class="mt-6 border-t border-[#2d3a50] pt-6 text-center text-xs text-slate-300">
                Already have an approved account?
                <a href="{{ route('login') }}" class="font-bold text-sky-400 hover:text-sky-300 ml-1 transition-colors hover:underline">
                    Sign in here →
                </a>
            </div>
        @endif

    </div>

</div>
@endsection
