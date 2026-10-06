<section x-show="viewSection === 'search'" x-cloak aria-labelledby="search-heading">
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-cyan-300">Entire Drive</p>
            <h1 id="search-heading" class="mt-1 text-xl font-semibold text-white">Search</h1>
            <p class="mt-1 text-xs text-slate-500">Find files and folders by name in every Drive folder.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                Type
                <select x-model="searchType" @change="runSearch()"
                        class="mt-1 block h-9 rounded-lg border border-slate-700 bg-[#0b1017] px-3 text-xs normal-case tracking-normal text-slate-200 outline-none focus:border-cyan-400">
                    <option value="all">All</option>
                    <option value="folder">Folders</option>
                    <option value="image">Images</option>
                    <option value="video">Videos</option>
                    <option value="audio">Audio</option>
                    <option value="document">Documents</option>
                    <option value="archive">Archives</option>
                </select>
            </label>
            <label class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                Sort
                <select x-model="searchSort" @change="runSearch()"
                        class="mt-1 block h-9 rounded-lg border border-slate-700 bg-[#0b1017] px-3 text-xs normal-case tracking-normal text-slate-200 outline-none focus:border-cyan-400">
                    <option value="name">Name</option>
                    <option value="modified">Modified</option>
                    <option value="size">Size</option>
                    <option value="type">Type</option>
                </select>
            </label>
            <button type="button"
                    @click="searchDirection = searchDirection === 'asc' ? 'desc' : 'asc'; runSearch()"
                    class="mt-4 h-9 rounded-lg border border-slate-700 px-3 text-xs font-medium text-slate-300 hover:border-cyan-400/50 hover:text-cyan-300"
                    :aria-label="searchDirection === 'asc' ? 'Sort descending' : 'Sort ascending'"
                    x-text="searchDirection === 'asc' ? 'Ascending' : 'Descending'"></button>
        </div>
    </div>

    <div x-show="isSearchLoading" class="jhn-surface rounded-xl p-10 text-center text-sm text-slate-400">
        Searching your Drive?
    </div>

    <div x-show="!isSearchLoading && !searchQuery.trim()" class="jhn-surface rounded-xl px-6 py-14 text-center">
        <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="7" stroke-width="1.5"/>
            <path stroke-linecap="round" stroke-width="1.5" d="M20 20l-4-4"/>
        </svg>
        <h2 class="mt-3 text-sm font-semibold text-white">Search your entire Drive</h2>
        <p class="mt-1 text-xs text-slate-500">Enter a file or folder name in the search box above.</p>
    </div>

    <div x-show="!isSearchLoading && searchQuery.trim() && searchResults.length === 0" class="jhn-surface rounded-xl px-6 py-14 text-center">
        <h2 class="text-sm font-semibold text-white">No files match your search</h2>
        <p class="mt-1 text-xs text-slate-500">Try another name or broaden the type filter.</p>
    </div>

    <div x-show="!isSearchLoading && searchResults.length > 0">
        <div class="mb-3 flex items-center justify-between text-xs text-slate-500">
            <span x-text="searchResults.length + (searchResults.length === 1 ? ' result' : ' results')"></span>
            <span x-show="searchTruncated" class="text-amber-300">Showing the first 500 matches</span>
        </div>

        <div class="jhn-surface overflow-hidden rounded-xl">
            <div class="hidden grid-cols-[minmax(0,1fr)_minmax(12rem,0.8fr)_7rem_9rem_10rem] gap-4 border-b border-slate-800 px-4 py-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500 lg:grid">
                <span>Name</span><span>Location</span><span>Size</span><span>Modified</span><span class="text-right">Actions</span>
            </div>
            <template x-for="item in searchResults" :key="item.path">
                <article class="grid gap-3 border-b border-slate-800/80 px-4 py-4 last:border-0 lg:grid-cols-[minmax(0,1fr)_minmax(12rem,0.8fr)_7rem_9rem_10rem] lg:items-center lg:gap-4">
                    <button type="button" @click="openSearchResult(item)" class="flex min-w-0 items-center gap-3 text-left">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                              :class="item.is_dir ? 'bg-amber-400/10 text-amber-300' : 'bg-slate-800 text-cyan-300'">
                            <svg x-show="item.is_dir" class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                            <span x-show="!item.is_dir" class="text-[9px] font-bold uppercase" x-text="item.extension || item.category"></span>
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium text-white" x-text="item.name"></span>
                            <span class="mt-0.5 block text-[10px] capitalize text-slate-500" x-text="item.category"></span>
                        </span>
                    </button>
                    <button type="button" @click="openItemLocation(item)" class="truncate text-left text-xs text-slate-400 hover:text-cyan-300" :title="item.location" x-text="item.location"></button>
                    <span class="text-xs text-slate-400" x-text="item.human_size"></span>
                    <span class="text-xs text-slate-400" x-text="item.modified_human"></span>
                    <div class="flex gap-2 lg:justify-end">
                        <button type="button" @click="openSearchResult(item)" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-300 hover:border-cyan-400/50 hover:text-cyan-300">
                            Open
                        </button>
                        <a x-show="!item.is_dir" :href="item.download_url" class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-medium text-slate-300 hover:border-slate-500 hover:text-white">
                            Download
                        </a>
                    </div>
                </article>
            </template>
        </div>
    </div>
</section>
