<div x-show="showDetailsModal"
     x-cloak
     class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/75 p-4"
     @click.self="closeItemDetails()"
     role="dialog"
     aria-modal="true"
     aria-labelledby="item-details-title">
    <div class="jhn-surface w-full max-w-lg rounded-xl">
        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
            <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-[0.14em] text-cyan-300">Item details</p>
                <h2 id="item-details-title" class="mt-1 truncate text-base font-semibold text-white" x-text="detailsItem?.name || ''"></h2>
            </div>
            <button type="button"
                    x-ref="detailsClose"
                    class="jhn-icon-button"
                    @click="closeItemDetails()"
                    aria-label="Close details">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <template x-if="detailsItem">
            <div>
                <dl class="divide-y divide-slate-800 px-5">
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Type</dt>
                        <dd class="truncate text-right font-medium capitalize text-slate-200"
                            x-text="detailsItem.is_dir ? 'Folder' : (detailsItem.mime_type || detailsItem.category)"></dd>
                    </div>
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Size</dt>
                        <dd class="text-right font-medium text-slate-200" x-text="detailsItem.human_size"></dd>
                    </div>
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Location</dt>
                        <dd class="truncate text-right font-medium text-slate-200" x-text="itemLocation(detailsItem)"></dd>
                    </div>
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Modified</dt>
                        <dd class="text-right font-medium text-slate-200" x-text="formatDate(detailsItem.modified_at)"></dd>
                    </div>
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Owner</dt>
                        <dd class="truncate text-right font-medium text-slate-200">{{ auth()->user()->name }}</dd>
                    </div>
                    <div class="grid grid-cols-[7rem_minmax(0,1fr)] gap-3 py-3 text-xs">
                        <dt class="text-slate-500">Shared</dt>
                        <dd class="text-right font-medium" :class="detailsItem.share_token ? 'text-emerald-300' : 'text-slate-400'">
                            <span x-text="detailsItem.share_token ? 'Public link active' : 'Private'"></span>
                            <span x-show="detailsItem.share_token" class="ml-1 text-slate-500" x-text="'? ' + (detailsItem.downloads_count || 0) + ' downloads'"></span>
                        </dd>
                    </div>
                </dl>

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-800 p-4">
                    <button type="button"
                            class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-300 hover:border-slate-500 hover:text-white"
                            @click="renameItem(detailsItem); closeItemDetails()">
                        Rename
                    </button>
                    <button type="button"
                            class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-300 hover:border-emerald-500/50 hover:text-emerald-300"
                            @click="shareItem(detailsItem)">
                        <span x-text="detailsItem.share_token ? 'Copy share link' : 'Create share link'"></span>
                    </button>
                    <a x-show="!detailsItem.is_dir"
                       :href="detailsItem.download_url"
                       class="jhn-button-primary px-3 py-2 text-xs">
                        Download
                    </a>
                    <button x-show="detailsItem.is_dir"
                            type="button"
                            class="jhn-button-primary px-3 py-2 text-xs"
                            @click="selectedPaths = [detailsItem.path]; downloadSelected()">
                        Download ZIP
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
