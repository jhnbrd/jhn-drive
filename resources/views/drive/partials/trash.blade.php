<section x-show="viewSection === 'trash'" x-cloak aria-labelledby="trash-heading">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-rose-300">Recovery area</p>
            <h1 id="trash-heading" class="mt-1 text-xl font-semibold text-white">Trash</h1>
            <p class="mt-1 text-xs text-slate-400">Items in Trash still use storage until permanently deleted.</p>
        </div>
        <button type="button"
                x-show="trashItems.length > 0"
                @click="showEmptyTrashModal = true"
                class="rounded-lg border border-rose-500/40 px-3 py-2 text-xs font-semibold text-rose-300 transition hover:bg-rose-500/10">
            Empty Trash
        </button>
    </div>

    <div x-show="isTrashLoading" class="jhn-surface rounded-xl p-8 text-center text-sm text-slate-400">
        Loading Trash?
    </div>

    <div x-show="!isTrashLoading && filteredTrashItems.length === 0" class="jhn-surface rounded-xl px-6 py-14 text-center">
        <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 7h14m-9 4v6m4-6v6M9 7l1-3h4l1 3m-8 0l1 13h8l1-13"/>
        </svg>
        <h2 class="mt-3 text-sm font-semibold text-white" x-text="searchQuery ? 'No matching items' : 'Trash is empty'"></h2>
        <p class="mt-1 text-xs text-slate-500" x-text="searchQuery ? 'Try a different filter.' : 'Deleted items will appear here.'"></p>
    </div>

    <div x-show="!isTrashLoading && filteredTrashItems.length > 0" class="jhn-surface overflow-hidden rounded-xl">
        <div class="hidden grid-cols-[minmax(0,1fr)_minmax(10rem,0.6fr)_7rem_12rem] gap-4 border-b border-slate-800 px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500 md:grid">
            <span>Name</span><span>Original location</span><span>Size</span><span class="text-right">Actions</span>
        </div>
        <template x-for="item in filteredTrashItems" :key="item.id">
            <article class="grid gap-3 border-b border-slate-800/80 px-4 py-4 last:border-0 md:grid-cols-[minmax(0,1fr)_minmax(10rem,0.6fr)_7rem_12rem] md:items-center md:gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0" :class="item.is_dir ? 'text-amber-300' : 'text-cyan-300'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path x-show="item.is_dir" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            <path x-show="!item.is_dir" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M7 3h7l5 5v13H7V3zm7 0v6h5"/>
                        </svg>
                        <span class="truncate text-sm font-medium text-white" x-text="item.name"></span>
                    </div>
                    <p class="mt-1 truncate pl-7 text-[11px] text-slate-500" x-text="'Deleted ' + item.deleted_human"></p>
                </div>
                <p class="truncate text-xs text-slate-400" :title="item.original_path" x-text="item.original_path"></p>
                <p class="text-xs text-slate-400" x-text="item.human_size"></p>
                <div class="flex gap-2 md:justify-end">
                    <button type="button"
                            @click="restoreTrashItem(item)"
                            :disabled="isTrashMutating || !item.exists"
                            class="rounded-lg bg-cyan-400 px-3 py-2 text-xs font-semibold text-slate-950 transition hover:bg-cyan-300 disabled:cursor-not-allowed disabled:opacity-50">
                        Restore
                    </button>
                    <button type="button"
                            @click="confirmPermanentDelete(item)"
                            :disabled="isTrashMutating"
                            class="rounded-lg border border-rose-500/40 px-3 py-2 text-xs font-semibold text-rose-300 transition hover:bg-rose-500/10 disabled:opacity-50">
                        Delete forever
                    </button>
                </div>
            </article>
        </template>
    </div>

    <div x-show="showPermanentDeleteModal" x-cloak class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div @click.outside="showPermanentDeleteModal = false" class="w-full max-w-md rounded-xl border border-rose-500/50 bg-[#161d2a] p-5 sm:p-6">
            <h2 class="text-sm font-bold text-white">Permanently delete item?</h2>
            <p class="mt-3 text-xs leading-relaxed text-slate-300">
                <span class="font-semibold text-white" x-text="pendingPermanentTrashItem?.name"></span> will be removed forever. This cannot be undone.
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="showPermanentDeleteModal = false" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300">Cancel</button>
                <button type="button" @click="permanentlyDeleteTrashItem()" :disabled="isTrashMutating" class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">Delete forever</button>
            </div>
        </div>
    </div>

    <div x-show="showEmptyTrashModal" x-cloak class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
        <div @click.outside="showEmptyTrashModal = false" class="w-full max-w-md rounded-xl border border-rose-500/50 bg-[#161d2a] p-5 sm:p-6">
            <h2 class="text-sm font-bold text-white">Empty Trash?</h2>
            <p class="mt-3 text-xs leading-relaxed text-slate-300">Every item in Trash will be permanently deleted. This cannot be undone.</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" @click="showEmptyTrashModal = false" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300">Cancel</button>
                <button type="button" @click="emptyTrash()" :disabled="isTrashMutating" class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50">Empty Trash</button>
            </div>
        </div>
    </div>
</section>
