<div class="relative shrink-0" @click.stop>
    <button type="button"
            class="jhn-icon-button"
            @click="toggleMenu(item.path)"
            :aria-expanded="activeMenu === item.path"
            :aria-label="'Actions for ' + item.name">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5h.01M12 12h.01M12 19h.01"/>
        </svg>
    </button>

    <div x-show="activeMenu === item.path"
         x-cloak
         @click.outside="activeMenu = null"
         class="jhn-surface absolute right-0 top-full z-50 mt-1.5 w-52 rounded-lg p-1.5">
        <button type="button"
                class="jhn-nav-item"
                @click="activateItem(item); activeMenu = null">
            <svg class="h-4 w-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"/>
                <circle cx="12" cy="12" r="2.5"/>
            </svg>
            <span x-text="item.is_dir ? 'Open folder' : (item.preview_url ? 'Preview' : 'Open details')"></span>
        </button>

        <template x-if="!item.is_dir">
            <a :href="item.download_url" class="jhn-nav-item">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14"/>
                </svg>
                Download
            </a>
        </template>
        <button x-show="item.is_dir"
                type="button"
                class="jhn-nav-item"
                @click="selectedPaths = [item.path]; downloadSelected(); activeMenu = null">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M5 7l1 13h12l1-13M9 3h6l1 4H8l1-4z"/>
            </svg>
            Download as ZIP
        </button>

        <button type="button"
                class="jhn-nav-item"
                @click="shareItem(item); activeMenu = null">
            <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a4 4 0 010-8h3m6 0h3a4 4 0 010 8h-3m-6-4h6"/>
            </svg>
            <span x-text="item.share_token ? 'Copy share link' : 'Create share link'"></span>
        </button>
        <button x-show="item.share_token"
                type="button"
                class="jhn-nav-item text-amber-300"
                @click="unshareItem(item); activeMenu = null">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4l16 16"/>
            </svg>
            Revoke share
        </button>

        <div class="my-1 border-t border-slate-800"></div>
        <button type="button" class="jhn-nav-item" @click="openTransferDialog(item, 'move')">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 9h10m0 0l-3-3m3 3l-3 3M19 15H9m0 0l3-3m-3 3l3 3"/>
            </svg>
            Move
        </button>
        <button type="button" class="jhn-nav-item" @click="openTransferDialog(item, 'copy')">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <rect x="8" y="8" width="11" height="11" rx="2" stroke-width="2"/>
                <path stroke-linecap="round" stroke-width="2" d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2"/>
            </svg>
            Copy
        </button>
        <button type="button" class="jhn-nav-item" @click="renameItem(item); activeMenu = null">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 20h4L19 9a2.8 2.8 0 10-4-4L4 16v4z"/>
            </svg>
            Rename
        </button>
        <button type="button" class="jhn-nav-item" @click="showItemDetails(item)">
            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke-width="2"/>
                <path stroke-linecap="round" stroke-width="2" d="M12 11v5m0-8h.01"/>
            </svg>
            Details
        </button>
        <button type="button"
                class="jhn-nav-item text-rose-300 hover:text-rose-200"
                @click="confirmDelete(item); activeMenu = null">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 4v6m4-6v6M9 7l1-3h4l1 3m-8 0l1 13h8l1-13"/>
            </svg>
            Move to Trash
        </button>
    </div>
</div>
