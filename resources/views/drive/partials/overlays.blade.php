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
         class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div @click.outside="showNewFolderModal = false" 
             class="w-full max-w-md rounded-xl border border-[#3b4b66] bg-[#161d2a] p-5 sm:p-6">
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
         class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div @click.outside="showRenameModal = false" 
             class="w-full max-w-md rounded-xl border border-[#3b4b66] bg-[#161d2a] p-5 sm:p-6">
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
         class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div @click.outside="showDeleteModal = false" 
             class="w-full max-w-md rounded-xl border border-rose-500/50 bg-[#161d2a] p-5 sm:p-6">
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
                 class="pointer-events-auto flex w-[calc(100vw-1.5rem)] items-start gap-3 rounded-xl border border-[#3b4b66] bg-[#161d2a] p-4 sm:w-96">
                
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

