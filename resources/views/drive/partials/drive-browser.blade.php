<section x-show="viewSection === 'drive'" x-cloak aria-label="My Drive">
    <div class="mb-4 flex min-h-11 flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-[#101720] px-3 py-2.5">
        <div class="flex min-w-0 items-center gap-3">
            <label class="flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-700 bg-slate-900">
                <span class="sr-only">Select all visible items</span>
                <input type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-400"
                       :checked="allVisibleSelected" @change="toggleSelectAll()">
            </label>
            <div class="min-w-0">
                <p class="truncate text-xs font-semibold text-slate-200"
                   x-text="selectionCount > 0 ? selectionCount + ' selected' : filteredItems.length + ' items'"></p>
                <p class="hidden text-[11px] text-slate-500 sm:block"
                   x-text="selectionCount > 0 ? 'Shift-click a checkbox to select a range' : 'Select items for bulk actions'"></p>
            </div>
        </div>
        <div x-show="selectionCount === 0" class="flex flex-wrap items-center justify-end gap-2">
            <label>
                <span class="sr-only">Filter My Drive by type</span>
                <select x-model="driveFilter" @change="clearSelection()"
                        class="h-9 rounded-lg border border-slate-700 bg-[#0b1017] px-2.5 text-xs text-slate-200 outline-none focus:border-cyan-400">
                    <option value="all">All types</option>
                    <option value="folder">Folders</option>
                    <option value="image">Images</option>
                    <option value="video">Videos</option>
                    <option value="audio">Audio</option>
                    <option value="document">Documents</option>
                    <option value="archive">Archives</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Sort My Drive</span>
                <select x-model="driveSort"
                        class="h-9 rounded-lg border border-slate-700 bg-[#0b1017] px-2.5 text-xs text-slate-200 outline-none focus:border-cyan-400">
                    <option value="name">Name</option>
                    <option value="modified">Modified</option>
                    <option value="size">Size</option>
                    <option value="type">Type</option>
                </select>
            </label>
            <button type="button"
                    @click="driveDirection = driveDirection === 'asc' ? 'desc' : 'asc'"
                    class="h-9 rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-300 hover:border-cyan-400/50 hover:text-cyan-300"
                    :aria-label="driveDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
                    x-text="driveDirection === 'asc' ? 'Ascending' : 'Descending'"></button>
            <button x-show="driveFilter !== 'all' || driveSort !== 'name' || driveDirection !== 'asc'"
                    type="button"
                    @click="resetDriveFilters()"
                    class="h-9 rounded-lg px-2 text-xs font-medium text-slate-500 hover:text-white">
                Reset
            </button>
        </div>
        <div x-show="selectionCount > 0" class="flex flex-wrap items-center justify-end gap-2">
            <button type="button"
                    class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-200 hover:border-cyan-400/50 hover:text-cyan-300 disabled:opacity-50"
                    :disabled="isBulkDownloading" @click="downloadSelected()">
                <svg class="h-4 w-4" :class="isBulkDownloading && 'animate-pulse'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>
                </svg>
                <span x-text="isBulkDownloading ? 'Packaging?' : 'Download'"></span>
            </button>
            <button type="button"
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-200 hover:border-cyan-400/50 hover:text-cyan-300"
                    @click="openTransferDialog(selectedItems, 'move')">Move</button>
            <button type="button"
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-200 hover:border-cyan-400/50 hover:text-cyan-300"
                    @click="openTransferDialog(selectedItems, 'copy')">Copy</button>
            <button x-show="selectionCount === 1" type="button"
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-200 hover:border-emerald-500/50 hover:text-emerald-300"
                    @click="shareItem(selectedItems[0])">Share</button>
            <button x-show="selectionCount === 1" type="button"
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-200 hover:border-slate-500 hover:text-white"
                    @click="showItemDetails(selectedItems[0])">Details</button>
            <button type="button" class="jhn-icon-button" @click="clearSelection()" aria-label="Clear selection">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div x-show="isLoading" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5" aria-label="Loading folder">
        <template x-for="index in 10" :key="index">
            <div class="jhn-surface h-44 animate-pulse rounded-xl"></div>
        </template>
    </div>

    <div x-show="!isLoading && filteredItems.length === 0"
         class="mx-auto mt-10 max-w-lg rounded-xl border border-slate-800 bg-[#101720] px-6 py-12 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-slate-800 text-slate-500">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
        </span>
        <h2 class="mt-4 text-base font-semibold text-white"
            x-text="items.length > 0 ? 'No items match this filter' : (currentPath ? 'This folder is empty' : 'Your Drive is empty')"></h2>
        <p class="mt-2 text-xs leading-relaxed text-slate-500"
           x-text="items.length > 0 ? 'Choose another file type or reset the filters.' : 'Upload files or create a folder to get started.'"></p>
        <button x-show="items.length > 0" type="button" class="mt-5 rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-200 hover:border-cyan-400/50" @click="resetDriveFilters()">Reset filters</button>
        <div x-show="items.length === 0" class="mt-5 flex flex-wrap justify-center gap-2">
            <button type="button" class="jhn-button-primary" @click="$refs.fileInput.click()">Upload files</button>
            <button type="button" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-medium text-slate-200 hover:border-slate-500"
                    @click="openNewFolderDialog()">New folder</button>
        </div>
    </div>

    <div x-show="!isLoading && filteredItems.length > 0 && viewMode === 'grid'"
         class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
        <template x-for="item in filteredItems" :key="item.path">
            <article class="group relative min-w-0 rounded-xl border bg-[#111821] transition"
                     :class="isSelected(item.path) ? 'border-cyan-400 ring-1 ring-cyan-400/40' : 'border-slate-800 hover:border-slate-600'">
                <label class="absolute left-2.5 top-2.5 z-20 flex h-8 w-8 cursor-pointer items-center justify-center rounded-lg border border-slate-700 bg-[#090e14]/90">
                    <span class="sr-only" x-text="'Select ' + item.name"></span>
                    <input type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-400"
                           :checked="isSelected(item.path)" @click.stop="toggleSelection(item, $event)">
                </label>
                <div class="absolute right-2.5 top-2.5 z-30">
                    @include('drive.partials.item-actions')
                </div>
                <button type="button" class="block w-full text-left"
                        @click="activateItem(item, $event)" @dblclick.prevent="item.is_dir && navigateTo(item.path)">
                    <span class="relative flex h-28 items-center justify-center overflow-hidden rounded-t-xl border-b border-slate-800 bg-[#0b1118] sm:h-32">
                        <template x-if="item.category === 'image'">
                            <img :src="item.preview_url" :alt="item.name" loading="lazy" class="h-full w-full object-cover">
                        </template>
                        <template x-if="item.category !== 'image'">
                            <span class="flex h-14 w-14 items-center justify-center rounded-xl"
                                  :class="item.is_dir ? 'bg-amber-400/10 text-amber-300' : 'bg-slate-800 text-slate-300'">
                                <svg x-show="item.is_dir" class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                </svg>
                                <span x-show="!item.is_dir" class="text-[10px] font-bold uppercase" x-text="item.extension || item.category"></span>
                            </span>
                        </template>
                        <span x-show="item.category === 'video'" class="absolute bottom-2 right-2 rounded bg-black/75 px-2 py-1 text-[9px] font-semibold text-violet-300">VIDEO</span>
                        <span x-show="item.share_token" class="absolute bottom-2 left-2 rounded bg-emerald-400/15 px-2 py-1 text-[9px] font-medium text-emerald-300">SHARED</span>
                    </span>
                    <span class="block p-3">
                        <span class="block truncate text-xs font-semibold text-slate-100" :title="item.name" x-text="item.name"></span>
                        <span class="mt-1.5 flex items-center justify-between gap-2 text-[10px] text-slate-500">
                            <span class="truncate" x-text="item.human_size"></span>
                            <span class="shrink-0" x-text="item.modified_human"></span>
                        </span>
                    </span>
                </button>
            </article>
        </template>
    </div>

    <div x-show="!isLoading && filteredItems.length > 0 && viewMode === 'list'"
         class="overflow-x-auto rounded-xl border border-slate-800 bg-[#111821]">
        <table class="min-w-[44rem] w-full text-left text-xs">
            <thead class="border-b border-slate-800 bg-[#0b1118] text-[10px] uppercase tracking-[0.12em] text-slate-500">
                <tr>
                    <th class="w-12 px-4 py-3"><span class="sr-only">Select</span></th>
                    <th class="px-2 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Size</th>
                    <th class="px-4 py-3 font-semibold">Modified</th>
                    <th class="w-16 px-4 py-3 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <template x-for="item in filteredItems" :key="item.path">
                    <tr :class="isSelected(item.path) ? 'bg-cyan-400/5' : 'hover:bg-slate-800/40'">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-cyan-400 focus:ring-cyan-400"
                                   :aria-label="'Select ' + item.name" :checked="isSelected(item.path)"
                                   @click.stop="toggleSelection(item, $event)">
                        </td>
                        <td class="min-w-0 px-2 py-3">
                            <button type="button" class="flex max-w-md items-center gap-3 text-left" @click="activateItem(item, $event)">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                      :class="item.is_dir ? 'bg-amber-400/10 text-amber-300' : 'bg-slate-800 text-slate-300'">
                                    <svg x-show="item.is_dir" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                    </svg>
                                    <span x-show="!item.is_dir" class="text-[9px] font-bold uppercase" x-text="item.extension || 'file'"></span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-slate-100" x-text="item.name"></span>
                                    <span x-show="item.share_token" class="mt-0.5 block text-[10px] text-emerald-300">Public link active</span>
                                </span>
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-400" x-text="item.human_size"></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-400" x-text="item.modified_human"></td>
                        <td class="px-4 py-3 text-right">@include('drive.partials.item-actions')</td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</section>
