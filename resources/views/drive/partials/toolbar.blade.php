<header class="jhn-toolbar z-20 flex min-h-16 shrink-0 flex-wrap items-center gap-3 border-b px-3 py-3 sm:px-5 lg:flex-nowrap lg:px-6">
    <button type="button"
            class="jhn-icon-button lg:hidden"
            @click="mobileSidebarOpen = true"
            aria-label="Open navigation">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <div class="min-w-0 flex-1">
        <div x-show="viewSection === 'home'" class="flex items-center gap-2">
            <h2 class="truncate text-sm font-semibold text-white">Home</h2>
            <span class="hidden text-[11px] text-slate-500 sm:inline">Overview</span>
        </div>
        <div x-show="viewSection === 'drive'" class="flex min-w-0 items-center gap-1 overflow-x-auto py-1 text-xs">
            <template x-for="(crumb, index) in breadcrumbs" :key="crumb.path">
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button"
                            @click="navigateTo(crumb.path)"
                            class="max-w-40 truncate rounded-md px-2 py-1.5 font-medium transition-colors"
                            :class="index === breadcrumbs.length - 1 ? 'bg-cyan-400/10 text-cyan-300' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
                            x-text="crumb.name"></button>
                    <span x-show="index < breadcrumbs.length - 1" class="text-slate-700">/</span>
                </div>
            </template>
        </div>
        <div x-show="viewSection === 'shared'" class="flex items-center gap-2">
            <h2 class="truncate text-sm font-semibold text-white">Shared links</h2>
            <span class="rounded bg-emerald-400/10 px-2 py-0.5 text-[10px] font-medium text-emerald-300" x-text="sharedItems.length + ' active'"></span>
        </div>
    </div>

    <div class="order-3 flex w-full items-center gap-2 lg:order-none lg:w-auto"
         :class="viewSection === 'home' && 'ml-auto !order-none !w-auto'">
        <label x-show="viewSection !== 'home'" class="relative min-w-0 flex-1 lg:w-64 lg:flex-none">
            <span class="sr-only">Search current section</span>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="search"
                   x-model="searchQuery"
                   :placeholder="viewSection === 'drive' ? 'Filter this folder' : 'Filter shared links'"
                   class="h-10 w-full rounded-lg border border-slate-700 bg-[#0b1017] pl-9 pr-3 text-xs text-white placeholder-slate-500 outline-none transition focus:border-cyan-400">
        </label>

        <div x-show="viewSection === 'drive'" class="hidden items-center rounded-lg border border-slate-700 bg-[#0b1017] p-0.5 sm:flex">
            <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-md"
                    :class="viewMode === 'grid' ? 'bg-slate-700 text-cyan-300' : 'text-slate-500 hover:text-white'"
                    @click="viewMode = 'grid'"
                    aria-label="Grid view"
                    :aria-pressed="viewMode === 'grid'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z"/>
                </svg>
            </button>
            <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-md"
                    :class="viewMode === 'list' ? 'bg-slate-700 text-cyan-300' : 'text-slate-500 hover:text-white'"
                    @click="viewMode = 'list'"
                    aria-label="List view"
                    :aria-pressed="viewMode === 'list'">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 6h14M5 12h14M5 18h14"/>
                </svg>
            </button>
        </div>

        <button type="button"
                class="jhn-icon-button"
                @click="viewSection === 'home' ? loadHome(true) : (viewSection === 'drive' ? loadFiles(currentPath) : loadSharedLinks())"
                :class="(isLoading || isHomeLoading) && 'animate-spin text-cyan-300'"
                aria-label="Refresh">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5m11 11v-5h-5M5.5 15A7 7 0 0018 17m.5-8A7 7 0 006 7"/>
            </svg>
        </button>
    </div>

    <div class="relative" x-data="{ openUserMenu: false }" @click.outside="openUserMenu = false">
        <button type="button"
                class="flex h-10 items-center gap-2 rounded-lg border border-slate-700 bg-[#111821] px-2 text-xs text-white hover:border-slate-600"
                @click="openUserMenu = !openUserMenu"
                :aria-expanded="openUserMenu"
                aria-label="Open account menu">
            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-cyan-400/15 font-bold text-cyan-300">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </span>
            <span class="hidden max-w-28 truncate font-medium sm:block">{{ auth()->user()->name }}</span>
            <svg class="hidden h-3.5 w-3.5 text-slate-500 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="openUserMenu"
             x-cloak
             class="jhn-surface absolute right-0 top-full z-40 mt-2 w-64 rounded-lg p-2">
            <div class="border-b border-slate-800 px-3 py-2.5">
                <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
            </div>
            @if (auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.index') }}" class="jhn-nav-item mt-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.6-8A12 12 0 0112 3a12 12 0 01-8.6 3A12 12 0 003 9c0 5.6 3.8 10.3 9 11.6 5.2-1.3 9-6 9-11.6 0-1-.1-2-.4-3z"/>
                    </svg>
                    Admin
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="jhn-nav-item text-rose-300 hover:text-rose-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Sign out
                </button>
            </form>
        </div>
    </div>
</header>
