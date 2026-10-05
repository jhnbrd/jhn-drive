            <!-- ============================================================= -->
            <!-- SECTION 1: MY DRIVE (FILES & FOLDERS) -->
            <!-- ============================================================= -->
            <div x-show="viewSection === 'drive'">
                
                <!-- PROMINENT CENTERED EMPTY STATE -->
                <div x-show="!isLoading && filteredItems.length === 0" 
                     x-cloak
                     class="mx-auto mt-8 max-w-lg rounded-xl border border-[#3b4b66] bg-[#131822] p-6 text-center sm:mt-12 sm:p-10">
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
                         class="overflow-x-auto rounded-xl border border-[#2d3a50] bg-[#131a26]">
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

