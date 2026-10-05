<section x-show="viewSection === 'home'" x-cloak class="mx-auto w-full max-w-7xl">
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-medium text-cyan-300">Private cloud</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight text-white">Welcome back, {{ strtok(auth()->user()->name, ' ') }}</h1>
            <p class="mt-1 text-sm text-slate-400">Your files, recent work, and storage at a glance.</p>
        </div>
        <p x-show="home.scanned_at" class="text-[11px] text-slate-600">
            Storage refreshed <span x-text="home.scanned_at ? new Date(home.scanned_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''"></span>
        </p>
    </div>

    <div x-show="isHomeLoading && !home.scanned_at" class="grid gap-4 lg:grid-cols-3" aria-label="Loading Home">
        <div class="jhn-surface h-40 animate-pulse rounded-xl lg:col-span-2"></div>
        <div class="jhn-surface h-40 animate-pulse rounded-xl"></div>
        <div class="jhn-surface h-72 animate-pulse rounded-xl lg:col-span-2"></div>
        <div class="jhn-surface h-72 animate-pulse rounded-xl"></div>
    </div>

    <div x-show="!isHomeLoading || home.scanned_at" class="space-y-6">
        <section aria-labelledby="quick-actions-heading">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="quick-actions-heading" class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Quick actions</h2>
            </div>
            <div class="grid gap-3 sm:grid-cols-3">
                <button type="button"
                        class="jhn-surface group flex min-h-24 items-center gap-4 rounded-xl p-4 text-left transition hover:border-cyan-400/50"
                        @click="currentPath = ''; $refs.fileInput.click()">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-cyan-400/10 text-cyan-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-white">Upload files</span>
                        <span class="mt-1 block text-xs text-slate-500">Add files to My Drive</span>
                    </span>
                </button>

                <button type="button"
                        class="jhn-surface group flex min-h-24 items-center gap-4 rounded-xl p-4 text-left transition hover:border-amber-400/50"
                        @click="currentPath = ''; openNewFolderDialog()">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-400/10 text-amber-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2zm6-4h6m-3-3v6"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-white">New folder</span>
                        <span class="mt-1 block text-xs text-slate-500">Organize from the root</span>
                    </span>
                </button>

                <button type="button"
                        class="jhn-surface group flex min-h-24 items-center gap-4 rounded-xl p-4 text-left transition hover:border-slate-500"
                        @click="switchSection('drive')">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold text-white">Browse My Drive</span>
                        <span class="mt-1 block text-xs text-slate-500">Open the full file browser</span>
                    </span>
                </button>
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(19rem,0.75fr)]">
            <section class="jhn-surface overflow-hidden rounded-xl" aria-labelledby="recent-heading">
                <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3.5 sm:px-5">
                    <div>
                        <h2 id="recent-heading" class="text-sm font-semibold text-white">Recently modified</h2>
                        <p class="mt-0.5 text-[11px] text-slate-500">Newest files across My Drive</p>
                    </div>
                    <button type="button" class="text-xs font-medium text-cyan-300 hover:text-cyan-200" @click="switchSection('drive')">View Drive</button>
                </div>

                <div x-show="home.recent.length === 0" class="px-5 py-12 text-center">
                    <svg class="mx-auto h-8 w-8 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="mt-3 text-sm font-medium text-slate-300">No files yet</p>
                    <p class="mt-1 text-xs text-slate-500">Upload a file and it will appear here.</p>
                </div>

                <ul x-show="home.recent.length > 0" class="divide-y divide-slate-800">
                    <template x-for="item in home.recent" :key="item.path">
                        <li class="flex min-w-0 items-center gap-3 px-4 py-3 sm:px-5">
                            <button type="button"
                                    class="flex min-w-0 flex-1 items-center gap-3 text-left"
                                    @click="openItemLocation(item)"
                                    :aria-label="'Show ' + item.name + ' in My Drive'">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-[9px] font-bold uppercase"
                                      :style="{ color: categoryColor(item.category) }"
                                      x-text="item.extension || 'file'"></span>
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-semibold text-slate-100" x-text="item.name"></span>
                                    <span class="mt-1 block truncate text-[11px] text-slate-500">
                                        <span x-text="item.parent_path || 'My Drive'"></span>
                                        <span aria-hidden="true"> ? </span>
                                        <span x-text="item.human_size"></span>
                                        <span aria-hidden="true"> ? </span>
                                        <span x-text="item.modified_human"></span>
                                    </span>
                                </span>
                            </button>
                            <button x-show="item.preview_url"
                                    type="button"
                                    class="jhn-icon-button shrink-0"
                                    @click="openMediaModal(item)"
                                    :aria-label="'Preview ' + item.name">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/>
                                    <circle cx="12" cy="12" r="2.5"/>
                                </svg>
                            </button>
                            <a :href="item.download_url" class="jhn-icon-button shrink-0" :aria-label="'Download ' + item.name">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>
                                </svg>
                            </a>
                        </li>
                    </template>
                </ul>
            </section>

            <section class="jhn-surface rounded-xl p-4 sm:p-5" aria-labelledby="storage-heading">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="storage-heading" class="text-sm font-semibold text-white">Storage</h2>
                        <p class="mt-1 text-[11px] text-slate-500">
                            <span x-text="home.storage.file_count || 0"></span> files indexed
                        </p>
                    </div>
                    <span class="text-xs font-semibold tabular-nums text-cyan-300" x-text="(home.storage.percent_used || 0) + '%'"></span>
                </div>

                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-800">
                    <div class="h-full rounded-full bg-cyan-400 transition-[width]"
                         :style="'width: ' + Math.min(home.storage.percent_used || 0, 100) + '%'"></div>
                </div>
                <p class="mt-2 text-xs text-slate-400">
                    <span class="font-semibold text-slate-200" x-text="home.storage.used_human || '0 B'"></span>
                    of <span x-text="home.storage.quota_human || '20 GB'"></span> used
                </p>

                <div class="mt-6 space-y-3">
                    <template x-for="category in home.categories" :key="category.key">
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-[11px]">
                                <span class="flex items-center gap-2 text-slate-400">
                                    <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: categoryColor(category.key) }"></span>
                                    <span x-text="category.label"></span>
                                    <span class="text-slate-600" x-text="'(' + category.count + ')'"></span>
                                </span>
                                <span class="tabular-nums text-slate-300" x-text="category.human_size"></span>
                            </div>
                            <div class="h-1 overflow-hidden rounded-full bg-slate-800">
                                <div class="h-full rounded-full"
                                     :style="{ width: Math.max(category.percent, category.bytes > 0 ? 2 : 0) + '%', backgroundColor: categoryColor(category.key) }"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>
        </div>

        <section class="jhn-surface overflow-hidden rounded-xl" aria-labelledby="shared-home-heading">
            <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3.5 sm:px-5">
                <div>
                    <h2 id="shared-home-heading" class="text-sm font-semibold text-white">Recently shared</h2>
                    <p class="mt-0.5 text-[11px] text-slate-500">Your newest active public links</p>
                </div>
                <button type="button" class="text-xs font-medium text-cyan-300 hover:text-cyan-200" @click="switchSection('shared')">All links</button>
            </div>

            <div x-show="home.shared.length === 0" class="px-5 py-8 text-center sm:text-left">
                <p class="text-sm font-medium text-slate-300">Nothing shared yet</p>
                <p class="mt-1 text-xs text-slate-500">Create a link from My Drive when you want to share a file or folder.</p>
            </div>

            <div x-show="home.shared.length > 0" class="grid divide-y divide-slate-800 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                <template x-for="item in home.shared" :key="item.id">
                    <div class="flex min-w-0 items-center gap-3 px-4 py-4 sm:px-5">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-400/10 text-emerald-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.8 10.2a4 4 0 00-5.6 0l-4 4a4 4 0 105.6 5.6l1.1-1.1m-.7-4.9a4 4 0 005.6 0l4-4a4 4 0 00-5.6-5.6l-1.1 1.1"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-xs font-semibold text-slate-100" x-text="item.name"></span>
                            <span class="mt-1 block text-[11px] text-slate-500">
                                <span x-text="item.created_human"></span>
                                <span aria-hidden="true"> ? </span>
                                <span x-text="item.downloads_count + ' downloads'"></span>
                            </span>
                        </span>
                        <button type="button" class="jhn-icon-button shrink-0" @click="copyDirectLink(item.share_url)" :aria-label="'Copy link for ' + item.name">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </section>
    </div>
</section>
