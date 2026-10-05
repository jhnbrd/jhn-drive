<div x-show="mobileSidebarOpen"
     x-cloak
     x-transition.opacity
     class="fixed inset-0 z-40 bg-black/60 lg:hidden"
     @click="mobileSidebarOpen = false"
     aria-hidden="true"></div>

<aside class="jhn-sidebar fixed inset-y-0 left-0 z-50 flex w-[17rem] shrink-0 flex-col border-r px-3 py-4 transition-transform duration-200 lg:static lg:z-10 lg:w-60 lg:translate-x-0"
       :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       aria-label="Primary navigation">
    <div class="flex items-center justify-between px-2 pb-5">
        <a href="{{ route('drive.index') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-cyan-400 text-slate-950">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h10a4 4 0 004-4 4 4 0 00-3-3.87 5 5 0 00-9.6-1.5A4 4 0 003 15z"/>
                </svg>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold tracking-tight text-white">JHN Drive</span>
                <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Private storage
                </span>
            </span>
        </a>
        <button type="button"
                class="jhn-icon-button lg:hidden"
                @click="mobileSidebarOpen = false"
                aria-label="Close navigation">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="relative mb-5" x-data="{ openNew: false }">
        <button type="button"
                class="jhn-button-primary w-full"
                @click="openNew = !openNew"
                :aria-expanded="openNew">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"/>
            </svg>
            New
            <svg class="ml-auto h-3.5 w-3.5 transition-transform" :class="openNew ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="openNew"
             x-cloak
             @click.outside="openNew = false"
             class="jhn-surface absolute inset-x-0 top-full z-30 mt-2 rounded-lg p-1.5">
            <button type="button"
                    class="jhn-nav-item"
                    @click="$refs.fileInput.click(); openNew = false; mobileSidebarOpen = false">
                <svg class="h-4 w-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Upload files
            </button>
            <button type="button"
                    class="jhn-nav-item"
                    @click="openNewFolderDialog(); openNew = false; mobileSidebarOpen = false">
                <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                </svg>
                New folder
            </button>
        </div>
    </div>

    <input type="file" x-ref="fileInput" @change="handleFileInputChange($event)" multiple class="hidden">

    <nav class="flex-1 space-y-1" aria-label="Drive sections">
        <button type="button"
                class="jhn-nav-item"
                :class="viewSection === 'drive' && 'jhn-nav-item-active'"
                @click="switchSection('drive'); mobileSidebarOpen = false">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            My Drive
        </button>
        <button type="button"
                class="jhn-nav-item"
                :class="viewSection === 'shared' && 'jhn-nav-item-active'"
                @click="switchSection('shared'); mobileSidebarOpen = false">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.8 10.2a4 4 0 00-5.6 0l-4 4a4 4 0 105.6 5.6l1.1-1.1m-.7-4.9a4 4 0 005.6 0l4-4a4 4 0 00-5.6-5.6l-1.1 1.1"/>
            </svg>
            Shared links
            <span class="ml-auto rounded bg-slate-800 px-1.5 py-0.5 text-[10px] tabular-nums text-slate-400" x-text="sharedItems.length"></span>
        </button>
        <a href="{{ route('secret.pin') }}" class="jhn-nav-item">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4"/>
            </svg>
            Secret Vault
        </a>
        @if (auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.index') }}" class="jhn-nav-item">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.6-8A12 12 0 0112 3a12 12 0 01-8.6 3A12 12 0 003 9c0 5.6 3.8 10.3 9 11.6 5.2-1.3 9-6 9-11.6 0-1-.1-2-.4-3z"/>
                </svg>
                Admin
            </a>
        @endif
    </nav>

    <div class="mt-5 border-t border-slate-800 pt-4">
        <div class="mb-2 flex items-center justify-between text-[11px]">
            <span class="font-medium text-slate-400">Storage</span>
            <span class="tabular-nums text-slate-500" x-text="(stats.percent_used || 0) + '%'"></span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-slate-800">
            <div class="h-full rounded-full bg-cyan-400 transition-[width]"
                 :class="stats.percent_used >= 90 ? 'bg-rose-500' : (stats.percent_used >= 70 ? 'bg-amber-400' : 'bg-cyan-400')"
                 :style="'width: ' + Math.min(stats.percent_used || 0, 100) + '%'"></div>
        </div>
        <p class="mt-2 text-[11px] text-slate-500">
            <span class="font-medium text-slate-300" x-text="stats.used_human || '0 B'"></span>
            of <span x-text="stats.quota_human || '20 GB'"></span>
        </p>
    </div>
</aside>
