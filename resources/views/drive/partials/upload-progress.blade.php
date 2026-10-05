            <!-- Upload Progress Indicator Bar -->
            <div x-show="isUploading"
                 x-cloak
                 class="mb-6 rounded-2xl border border-sky-500/40 bg-[#161d2a] p-4 shadow-xl"
                 role="status"
                 aria-live="polite">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-xs font-bold text-sky-400">
                            <svg class="h-4 w-4 shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span x-text="`Uploading ${uploadFileIndex} of ${uploadFileCount}`"></span>
                        </div>
                        <p class="mt-1 truncate text-sm font-semibold text-white" x-text="uploadCurrentFile"></p>
                        <p class="mt-1 text-[11px] font-mono text-slate-300">
                            <span x-text="formatBytes(uploadBytesSent)"></span>
                            <span class="text-slate-500"> / </span>
                            <span x-text="formatBytes(uploadBytesTotal)"></span>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="text-white font-mono text-xs font-bold" x-text="uploadProgress + '%'"></span>
                        <button type="button"
                                @click="cancelUpload()"
                                class="rounded-lg border border-rose-500/40 bg-rose-950/30 px-3 py-1.5 text-[11px] font-bold text-rose-300 transition-colors hover:bg-rose-600 hover:text-white">
                            Cancel
                        </button>
                    </div>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-[#253043]">
                    <div class="h-full bg-gradient-to-r from-sky-400 to-sky-500 transition-all duration-150" :style="'width: ' + uploadProgress + '%'"></div>
                </div>
                <p class="mt-2 text-[11px] text-slate-400" x-show="stats.upload_limits">
                    Server limit: <span x-text="stats.upload_limits?.max_file_human"></span> per file.
                </p>
            </div>

