@extends('layouts.app')

@section('title', 'Admin Panel | JHN Drive')

@section('content')
<div class="min-h-screen bg-[#0d1117] text-[#f0f6fc] pb-16">
    
    <!-- Top Bar -->
    <header class="sticky top-0 z-30 flex flex-wrap items-center justify-between gap-3 border-b border-[#263241] bg-[#0d1219]/95 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-cyan-400 text-slate-950">
                <svg class="h-5 w-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-base font-bold tracking-tight text-white">JHN Drive • Superadmin Control Panel</h1>
                <p class="hidden text-[11px] text-slate-400 sm:block">Registration approvals and quota management</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('drive.index') }}" 
               class="flex items-center gap-2 rounded-xl border border-[#3b4b66] bg-[#1a2536] px-4 py-2 text-xs font-bold text-slate-200 hover:bg-[#263750] hover:text-white hover:border-sky-400 transition-colors shadow-sm">
                <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span>Back to Drive</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="rounded-xl border border-rose-500/40 bg-rose-950/30 px-3.5 py-2 text-xs font-bold text-rose-300 hover:bg-rose-600 hover:text-white hover:border-rose-500 transition-colors shadow-sm">
                    Sign Out
                </button>
            </form>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-8">

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-500/40 bg-emerald-500/15 p-4 text-xs font-semibold text-emerald-300 shadow-sm">
                <svg class="h-5 w-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (session('info'))
            <div class="mb-6 flex items-center gap-3 rounded-xl border border-sky-500/40 bg-sky-500/15 p-4 text-xs font-semibold text-sky-300 shadow-sm">
                <svg class="h-5 w-5 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 flex items-center gap-3 rounded-xl border border-rose-500/40 bg-rose-500/15 p-4 text-xs font-semibold text-rose-300 shadow-sm">
                <svg class="h-5 w-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Metrics Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="rounded-2xl border border-[#3b4b66] bg-[#161d2a] p-5 shadow-sm">
                <div class="text-xs font-bold text-slate-300">Total Registrations</div>
                <div class="mt-2 text-2xl font-black text-white">{{ $metrics['total_users'] }}</div>
                <div class="mt-1 text-[11px] text-slate-400">Across all statuses</div>
            </div>

            <div class="rounded-2xl border border-amber-500/40 bg-[#161d2a] p-5 relative overflow-hidden shadow-sm">
                <div class="text-xs font-bold text-amber-300">Pending Requests</div>
                <div class="mt-2 text-2xl font-black text-amber-400">{{ $metrics['pending_count'] }}</div>
                <div class="mt-1 text-[11px] text-slate-300">Awaiting your approval</div>
            </div>

            <div class="rounded-2xl border border-emerald-500/40 bg-[#161d2a] p-5 shadow-sm">
                <div class="text-xs font-bold text-emerald-300">Active Users</div>
                <div class="mt-2 text-2xl font-black text-emerald-400">{{ $metrics['approved_count'] }}</div>
                <div class="mt-1 text-[11px] text-slate-300">Approved with 20GB quota</div>
            </div>

            <div class="rounded-2xl border border-sky-500/40 bg-[#161d2a] p-5 shadow-sm">
                <div class="text-xs font-bold text-sky-300">Total Storage Used</div>
                <div class="mt-2 text-2xl font-black text-sky-400">{{ $metrics['total_used_human'] }}</div>
                <div class="mt-1 text-[11px] text-slate-300">Across all user drives</div>
            </div>
        </div>

        <!-- SECTION 1: PENDING ACCESS REQUESTS -->
        <div class="mb-10 rounded-2xl border border-[#3b4b66] bg-[#161d2a] overflow-hidden shadow-xl">
            <div class="border-b border-[#3b4b66] px-6 py-4 flex items-center justify-between bg-[#0e1420]">
                <div>
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>Pending Access Requests</span>
                        <span class="rounded-full bg-amber-500/20 border border-amber-500/40 px-2.5 py-0.5 text-[11px] text-amber-300 font-mono font-bold">
                            {{ $pendingUsers->count() }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">Approve to grant login access and initialize their 20 GB personal storage drive.</p>
                </div>
            </div>

            @if ($pendingUsers->isEmpty())
                <div class="px-6 py-12 text-center text-xs text-slate-400">
                    <svg class="mx-auto h-8 w-8 text-slate-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    No pending registration requests. All caught up!
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-[#f0f6fc]">
                        <thead class="border-b border-[#3b4b66] bg-[#0e1420] uppercase tracking-wider text-slate-200">
                            <tr>
                                <th class="py-3.5 px-6 font-extrabold">Applicant</th>
                                <th class="py-3.5 px-4 font-extrabold">Email</th>
                                <th class="py-3.5 px-4 font-extrabold">Note / Reason</th>
                                <th class="py-3.5 px-4 font-extrabold">Submitted</th>
                                <th class="py-3.5 px-6 text-right font-extrabold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2d3a50]">
                            @foreach ($pendingUsers as $applicant)
                                <tr class="hover:bg-[#1f2a3a] transition-colors">
                                    <td class="py-3.5 px-6 font-bold text-white">{{ $applicant->name }}</td>
                                    <td class="py-3.5 px-4 font-mono text-slate-300 font-semibold">{{ $applicant->email }}</td>
                                    <td class="py-3.5 px-4 text-slate-300 max-w-xs truncate" title="{{ $applicant->request_note }}">
                                        {{ $applicant->request_note ?: '—' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-slate-300 whitespace-nowrap">{{ $applicant->created_at->diffForHumans() }}</td>
                                    <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-2">
                                            <!-- Approve Form -->
                                            <form method="POST" action="{{ route('admin.users.approve', $applicant->id) }}">
                                                @csrf
                                                <button type="submit" 
                                                        class="rounded-xl bg-emerald-500 px-4 py-1.5 text-xs font-extrabold text-slate-950 hover:bg-emerald-400 transition-colors shadow-sm">
                                                    Approve (20 GB)
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="{{ route('admin.users.reject', $applicant->id) }}">
                                                @csrf
                                                <button type="submit" 
                                                        class="rounded-xl border border-rose-500/50 bg-rose-950/50 px-3.5 py-1.5 text-xs font-bold text-rose-300 hover:bg-rose-600 hover:text-white hover:border-rose-400 transition-colors shadow-sm">
                                                    Decline
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- SECTION 2: APPROVED USERS & STORAGE QUOTAS -->
        <div class="rounded-2xl border border-[#3b4b66] bg-[#161d2a] overflow-hidden shadow-xl">
            <div class="border-b border-[#3b4b66] px-6 py-4 flex items-center justify-between bg-[#0e1420]">
                <div>
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <span>Active Users & 20 GB Quotas</span>
                        <span class="rounded-full bg-emerald-500/20 border border-emerald-500/40 px-2.5 py-0.5 text-[11px] text-emerald-300 font-mono font-bold">
                            {{ $approvedUsers->count() }}
                        </span>
                    </h2>
                    <p class="text-xs text-slate-300 mt-0.5">Individual quota consumption and active accounts.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-[#f0f6fc]">
                    <thead class="border-b border-[#3b4b66] bg-[#0e1420] uppercase tracking-wider text-slate-200">
                        <tr>
                            <th class="py-3.5 px-6 font-extrabold">User</th>
                            <th class="py-3.5 px-4 font-extrabold">Role</th>
                            <th class="py-3.5 px-4 font-extrabold">Storage Usage (20 GB Quota)</th>
                            <th class="py-3.5 px-4 font-extrabold">Shared Links</th>
                            <th class="py-3.5 px-4 font-extrabold">Approved At</th>
                            <th class="py-3.5 px-6 text-right font-extrabold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#2d3a50]">
                        @foreach ($approvedUsers as $user)
                            <tr class="hover:bg-[#1f2a3a] transition-colors">
                                <td class="py-3.5 px-6">
                                    <div class="font-bold text-white">{{ $user->name }}</div>
                                    <div class="text-[11px] font-mono text-slate-300">{{ $user->email }}</div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($user->isSuperAdmin())
                                        <span class="rounded-md bg-sky-500/20 border border-sky-500/40 px-2 py-0.5 text-[10px] font-bold text-sky-300">
                                            Superadmin
                                        </span>
                                    @else
                                        <span class="rounded-md bg-[#1e293b] border border-[#3b4b66] px-2 py-0.5 text-[10px] font-semibold text-slate-300">
                                            User
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 min-w-[200px]">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-bold text-white">{{ $user->humanUsedStorage() }}</span>
                                        <span class="text-slate-300 font-semibold">{{ $user->storagePercentage() }}% of 20 GB</span>
                                    </div>
                                    <div class="h-2 w-full rounded-full bg-[#0e1420] border border-[#3b4b66] overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-sky-400 to-sky-500" 
                                             style="width: {{ min($user->storagePercentage(), 100) }}%"></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-sky-300 whitespace-nowrap font-mono font-bold">
                                    {{ $user->shared_links_count }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-300 whitespace-nowrap font-medium">
                                    {{ $user->approved_at ? $user->approved_at->format('M j, Y') : '—' }}
                                </td>
                                <td class="py-3.5 px-6 text-right whitespace-nowrap">
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" onsubmit="return confirm('Permanently delete user {{ $user->name }} and all their uploaded files?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="rounded-xl border border-rose-500/40 bg-rose-950/40 p-2 text-rose-300 hover:bg-rose-600 hover:text-white hover:border-rose-400 transition-colors shadow-sm inline-flex items-center justify-center"
                                                    title="Delete user and wipe storage">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-[11px] text-slate-400 font-semibold italic">Active session</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
