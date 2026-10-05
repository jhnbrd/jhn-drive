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
                    <span class="font-medium text-[#cbd5e1]">Port 8088 ??? Ready</span>
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

