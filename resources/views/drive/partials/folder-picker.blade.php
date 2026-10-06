<div x-show="showTransferModal"
     x-cloak
     class="mobile-sheet fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="transfer-dialog-title">
    <div @click.outside="closeTransferDialog()" class="jhn-surface flex max-h-[85dvh] w-full max-w-xl flex-col overflow-hidden rounded-xl">
        <div class="flex items-start justify-between border-b border-slate-800 px-5 py-4">
            <div class="min-w-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-300"
                   x-text="transferOperation === 'move' ? 'Move selection' : 'Copy selection'"></p>
                <h2 id="transfer-dialog-title" class="mt-1 text-base font-semibold text-white"
                    x-text="transferOperation === 'move' ? 'Choose a destination' : 'Choose where to copy'"></h2>
                <p class="mt-1 truncate text-xs text-slate-500"
                   x-text="transferItems.length === 1 ? transferItems[0].name : transferItems.length + ' selected items'"></p>
            </div>
            <button type="button" class="jhn-icon-button" @click="closeTransferDialog()" :disabled="isTransferring" aria-label="Close folder picker">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex min-h-11 items-center gap-1 overflow-x-auto border-b border-slate-800 px-4 py-2 text-xs" aria-label="Destination breadcrumbs">
            <template x-for="(crumb, index) in pickerBreadcrumbs" :key="crumb.path">
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button"
                            @click="loadPickerFolder(crumb.path)"
                            class="rounded-md px-2 py-1.5"
                            :class="index === pickerBreadcrumbs.length - 1 ? 'bg-cyan-400/10 text-cyan-300' : 'text-slate-400 hover:text-white'"
                            x-text="crumb.name"></button>
                    <span x-show="index < pickerBreadcrumbs.length - 1" class="text-slate-700">/</span>
                </div>
            </template>
        </nav>

        <div class="min-h-56 flex-1 overflow-y-auto p-3">
            <div x-show="isPickerLoading" class="py-16 text-center text-xs text-slate-500">Loading folders?</div>

            <button x-show="!isPickerLoading && pickerPath"
                    type="button"
                    @click="loadPickerFolder(pickerParentPath)"
                    class="jhn-nav-item mb-1">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-width="2" d="M15 18l-6-6 6-6"/>
                </svg>
                Parent folder
            </button>

            <template x-for="folder in pickerFolders" :key="folder.path">
                <button type="button"
                        @click="loadPickerFolder(folder.path)"
                        :disabled="transferDestinationInvalid(folder.path)"
                        class="jhn-nav-item disabled:cursor-not-allowed disabled:opacity-35">
                    <svg class="h-4 w-4 text-amber-300" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                    <span class="truncate" x-text="folder.name"></span>
                    <span x-show="transferDestinationInvalid(folder.path)" class="ml-auto text-[10px] text-slate-600">Selected folder</span>
                </button>
            </template>

            <p x-show="!isPickerLoading && pickerFolders.length === 0" class="py-12 text-center text-xs text-slate-500">
                This folder has no subfolders.
            </p>
        </div>

        <div class="border-t border-slate-800 px-5 py-4">
            <p class="truncate text-[11px] text-slate-500">
                Destination:
                <span class="font-medium text-slate-300" x-text="pickerPath ? 'My Drive / ' + pickerPath : 'My Drive'"></span>
            </p>
            <p x-show="transferDestinationInvalid(pickerPath)" class="mt-2 text-xs text-rose-300">
                A folder cannot be moved or copied into itself.
            </p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="closeTransferDialog()" :disabled="isTransferring"
                        class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 disabled:opacity-50">
                    Cancel
                </button>
                <button type="button"
                        @click="executeTransfer()"
                        :disabled="isPickerLoading || isTransferring || transferDestinationInvalid(pickerPath)"
                        class="jhn-button-primary disabled:cursor-not-allowed disabled:opacity-50">
                    <span x-text="isTransferring ? 'Working?' : (transferOperation === 'move' ? 'Move here' : 'Copy here')"></span>
                </button>
            </div>
        </div>
    </div>
</div>
