        <!-- Top Toolbar & Header -->
        <header class="flex h-16 w-full items-center justify-between border-b border-[#262f3d] bg-[#131822]/90 backdrop-blur-md px-6 shrink-0 z-10">
            
            <!-- Breadcrumbs Navigation (When in Drive Section) -->
            <div x-show="viewSection === 'drive'" class="flex items-center gap-1.5 text-xs overflow-x-auto py-1">
                <template x-for="(crumb, idx) in breadcrumbs" :key="crumb.path">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button @click="navigateTo(crumb.path)" 
                                :class="idx === breadcrumbs.length - 1 ? 'font-bold text-sky-300 bg-sky-500/20 border border-sky-400/50' : 'text-slate-200 hover:text-white hover:bg-[#1f293d] border border-transparent hover:border-[#334155]'"
                                class="rounded-lg px-3 py-1.5 transition-colors font-medium"
                                x-text="crumb.name">
                        </button>
                        <span x-show="idx < breadcrumbs.length - 1" class="text-slate-400 font-bold select-none">/</span>
                    </div>
                </template>
            </div>

            <!-- Title Header (When in Shared Links Section) -->
            <div x-show="viewSection === 'shared'" class="flex items-center gap-2 py-1">
                <h2 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span>Shared Links Management</span>
                </h2>
                <span class="rounded-full bg-emerald-500/20 px-3 py-0.5 text-xs font-bold text-emerald-300 border border-emerald-500/40 font-mono" x-text="sharedItems.length + ' active'"></span>
            </div>

            <!-- Right Controls: Search, View Switcher & User Profile -->
            <div class="flex items-center gap-3 shrink-0">
                
                <!-- Search Input -->
                <div class="relative w-56 sm:w-64">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           x-model="searchQuery" 
                           :placeholder="viewSection === 'drive' ? 'Search your drive...' : 'Search shared items...'" 
                           class="w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] py-2 pl-9 pr-8 text-xs text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 transition-colors">
                    <button x-show="searchQuery" 
                            @click="searchQuery = ''" 
                            class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-300 hover:text-white">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- View Mode Toggle (Grid vs List) -->
                <div x-show="viewSection === 'drive'" class="flex rounded-xl border border-[#3b4b66] bg-[#0e1420] p-1">
                    <button @click="viewMode = 'grid'" 
                            :class="viewMode === 'grid' ? 'bg-sky-500/25 text-sky-300 border border-sky-400/40 font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-[#1f293d] border border-transparent'"
                            class="rounded-lg p-1.5 transition-colors"
                            title="Grid View">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </button>
                    <button @click="viewMode = 'list'" 
                            :class="viewMode === 'list' ? 'bg-sky-500/25 text-sky-300 border border-sky-400/40 font-bold shadow-sm' : 'text-slate-300 hover:text-white hover:bg-[#1f293d] border border-transparent'"
                            class="rounded-lg p-1.5 transition-colors"
                            title="List View">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                    </button>
                </div>

                <!-- Refresh Button -->
                <button @click="viewSection === 'drive' ? loadFiles(currentPath) : loadSharedLinks()" 
                        :class="isLoading ? 'animate-spin text-sky-400' : 'text-slate-200 hover:text-white'"
                        class="rounded-xl border border-[#3b4b66] bg-[#1a2536] p-2 hover:bg-[#263750] hover:border-sky-400 transition-colors shadow-sm" 
                        title="Refresh">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>

                <!-- User Profile & Superadmin Menu -->
                <div class="relative ml-1" x-data="{ openUserMenu: false }" @click.outside="openUserMenu = false">
                    <button @click="openUserMenu = !openUserMenu" 
                            class="flex items-center gap-2.5 rounded-xl border border-[#3b4b66] bg-[#1a2536] px-3.5 py-1.5 text-xs text-white hover:border-sky-400 hover:bg-[#263750] transition-colors shadow-sm">
                        <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-500 text-slate-950 font-extrabold text-[11px] shadow-sm">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="max-w-[100px] truncate font-bold text-white">{{ auth()->user()->name }}</span>
                        <svg class="h-3.5 w-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Content -->
                    <div x-show="openUserMenu" 
                         x-cloak
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         class="absolute right-0 top-full z-40 mt-2 w-64 rounded-2xl border border-[#3b4b66] bg-[#161d2a] p-2 shadow-2xl">
                        
                        <div class="px-3.5 py-3 border-b border-[#2d394b] mb-2 bg-[#0e1420] rounded-xl">
                            <div class="font-bold text-xs text-white truncate">{{ auth()->user()->name }}</div>
                            <div class="text-[11px] font-mono text-slate-300 truncate mt-0.5">{{ auth()->user()->email }}</div>
                            <div class="mt-2.5 flex items-center gap-1.5">
                                <span class="rounded-md bg-sky-500/20 text-sky-300 text-[10px] px-2 py-0.5 font-bold border border-sky-500/40">20 GB Quota</span>
                                @if (auth()->user()->isSuperAdmin())
                                    <span class="rounded-md bg-emerald-500/20 text-emerald-300 text-[10px] px-2 py-0.5 font-bold border border-emerald-500/40">Superadmin</span>
                                @endif
                            </div>
                        </div>

                        @if (auth()->user()->isSuperAdmin())
                            <a href="{{ route('admin.index') }}" 
                               class="flex w-full items-center gap-2.5 rounded-xl border border-[#3b4b66] bg-[#1a2536] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-sky-300 hover:border-sky-400 transition-colors mb-1.5 shadow-sm">
                                <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Superadmin Panel</span>
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-rose-500/30 bg-rose-950/30 px-3.5 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-600 hover:text-white transition-colors">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </header>
