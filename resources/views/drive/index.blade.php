@extends('layouts.app')

@section('title', 'JHN Drive | Minimalist Cloud Storage')

@section('content')
<div x-data="driveApp()" 
     x-init="init()" 
     @dragover.prevent="isDragging = true" 
     @dragleave.prevent="onDragLeave($event)" 
     @drop.prevent="handleDrop($event)"
     @keydown.escape.window="showPreviewModal = false; showNewFolderModal = false; showDeleteModal = false"
     class="flex h-screen w-screen overflow-hidden bg-[#0b0e14] text-[#f8fafc]">

    <!-- ========================================================================= -->
    <!-- DRAG AND DROP OVERLAY -->
    <!-- ========================================================================= -->
    <div x-show="isDragging" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b0e14]/90 backdrop-blur-md border-4 border-dashed border-sky-400 pointer-events-none">
        <div class="text-center">
            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-sky-500/20 text-sky-400 border border-sky-400/40 shadow-2xl shadow-sky-500/20 animate-bounce">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h3 class="mt-5 text-2xl font-bold text-white">Drop files to upload</h3>
            <p class="mt-2 text-sm text-[#94a3b8]">Files will be stored in your isolated 20 GB drive at <span class="font-mono text-sky-400" x-text="'/' + currentPath"></span></p>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SIDEBAR -->
    <!-- ========================================================================= -->
    <aside class="flex w-64 flex-col border-r border-[#262f3d] bg-[#131822] px-4 py-5 select-none shrink-0 z-10 shadow-xl">
        
        <!-- Brand Header -->
        <div class="flex items-center gap-3 px-2 mb-6">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br from-sky-400 to-sky-600 text-slate-950 shadow-lg shadow-sky-500/30">
                <svg class="h-6 w-6 stroke-2 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h10a4 4 0 004-4 4 4 0 00-3-3.87 5 5 0 00-9.6-1.5A4 4 0 003 15z" />
                </svg>
            </div>
            <div>
                <h1 class="text-base font-bold tracking-tight text-white flex items-center gap-1.5">
                    <span>JHN Drive</span>
                </h1>
                <div class="flex items-center gap-1.5 text-[11px] text-[#94a3b8]">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-pulse shadow-sm shadow-emerald-400/50"></span>
                    <span class="font-medium text-[#cbd5e1]">Port 8088 • Ready</span>
                </div>
            </div>
        </div>

        <!-- + New Action Menu (High Contrast) -->
        <div class="relative mb-6" x-data="{ openNew: false }">
            <button @click="openNew = !openNew" 
                    type="button" 
                    class="flex w-full items-center justify-between gap-2 rounded-xl bg-sky-500 px-4 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/30 hover:bg-sky-400 active:scale-[0.98] transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-2 focus:ring-offset-[#131822]">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>+ New Upload</span>
                </div>
                <svg class="h-4 w-4 transition-transform duration-150 text-white" :class="openNew ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Dropdown Items -->
            <div x-show="openNew" 
                 @click.outside="openNew = false"
                 x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute left-0 right-0 top-full z-30 mt-2 rounded-xl border border-[#334155] bg-[#1a2230] p-1.5 shadow-2xl">
                
                <button @click="$refs.fileInput.click(); openNew = false" 
                        class="flex w-full items-center gap-3 rounded-lg px-3.5 py-2.5 text-xs font-medium text-white hover:bg-[#253043] hover:text-sky-400 transition-colors">
                    <svg class="h-4 w-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span>Upload File</span>
                </button>

                <button @click="openNewFolderDialog(); openNew = false" 
                        class="flex w-full items-center gap-3 rounded-lg px-3.5 py-2.5 text-xs font-medium text-white hover:bg-[#253043] hover:text-sky-400 transition-colors">
                    <svg class="h-4 w-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                    </svg>
                    <span>New Folder</span>
                </button>
            </div>
        </div>

        <!-- Hidden Native File Upload Input -->
        <input type="file" 
               x-ref="fileInput" 
               @change="handleFileInputChange($event)" 
               multiple 
               class="hidden">

        <!-- Navigation Links -->
        <nav class="flex-1 space-y-1.5">
            <!-- My Drive Link -->
            <button @click="switchSection('drive')" 
                    :class="viewSection === 'drive' ? 'bg-sky-500/20 text-sky-300 font-bold border border-sky-400/50 shadow-sm shadow-sky-500/10' : 'text-slate-200 hover:bg-[#1f293d] hover:text-white border border-transparent hover:border-[#334155] font-medium'"
                    class="flex w-full items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs transition-all">
                <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <span>My Drive</span>
            </button>

            <!-- Shared Links Section Link -->
            <button @click="switchSection('shared')" 
                    :class="viewSection === 'shared' ? 'bg-sky-500/20 text-sky-300 font-bold border border-sky-400/50 shadow-sm shadow-sky-500/10' : 'text-slate-200 hover:bg-[#1f293d] hover:text-white border border-transparent hover:border-[#334155] font-medium'"
                    class="flex w-full items-center justify-between rounded-xl px-3.5 py-2.5 text-xs transition-all">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    <span>Shared Links</span>
                </div>
                <span class="rounded-md bg-[#1f293d] border border-[#37465e] px-2.5 py-0.5 text-[10px] font-mono font-bold text-sky-300" x-text="sharedItems.length"></span>
            </button>
        </nav>

        <!-- 20 GB Storage Quota Meter (High Contrast) -->
        <div class="mt-auto border-t border-[#262f3d] pt-4">
            <div class="flex items-center justify-between text-xs mb-2">
                <span class="font-bold text-white">Storage Quota</span>
                <span class="font-mono text-sky-400 font-extrabold" x-text="(stats.percent_used || 0) + '% used'"></span>
            </div>
            
            <!-- Progress Bar with dynamic warning colors -->
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-[#0e1420] border border-[#334155]">
                <div class="h-full transition-all duration-300"
                     :class="(stats.percent_used >= 90) ? 'bg-rose-500' : ((stats.percent_used >= 70) ? 'bg-amber-400' : 'bg-gradient-to-r from-sky-400 to-sky-500')"
                     :style="'width: ' + Math.min(stats.percent_used || 0, 100) + '%'"></div>
            </div>

            <div class="mt-2.5 flex items-center justify-between text-[11px]">
                <span class="text-slate-200"><strong class="text-white font-bold" x-text="stats.used_human || '0 B'"></strong> of 20 GB</span>
                <span class="text-emerald-400 font-bold font-mono text-[11px]" x-text="(stats.free_human || '20 GB') + ' free'"></span>
            </div>
        </div>
    </aside>

    <!-- ========================================================================= -->
    <!-- MAIN VIEWPORT -->
    <!-- ========================================================================= -->
    <div class="flex flex-1 flex-col overflow-hidden min-w-0">
        
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

        <!-- Main Scrollable Content Area -->
        <main class="flex-1 overflow-y-auto px-6 py-8" @click="activeMenu = null">
            
            <!-- Upload Progress Indicator Bar -->
            <div x-show="isUploading" 
                 x-cloak
                 class="mb-6 rounded-2xl border border-sky-500/40 bg-[#161d2a] p-4 shadow-xl">
                <div class="flex items-center justify-between text-xs mb-2">
                    <span class="flex items-center gap-2 font-bold text-sky-400">
                        <svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Uploading to 20 GB Drive...
                    </span>
                    <span class="text-white font-mono font-bold" x-text="uploadProgress + '%'"></span>
                </div>
                <div class="h-2 w-full rounded-full bg-[#253043] overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-sky-400 to-sky-500 transition-all duration-150" :style="'width: ' + uploadProgress + '%'"></div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 1: MY DRIVE (FILES & FOLDERS) -->
            <!-- ============================================================= -->
            <div x-show="viewSection === 'drive'">
                
                <!-- PROMINENT CENTERED EMPTY STATE -->
                <div x-show="!isLoading && filteredItems.length === 0" 
                     x-cloak
                     class="mx-auto max-w-lg rounded-3xl border border-[#3b4b66] bg-[#131822] p-10 text-center shadow-2xl mt-12">
                    <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-sky-500/15 text-sky-400 border border-sky-500/30 shadow-inner">
                        <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 15a4 4 0 004 4h10a4 4 0 004-4 4 4 0 00-3-3.87 5 5 0 00-9.6-1.5A4 4 0 003 15z" />
                        </svg>
                    </div>
                    <h3 class="mt-6 text-xl font-bold text-white">Your drive folder is empty</h3>
                    <p class="mt-2 text-xs text-slate-300 leading-relaxed">
                        Drag and drop files anywhere on this page, or click below to upload directly to your private 20 GB cloud drive.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <button @click="$refs.fileInput.click()" 
                                class="inline-flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-sky-400 transition-colors shadow-lg shadow-sky-500/25">
                            <svg class="h-4 w-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <span>Upload Files</span>
                        </button>
                        <button @click="openNewFolderDialog()" 
                                class="inline-flex items-center gap-2 rounded-xl border border-[#3b4b66] bg-[#1e293b] px-5 py-2.5 text-xs font-bold text-slate-200 hover:bg-[#2d3b52] hover:text-white hover:border-slate-400 transition-colors shadow-sm">
                            <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            <span>New Folder</span>
                        </button>
                    </div>
                </div>

                <!-- FOLDERS SECTION -->
                <div x-show="folders.length > 0" class="mb-8">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-200 mb-3 flex items-center gap-2">
                        <span>Folders</span>
                        <span class="rounded-full bg-[#1a2536] border border-[#3b4b66] px-2.5 py-0.5 text-[10px] text-sky-300 font-mono font-bold" x-text="folders.length"></span>
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3.5">
                        <template x-for="folder in folders" :key="folder.path">
                            <div @dblclick="navigateTo(folder.path)" 
                                 :class="activeMenu === folder.path ? 'z-40 ring-1 ring-sky-400' : 'z-10'"
                                 class="group relative flex items-center justify-between rounded-2xl border border-[#2d3a50] bg-[#131a26] p-3.5 hover:border-sky-400 hover:bg-[#182333] transition-all cursor-pointer select-none shadow-md">
                                <div @click="navigateTo(folder.path)" class="flex items-center gap-3 overflow-hidden flex-1">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-4.5a3 3 0 00-2.12.88L6.88 8.38A3 3 0 014.76 9H4.5A3 3 0 001.5 12v6a3 3 0 003 3h15z"/>
                                        </svg>
                                    </div>
                                    <div class="overflow-hidden">
                                        <div class="flex items-center gap-1.5">
                                            <span class="truncate text-xs font-bold text-white group-hover:text-sky-300 transition-colors" x-text="folder.name"></span>
                                            <span x-show="folder.share_token" title="Shared Folder Link Active" class="h-2.5 w-2.5 rounded-full bg-emerald-400 shrink-0 shadow-sm shadow-emerald-400/50"></span>
                                        </div>
                                        <div class="text-[11px] font-medium text-slate-300" x-text="folder.human_size"></div>
                                    </div>
                                </div>

                                <!-- Context Trigger -->
                                <div class="relative shrink-0" @click.stop>
                                    <button @click="toggleMenu(folder.path)" 
                                            class="rounded-xl border border-[#3b4b66] bg-[#1a2536] p-2 text-slate-200 hover:bg-[#263750] hover:border-sky-400 hover:text-sky-300 transition-colors shadow-sm"
                                            title="Folder options">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                        </svg>
                                    </button>

                                    <!-- Dropdown Menu for Folder -->
                                    <div x-show="activeMenu === folder.path" 
                                         x-cloak
                                         @click.outside="activeMenu = null"
                                         class="absolute right-0 top-full z-50 mt-1.5 w-48 rounded-2xl border border-[#3b4b66] bg-[#161d2a] p-2 shadow-2xl">
                                        <button @click="navigateTo(folder.path); activeMenu = null" 
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-sky-300 transition-colors">
                                            <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>Open Folder</span>
                                        </button>
                                        
                                        <!-- Share Folder / Copy Link -->
                                        <button @click="shareItem(folder); activeMenu = null" 
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-emerald-300 transition-colors">
                                            <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                            </svg>
                                            <span x-text="folder.share_token ? 'Copy Share Link' : 'Share Folder'"></span>
                                        </button>

                                        <!-- Unshare Folder if active -->
                                        <button x-show="folder.share_token" 
                                                @click="unshareItem(folder); activeMenu = null" 
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-amber-300 hover:bg-amber-500/20 transition-colors">
                                            <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <span>Unshare Folder</span>
                                        </button>

                                        <div class="my-1.5 border-t border-[#2d394b]"></div>
                                        <button @click="renameItem(folder); activeMenu = null" 
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-sky-300 hover:bg-[#263750] transition-colors">
                                            <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            <span>Rename</span>
                                        </button>
                                        <button @click="confirmDelete(folder); activeMenu = null" 
                                                class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-500/20 transition-colors">
                                            <svg class="h-4 w-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- FILES SECTION -->
                <div x-show="files.length > 0">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-200 mb-3 flex items-center gap-2">
                        <span>Files</span>
                        <span class="rounded-full bg-[#1a2536] border border-[#3b4b66] px-2.5 py-0.5 text-[10px] text-sky-300 font-mono font-bold" x-text="files.length"></span>
                    </h2>
                    
                    <!-- GRID VIEW WITH IMAGE & VIDEO PREVIEWS -->
                    <div x-show="viewMode === 'grid'" 
                         class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                        <template x-for="file in files" :key="file.path">
                            <div :class="activeMenu === file.path ? 'z-40 ring-1 ring-sky-400' : 'z-10'"
                                 class="group relative flex flex-col rounded-2xl border border-[#2d3a50] bg-[#131a26] hover:border-sky-400 hover:bg-[#182333] transition-all select-none shadow-md">
                                
                                <!-- Thumbnail / Media Preview / Icon Area -->
                                <div class="flex h-36 w-full items-center justify-center bg-[#0e1420] border-b border-[#2d3a50] relative rounded-t-2xl">
                                    
                                    <!-- Inner Media / Thumbnail wrapper with overflow-hidden for rounded top corners -->
                                    <div class="absolute inset-0 overflow-hidden rounded-t-2xl flex items-center justify-center">
                                        <!-- Image Thumbnail Preview -->
                                        <template x-if="file.category === 'image'">
                                            <div class="h-full w-full cursor-pointer relative" @click="openMediaModal(file)">
                                                <img :src="file.preview_url" 
                                                     :alt="file.name" 
                                                     loading="lazy" 
                                                     class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-200">
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                                    <span class="rounded-lg bg-sky-500 px-2.5 py-1 text-xs font-bold text-slate-950 shadow-md">View Picture</span>
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Video Thumbnail Preview with Play Overlay -->
                                        <template x-if="file.category === 'video'">
                                            <div class="h-full w-full cursor-pointer relative bg-slate-950 flex items-center justify-center" @click="openMediaModal(file)">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-500/30 text-purple-300 border border-purple-400 group-hover:scale-110 group-hover:bg-purple-500 group-hover:text-black transition-all shadow-lg">
                                                    <svg class="h-6 w-6 fill-current ml-0.5" viewBox="0 0 24 24">
                                                        <path d="M8 5v14l11-7z"/>
                                                    </svg>
                                                </div>
                                                <div class="absolute bottom-2 right-2 rounded bg-black/90 border border-purple-500/40 px-2 py-0.5 text-[10px] font-mono font-bold text-purple-300">
                                                    VIDEO
                                                </div>
                                            </div>
                                        </template>

                                        <!-- Non-Media File Icons -->
                                        <template x-if="file.category !== 'image' && file.category !== 'video'">
                                            <div class="flex items-center justify-center">
                                                <template x-if="file.category === 'audio'">
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.category === 'pdf'">
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.category === 'code'">
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-500/20 text-teal-300 border border-teal-500/40">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.category === 'archive'">
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                                <template x-if="file.category !== 'image' && file.category !== 'video' && file.category !== 'audio' && file.category !== 'pdf' && file.category !== 'code' && file.category !== 'archive'">
                                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-500/20 text-slate-200 border border-slate-400/40">
                                                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Shared Pill Overlay (Outside inner media wrapper to avoid clipping) -->
                                    <div x-show="file.share_token" class="absolute top-2.5 left-2.5 z-20 flex items-center gap-1 rounded-full bg-emerald-500/25 border border-emerald-400/60 px-2.5 py-0.5 text-[10px] font-bold text-emerald-300 backdrop-blur-sm shadow-sm pointer-events-none">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                        <span>Shared</span>
                                    </div>

                                    <!-- 3-dots Menu Button & Dropdown (Outside inner media wrapper so dropdown can float over footer) -->
                                    <div class="absolute top-2.5 right-2.5 z-30" @click.stop>
                                        <button @click="toggleMenu(file.path)" 
                                                class="rounded-xl border border-[#3b4b66] bg-[#0e1420]/90 backdrop-blur-md p-2 text-slate-200 hover:border-sky-400 hover:text-sky-300 hover:bg-[#1a2536] transition-colors shadow-md"
                                                title="File options">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                            </svg>
                                        </button>

                                        <!-- Context Dropdown -->
                                        <div x-show="activeMenu === file.path" 
                                             x-cloak
                                             @click.outside="activeMenu = null"
                                             class="absolute right-0 top-full z-50 mt-1.5 w-48 rounded-2xl border border-[#3b4b66] bg-[#161d2a] p-2 shadow-2xl">
                                            <template x-if="file.preview_url">
                                                <button @click="openMediaModal(file); activeMenu = null" 
                                                        class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-sky-300 transition-colors">
                                                    <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    <span>Preview</span>
                                                </button>
                                            </template>
                                            <a :href="file.download_url" 
                                               class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-sky-300 transition-colors">
                                                <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                </svg>
                                                <span>Download</span>
                                            </a>
                                            <button @click="shareItem(file); activeMenu = null" 
                                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-white hover:bg-[#263750] hover:text-emerald-300 transition-colors">
                                                <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span x-text="file.share_token ? 'Copy Share Link' : 'Share File'"></span>
                                            </button>

                                            <!-- Unshare Option -->
                                            <button x-show="file.share_token" 
                                                    @click="unshareItem(file); activeMenu = null" 
                                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-amber-300 hover:bg-amber-500/20 transition-colors">
                                                <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                                <span>Unshare File</span>
                                            </button>

                                            <div class="my-1.5 border-t border-[#2d394b]"></div>
                                            <button @click="renameItem(file); activeMenu = null" 
                                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-sky-300 hover:bg-[#263750] transition-colors">
                                                <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                <span>Rename</span>
                                            </button>
                                            <button @click="confirmDelete(file); activeMenu = null" 
                                                    class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-500/20 transition-colors">
                                                <svg class="h-4 w-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                <span>Delete</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- File Details Footer -->
                                <div class="p-3.5">
                                    <div class="truncate text-xs font-bold text-white group-hover:text-sky-300 transition-colors" 
                                         :title="file.name" 
                                         @click="file.preview_url ? openMediaModal(file) : null"
                                         :class="file.preview_url ? 'cursor-pointer' : ''"
                                         x-text="file.name"></div>
                                    <div class="mt-1.5 flex items-center justify-between text-[11px] font-medium text-slate-300">
                                        <span x-text="file.human_size"></span>
                                        <span x-text="file.modified_human"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- LIST VIEW -->
                    <div x-show="viewMode === 'list'" 
                         class="overflow-hidden rounded-2xl border border-[#2d3a50] bg-[#131a26] shadow-xl">
                        <table class="w-full text-left text-xs text-[#f8fafc]">
                            <thead class="border-b border-[#3b4b66] bg-[#0e1420] uppercase tracking-wider text-slate-200">
                                <tr>
                                    <th class="py-4 pl-5 pr-3 font-extrabold">Name</th>
                                    <th class="px-4 py-4 font-extrabold">Size</th>
                                    <th class="px-4 py-4 font-extrabold">Modified</th>
                                    <th class="py-4 pl-3 pr-5 text-right font-extrabold">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#2d3a50]">
                                <template x-for="file in files" :key="file.path">
                                    <tr class="hover:bg-[#182333] transition-colors group">
                                        <td class="py-3 pl-5 pr-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#0b0e14] border border-[#3b4b66] font-bold text-[11px]">
                                                    <template x-if="file.category === 'image'"><span class="text-sky-400">IMG</span></template>
                                                    <template x-if="file.category === 'video'"><span class="text-purple-400">VID</span></template>
                                                    <template x-if="file.category === 'audio'"><span class="text-emerald-400">AUD</span></template>
                                                    <template x-if="file.category === 'pdf'"><span class="text-rose-400">PDF</span></template>
                                                    <template x-if="file.category === 'code'"><span class="text-teal-400">DEV</span></template>
                                                    <template x-if="file.category === 'archive'"><span class="text-amber-400">ZIP</span></template>
                                                    <template x-if="file.category !== 'image' && file.category !== 'video' && file.category !== 'audio' && file.category !== 'pdf' && file.category !== 'code' && file.category !== 'archive'"><span class="text-slate-300">DOC</span></template>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <div class="truncate font-bold text-white group-hover:text-sky-300 transition-colors" 
                                                         :class="file.preview_url ? 'cursor-pointer' : ''"
                                                         @click="file.preview_url ? openMediaModal(file) : null"
                                                         :title="file.name" 
                                                         x-text="file.name"></div>
                                                    <span x-show="file.share_token" class="text-[10px] text-emerald-400 font-mono font-bold">Shared Link Active</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-200 font-semibold" x-text="file.human_size"></td>
                                        <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-300 font-medium" x-text="file.modified_human"></td>
                                        <td class="whitespace-nowrap py-3 pl-3 pr-5 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <template x-if="file.preview_url">
                                                    <button @click="openMediaModal(file)" 
                                                            class="rounded-xl border border-sky-500/40 bg-[#162338] px-3 py-1.5 text-xs font-semibold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-colors shadow-sm flex items-center gap-1.5" 
                                                            title="Preview">
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                        <span>Preview</span>
                                                    </button>
                                                </template>
                                                <a :href="file.download_url" 
                                                   class="rounded-xl border border-[#3b4b66] bg-[#1a2536] px-3 py-1.5 text-xs font-semibold text-slate-200 hover:bg-[#263750] hover:text-white hover:border-slate-400 transition-colors shadow-sm flex items-center gap-1.5" 
                                                   title="Download">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                    <span>Download</span>
                                                </a>
                                                <button @click="shareItem(file)" 
                                                        class="rounded-xl border border-emerald-500/40 bg-[#142825] px-3 py-1.5 text-xs font-semibold text-emerald-300 hover:bg-emerald-500 hover:text-slate-950 hover:border-emerald-400 transition-colors shadow-sm flex items-center gap-1.5" 
                                                        :title="file.share_token ? 'Copy Share Link' : 'Share File'">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                    <span x-text="file.share_token ? 'Copy Link' : 'Share'"></span>
                                                </button>
                                                <button @click="renameItem(file)" 
                                                        class="rounded-xl border border-sky-500/40 bg-[#162338] px-3 py-1.5 text-xs font-semibold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-colors shadow-sm flex items-center gap-1.5"
                                                        title="Rename">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    <span>Rename</span>
                                                </button>
                                                <button @click="confirmDelete(file)" 
                                                        class="rounded-xl border border-rose-500/40 bg-rose-950/40 px-3 py-1.5 text-xs font-semibold text-rose-300 hover:bg-rose-600 hover:text-white hover:border-rose-500 transition-colors shadow-sm flex items-center gap-1.5" 
                                                        title="Delete">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    <span>Delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================= -->
            <!-- SECTION 2: DEDICATED SHARED LINKS VIEW -->
            <!-- ============================================================= -->
            <div x-show="viewSection === 'shared'" x-cloak>
                <div x-show="filteredSharedItems.length === 0" class="mx-auto max-w-lg rounded-3xl border border-[#3b4b66] bg-[#131822] p-10 text-center shadow-2xl mt-12">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-inner">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-white">No active shared links</h3>
                    <p class="mt-2 text-xs text-slate-300 leading-relaxed">
                        Files and folders you share publicly will appear here with one-click link copying and instant unshare capabilities.
                    </p>
                    <button @click="switchSection('drive')" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-sky-400 transition-colors shadow-md">
                        Browse My Drive
                    </button>
                </div>

                <div x-show="filteredSharedItems.length > 0" class="overflow-hidden rounded-2xl border border-[#2d3a50] bg-[#131a26] shadow-xl">
                    <table class="w-full text-left text-xs text-[#f8fafc]">
                        <thead class="border-b border-[#3b4b66] bg-[#0e1420] uppercase tracking-wider text-slate-200">
                            <tr>
                                <th class="py-4 pl-5 pr-3 font-extrabold">Shared Item</th>
                                <th class="px-4 py-4 font-extrabold">Type</th>
                                <th class="px-4 py-4 font-extrabold">Size</th>
                                <th class="px-4 py-4 font-extrabold">Downloads</th>
                                <th class="px-4 py-4 font-extrabold">Created</th>
                                <th class="py-4 pl-3 pr-5 text-right font-extrabold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2d3a50]">
                            <template x-for="item in filteredSharedItems" :key="item.id">
                                <tr class="hover:bg-[#182333] transition-colors group">
                                    <td class="py-3 pl-5 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#0b0e14] border border-[#3b4b66] font-bold text-[10px]">
                                                <template x-if="item.is_dir">
                                                    <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-4.5a3 3 0 00-2.12.88L6.88 8.38A3 3 0 014.76 9H4.5A3 3 0 001.5 12v6a3 3 0 003 3h15z"/>
                                                    </svg>
                                                </template>
                                                <template x-if="!item.is_dir && item.category === 'image'"><span class="text-sky-400">IMG</span></template>
                                                <template x-if="!item.is_dir && item.category === 'video'"><span class="text-purple-400">VID</span></template>
                                                <template x-if="!item.is_dir && item.category !== 'image' && item.category !== 'video'"><span class="text-slate-300">FILE</span></template>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="truncate font-bold text-white group-hover:text-sky-300 transition-colors" x-text="item.name"></div>
                                                <a :href="item.share_url" target="_blank" class="text-[11px] text-sky-400 font-mono hover:underline truncate block" x-text="item.share_url"></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px]">
                                        <span :class="item.is_dir ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-sky-500/20 text-sky-300 border border-sky-500/40'"
                                              class="rounded-lg px-2.5 py-1 font-bold"
                                              x-text="item.is_dir ? 'Folder' : 'File'"></span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-200 font-semibold" x-text="item.human_size"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-emerald-300 font-mono font-bold" x-text="item.downloads_count + ' dl'"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-300 font-medium" x-text="item.created_human"></td>
                                    <td class="whitespace-nowrap py-3 pl-3 pr-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="copyDirectLink(item.share_url)" 
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/50 bg-[#162338] px-3 py-1.5 text-xs font-bold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-colors shadow-sm">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Copy Link</span>
                                            </button>
                                            <button @click="unshareItem(item)" 
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-950/40 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950 hover:border-amber-400 transition-colors shadow-sm">
                                                <span>Unshare</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================================= -->
    <!-- MEDIA PREVIEW LIGHTBOX MODAL -->
    <!-- ========================================================================= -->
    <div x-show="showPreviewModal" 
         x-cloak
         @keydown.escape.window="closeMediaModal()"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-md p-4"
         @click.self="closeMediaModal()">
        <div class="relative w-full max-w-5xl rounded-3xl border border-[#3b4b66] bg-[#161d2a] shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-[#3b4b66] px-6 py-4 bg-[#0e1420]">
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <span class="rounded-lg bg-sky-500/20 px-2.5 py-1 text-xs font-bold uppercase tracking-wider text-sky-300 border border-sky-500/40 font-mono"
                          x-text="previewFile ? previewFile.category : 'PREVIEW'"></span>
                    <h3 class="text-sm font-bold text-white truncate" x-text="previewFile ? previewFile.name : ''"></h3>
                    <span class="rounded-full bg-[#1e293b] border border-[#3b4b66] px-2.5 py-0.5 text-[10px] text-slate-300 font-mono font-bold" x-text="previewFile ? previewFile.human_size : ''"></span>
                </div>
                <div class="flex items-center gap-2">
                    <template x-if="previewFile">
                        <a :href="previewFile.download_url" 
                           class="rounded-xl border border-sky-500/50 bg-[#162338] px-3.5 py-1.5 text-xs font-bold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-colors flex items-center gap-1.5 shadow-sm">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span>Download</span>
                        </a>
                    </template>
                    <button @click="closeMediaModal()" 
                            class="rounded-xl border border-[#3b4b66] bg-[#1a2536] p-2 text-slate-200 hover:bg-rose-600 hover:border-rose-500 hover:text-white transition-colors shadow-sm"
                            title="Close Preview">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Media Content) -->
            <div class="flex flex-1 items-center justify-center p-6 bg-black/60 overflow-hidden min-h-[350px]">
                <template x-if="previewFile && previewFile.category === 'image'">
                    <img :src="previewFile.preview_url" 
                         :alt="previewFile.name" 
                         class="max-h-[70vh] w-auto max-w-full object-contain rounded-xl shadow-2xl">
                </template>
                <template x-if="previewFile && previewFile.category === 'video'">
                    <div class="w-full flex items-center justify-center">
                        <video controls 
                               autoplay 
                               playsinline 
                               class="max-h-[70vh] w-full rounded-xl bg-black shadow-2xl">
                            <source :src="previewFile.preview_url" :type="previewFile.mime_type || 'video/mp4'">
                            Your browser does not support HTML5 video preview.
                        </video>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- NEW FOLDER MODAL -->
    <!-- ========================================================================= -->
    <div x-show="showNewFolderModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.outside="showNewFolderModal = false" 
             class="w-full max-w-md rounded-3xl border border-[#3b4b66] bg-[#161d2a] p-6 shadow-2xl">
            <h3 class="text-base font-bold text-white">Create New Folder</h3>
            <p class="mt-1 text-xs text-slate-300">Create inside <span class="font-mono text-sky-400 font-bold" x-text="'/' + currentPath"></span></p>

            <form @submit.prevent="createFolder()">
                <input type="text" 
                       x-model="newFolderName" 
                       x-ref="newFolderInput" 
                       placeholder="Folder name" 
                       class="mt-4 w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-xs text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 shadow-sm"
                       required>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button type="button" 
                            @click="showNewFolderModal = false" 
                            class="rounded-xl border border-[#3b4b66] bg-[#1e293b] px-4 py-2 text-xs font-bold text-slate-200 hover:bg-[#2d3b52] hover:text-white hover:border-slate-400 transition-colors shadow-sm">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="rounded-xl bg-sky-500 px-5 py-2 text-xs font-extrabold text-slate-950 hover:bg-sky-400 transition-colors shadow-md shadow-sky-500/20">
                        Create Folder
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- RENAME MODAL -->
    <!-- ========================================================================= -->
    <div x-show="showRenameModal" 
         x-cloak
         @keydown.escape.window="showRenameModal = false"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.outside="showRenameModal = false" 
             class="w-full max-w-md rounded-3xl border border-[#3b4b66] bg-[#161d2a] p-6 shadow-2xl">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-sky-500/20 text-sky-400 border border-sky-500/40">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Rename</h3>
                    <p class="text-xs text-slate-300" x-text="renameTarget ? renameTarget.name : ''"></p>
                </div>
            </div>

            <form @submit.prevent="executeRename()">
                <input type="text" 
                       x-model="renameName" 
                       x-ref="renameInput" 
                       placeholder="New name" 
                       class="mt-4 w-full rounded-xl border border-[#3b4b66] bg-[#0e1420] px-4 py-2.5 text-xs text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none focus:ring-1 focus:ring-sky-400 shadow-sm"
                       required>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button type="button" 
                            @click="showRenameModal = false" 
                            class="rounded-xl border border-[#3b4b66] bg-[#1e293b] px-4 py-2 text-xs font-bold text-slate-200 hover:bg-[#2d3b52] hover:text-white hover:border-slate-400 transition-colors shadow-sm">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="rounded-xl bg-sky-500 px-5 py-2 text-xs font-extrabold text-slate-950 hover:bg-sky-400 transition-colors shadow-md shadow-sky-500/20">
                        Rename
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DELETE CONFIRMATION MODAL -->
    <!-- ========================================================================= -->
    <div x-show="showDeleteModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
        <div @click.outside="showDeleteModal = false" 
             class="w-full max-w-md rounded-3xl border border-rose-500/50 bg-[#161d2a] p-6 shadow-2xl">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-rose-500/20 text-rose-400 border border-rose-500/40">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-white">Delete Item</h3>
                    <p class="text-xs text-rose-300">This action cannot be undone.</p>
                </div>
            </div>

            <p class="mt-4 text-xs text-slate-200 leading-relaxed">
                Are you sure you want to permanently delete <span class="font-bold text-white" x-text="targetDeleteItem ? targetDeleteItem.name : ''"></span>?
            </p>

            <div class="mt-6 flex justify-end gap-2.5">
                <button type="button" 
                        @click="showDeleteModal = false" 
                        class="rounded-xl border border-[#3b4b66] bg-[#1e293b] px-4 py-2 text-xs font-bold text-slate-200 hover:bg-[#2d3b52] hover:text-white hover:border-slate-400 transition-colors shadow-sm">
                    Cancel
                </button>
                <button type="button" 
                        @click="executeDelete()" 
                        class="rounded-xl bg-rose-600 px-5 py-2 text-xs font-bold text-white hover:bg-rose-500 transition-colors shadow-md shadow-rose-600/30">
                    Delete Permanently
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FLOATING TOAST NOTIFICATIONS -->
    <!-- ========================================================================= -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2.5 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="transform translate-y-4 opacity-0"
                 x-transition:enter-end="transform translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="transform translate-y-0 opacity-100"
                 x-transition:leave-end="transform translate-y-4 opacity-0"
                 class="pointer-events-auto flex w-96 items-start gap-3 rounded-2xl border border-[#3b4b66] bg-[#161d2a] p-4 shadow-2xl backdrop-blur-md">
                
                <div class="shrink-0">
                    <template x-if="toast.type === 'success'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-500/20 text-rose-400 border border-rose-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </div>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-500/20 text-sky-400 border border-sky-500/40">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </template>
                </div>

                <div class="flex-1 overflow-hidden">
                    <div class="text-xs font-bold text-white" x-text="toast.title"></div>
                    <div class="mt-0.5 text-xs text-slate-300 break-all leading-relaxed" x-text="toast.message"></div>
                </div>

                <button @click="dismissToast(toast.id)" class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-[#253043]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

</div>
@endsection

@push('scripts')
<script>
function driveApp() {
    return {
        viewSection: 'drive', // 'drive' or 'shared'
        items: [],
        sharedItems: [],
        currentPath: '',
        parentPath: '',
        breadcrumbs: [{ name: 'My Drive', path: '' }],
        viewMode: localStorage.getItem('jhn_drive_view') || 'grid',
        searchQuery: '',
        isLoading: false,
        isUploading: false,
        uploadProgress: 0,
        isDragging: false,
        activeMenu: null,
        stats: {
            percent_used: 0,
            used_human: '0 B',
            free_human: '20 GB',
            quota_human: '20 GB'
        },
        showNewFolderModal: false,
        newFolderName: '',
        showDeleteModal: false,
        targetDeleteItem: null,
        showPreviewModal: false,
        previewFile: null,
        showRenameModal: false,
        renameTarget: null,
        renameName: '',
        toasts: [],

        init() {
            this.$watch('viewMode', val => localStorage.setItem('jhn_drive_view', val));
            this.loadFiles('');
            this.loadStats();
            this.loadSharedLinks();
        },

        get folders() {
            return this.filteredItems.filter(i => i.is_dir);
        },

        get files() {
            return this.filteredItems.filter(i => !i.is_dir);
        },

        get filteredItems() {
            if (!this.searchQuery) return this.items;
            const query = this.searchQuery.toLowerCase();
            return this.items.filter(i => i.name.toLowerCase().includes(query));
        },

        get filteredSharedItems() {
            if (!this.searchQuery) return this.sharedItems;
            const query = this.searchQuery.toLowerCase();
            return this.sharedItems.filter(i => i.name.toLowerCase().includes(query));
        },

        switchSection(section) {
            this.viewSection = section;
            this.searchQuery = '';
            if (section === 'shared') {
                this.loadSharedLinks();
            } else {
                this.loadFiles(this.currentPath);
            }
        },

        async loadFiles(path = '') {
            this.isLoading = true;
            try {
                const res = await fetch(`/api/files?path=${encodeURIComponent(path)}`);
                const data = await res.json();
                if (data.success) {
                    this.items = data.items;
                    this.currentPath = data.current_path;
                    this.parentPath = data.parent_path;
                    this.breadcrumbs = data.breadcrumbs;
                } else {
                    this.showToast('Error', data.message || 'Failed to load directory.', 'error');
                }
            } catch (err) {
                this.showToast('Network Error', err.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async loadSharedLinks() {
            try {
                const res = await fetch('/api/shared');
                const data = await res.json();
                if (data.success) {
                    this.sharedItems = data.items;
                }
            } catch (e) {
                console.error('Failed to load shared links', e);
            }
        },

        async loadStats() {
            try {
                const res = await fetch('/api/stats');
                const data = await res.json();
                if (data.success) {
                    this.stats = data;
                }
            } catch (e) {
                console.error('Failed to load drive stats', e);
            }
        },

        navigateTo(path) {
            this.viewSection = 'drive';
            this.loadFiles(path);
        },

        openMediaModal(file) {
            if (!file.preview_url) return;
            this.previewFile = file;
            this.showPreviewModal = true;
        },

        closeMediaModal() {
            this.showPreviewModal = false;
            this.previewFile = null;
        },

        toggleMenu(path) {
            this.activeMenu = this.activeMenu === path ? null : path;
        },

        openNewFolderDialog() {
            this.newFolderName = '';
            this.showNewFolderModal = true;
            this.$nextTick(() => {
                this.$refs.newFolderInput && this.$refs.newFolderInput.focus();
            });
        },

        async createFolder() {
            if (!this.newFolderName.trim()) return;
            try {
                const res = await fetch('/api/mkdir', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        path: this.currentPath,
                        name: this.newFolderName.trim()
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showNewFolderModal = false;
                    this.showToast('Success', data.message, 'success');
                    this.loadFiles(this.currentPath);
                } else {
                    this.showToast('Error', data.message || 'Could not create folder.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        confirmDelete(item) {
            this.targetDeleteItem = item;
            this.showDeleteModal = true;
        },

        async executeDelete() {
            if (!this.targetDeleteItem) return;
            try {
                const res = await fetch('/api/delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: this.targetDeleteItem.path })
                });
                const data = await res.json();
                if (data.success) {
                    this.showDeleteModal = false;
                    this.showToast('Deleted', data.message, 'success');
                    this.loadFiles(this.currentPath);
                    this.loadStats();
                    this.loadSharedLinks();
                } else {
                    this.showToast('Error', data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        renameItem(item) {
            this.renameTarget = item;
            this.renameName = item.name;
            this.showRenameModal = true;
            this.$nextTick(() => {
                if (this.$refs.renameInput) {
                    this.$refs.renameInput.focus();
                    this.$refs.renameInput.select();
                }
            });
        },

        async executeRename() {
            if (!this.renameTarget || !this.renameName.trim()) return;
            try {
                const res = await fetch('/api/rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: this.renameTarget.path, new_name: this.renameName.trim() })
                });
                const data = await res.json();
                if (data.success) {
                    this.showRenameModal = false;
                    this.renameTarget = null;
                    this.renameName = '';
                    this.showToast('Renamed', data.message, 'success');
                    this.loadFiles(this.currentPath);
                    this.loadSharedLinks();
                } else {
                    this.showToast('Error', data.message || 'Rename failed.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        async shareItem(item) {
            try {
                const res = await fetch('/api/share', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: item.path })
                });
                const data = await res.json();
                if (data.success) {
                    item.share_token = data.token;
                    item.share_url = data.share_url;
                    await this.copyDirectLink(data.share_url);
                    this.loadSharedLinks();
                } else {
                    this.showToast('Share Failed', data.message || 'Unable to generate share link.', 'error');
                }
            } catch (err) {
                this.showToast('Error', err.message, 'error');
            }
        },

        async unshareItem(item) {
            try {
                const res = await fetch('/api/unshare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ 
                        path: item.path,
                        token: item.token || item.share_token 
                    })
                });
                const data = await res.json();
                if (data.success) {
                    item.share_token = null;
                    item.share_url = null;
                    this.showToast('Unshared', 'Public link revoked successfully.', 'info');
                    this.loadSharedLinks();
                    if (this.viewSection === 'drive') {
                        this.loadFiles(this.currentPath);
                    }
                } else {
                    this.showToast('Unshare Failed', data.message, 'error');
                }
            } catch (err) {
                this.showToast('Error', err.message, 'error');
            }
        },

        async copyDirectLink(url) {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
                this.showToast('Link Copied to Clipboard!', url, 'success');
            } catch (e) {
                this.showToast('Copied', url, 'info');
            }
        },

        handleFileInputChange(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                this.uploadFiles(files);
            }
            e.target.value = '';
        },

        onDragLeave(e) {
            if (e.clientX <= 0 || e.clientY <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
                this.isDragging = false;
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                this.uploadFiles(files);
            }
        },

        async uploadFiles(fileList) {
            this.isUploading = true;
            this.uploadProgress = 10;

            const formData = new FormData();
            formData.append('path', this.currentPath);

            for (let i = 0; i < fileList.length; i++) {
                formData.append('files[]', fileList[i]);
            }

            try {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '/api/upload', true);
                
                const csrf = document.querySelector('meta[name="csrf-token"]');
                if (csrf) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf.getAttribute('content'));
                }

                xhr.upload.onprogress = (e) => {
                    if (e.lengthComputable) {
                        this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                    }
                };

                xhr.onload = () => {
                    this.isUploading = false;
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                            this.showToast('Upload Complete', data.message, 'success');
                            this.loadFiles(this.currentPath);
                            this.loadStats();
                        } else {
                            this.showToast('Upload Error', data.message || 'Upload failed.', 'error');
                        }
                    } catch (e) {
                        this.showToast('Upload Error', `Server error (${xhr.status})`, 'error');
                    }
                };

                xhr.onerror = () => {
                    this.isUploading = false;
                    this.showToast('Upload Failed', 'Network connection interrupted.', 'error');
                };

                xhr.send(formData);
            } catch (err) {
                this.isUploading = false;
                this.showToast('Error', err.message, 'error');
            }
        },

        showToast(title, message, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, title, message, type });
            setTimeout(() => {
                this.dismissToast(id);
            }, 4000);
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }
}
</script>
@endpush
