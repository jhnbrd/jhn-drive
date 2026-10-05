            <!-- ============================================================= -->
            <!-- SECTION 2: DEDICATED SHARED LINKS VIEW -->
            <!-- ============================================================= -->
            <div x-show="viewSection === 'shared'" x-cloak>
                <div x-show="filteredSharedItems.length === 0" class="mx-auto max-w-lg rounded-3xl border border-[#3b4b66] bg-[#131822] p-10 text-center shadow-2xl mt-12">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 shadow-inner">
                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-white">No active shared links</h3>
                    <p class="mt-2 text-xs text-slate-300 leading-relaxed">
                        Files and folders you share publicly will appear here with one-click link copying and instant unshare capabilities.
                    </p>
                    <button @click="switchSection('drive')" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-sky-400 transition-colors shadow-md">
                        Browse My Drive
                    </button>
                </div>

                <div x-show="filteredSharedItems.length > 0" class="overflow-hidden rounded-2xl border border-[#2d3a50] bg-[#131a26] shadow-xl">
                    <table class="w-full text-left text-xs text-[#f8fafc]">
                        <thead class="border-b border-[#3b4b66] bg-[#0e1420] uppercase tracking-wider text-slate-200">
                            <tr>
                                <th class="py-4 pl-5 pr-3 font-extrabold">Shared Item</th>
                                <th class="px-4 py-4 font-extrabold">Type</th>
                                <th class="px-4 py-4 font-extrabold">Size</th>
                                <th class="px-4 py-4 font-extrabold">Downloads</th>
                                <th class="px-4 py-4 font-extrabold">Created</th>
                                <th class="py-4 pl-3 pr-5 text-right font-extrabold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#2d3a50]">
                            <template x-for="item in filteredSharedItems" :key="item.id">
                                <tr class="hover:bg-[#182333] transition-colors group">
                                    <td class="py-3 pl-5 pr-3">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#0b0e14] border border-[#3b4b66] font-bold text-[10px]">
                                                <template x-if="item.is_dir">
                                                    <svg class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M19.5 21a3 3 0 003-3v-4.5a3 3 0 00-3-3h-1.5V9a3 3 0 00-3-3h-4.5a3 3 0 00-2.12.88L6.88 8.38A3 3 0 014.76 9H4.5A3 3 0 001.5 12v6a3 3 0 003 3h15z"/>
                                                    </svg>
                                                </template>
                                                <template x-if="!item.is_dir && item.category === 'image'"><span class="text-sky-400">IMG</span></template>
                                                <template x-if="!item.is_dir && item.category === 'video'"><span class="text-purple-400">VID</span></template>
                                                <template x-if="!item.is_dir && item.category !== 'image' && item.category !== 'video'"><span class="text-slate-300">FILE</span></template>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="truncate font-bold text-white group-hover:text-sky-300 transition-colors" x-text="item.name"></div>
                                                <a :href="item.share_url" target="_blank" class="text-[11px] text-sky-400 font-mono hover:underline truncate block" x-text="item.share_url"></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px]">
                                        <span :class="item.is_dir ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-sky-500/20 text-sky-300 border border-sky-500/40'"
                                              class="rounded-lg px-2.5 py-1 font-bold"
                                              x-text="item.is_dir ? 'Folder' : 'File'"></span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-200 font-semibold" x-text="item.human_size"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-emerald-300 font-mono font-bold" x-text="item.downloads_count + ' dl'"></td>
                                    <td class="whitespace-nowrap px-4 py-3 text-[11px] text-slate-300 font-medium" x-text="item.created_human"></td>
                                    <td class="whitespace-nowrap py-3 pl-3 pr-5 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="copyDirectLink(item.share_url)" 
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-sky-500/50 bg-[#162338] px-3 py-1.5 text-xs font-bold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-colors shadow-sm">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                </svg>
                                                <span>Copy Link</span>
                                            </button>
                                            <button @click="unshareItem(item)" 
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-amber-500/50 bg-amber-950/40 px-3 py-1.5 text-xs font-bold text-amber-300 hover:bg-amber-500 hover:text-slate-950 hover:border-amber-400 transition-colors shadow-sm">
                                                <span>Unshare</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

