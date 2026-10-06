window.driveApp = function driveApp(userId = 'guest') {
    const preferenceKey = name => `jhn_drive_${userId}_${name}`;
    const readPreference = (name, allowed, fallback) => {
        try {
            const value = localStorage.getItem(preferenceKey(name));
            return allowed.includes(value) ? value : fallback;
        } catch {
            return fallback;
        }
    };
    const writePreference = (name, value) => {
        try {
            localStorage.setItem(preferenceKey(name), value);
        } catch {
            // Preferences are optional when browser storage is unavailable.
        }
    };

    return {
        viewSection: 'home',
        mobileSidebarOpen: false,
        items: [],
        sharedItems: [],
        trashItems: [],
        home: {
            recent: [],
            shared: [],
            categories: [],
            storage: {},
            scanned_at: null
        },
        isHomeLoading: false,
        currentPath: '',
        parentPath: '',
        breadcrumbs: [{ name: 'My Drive', path: '' }],
        viewMode: readPreference('view', ['grid', 'list'], 'grid'),
        driveFilter: readPreference('filter', ['all', 'folder', 'image', 'video', 'audio', 'document', 'archive'], 'all'),
        driveSort: readPreference('sort', ['name', 'modified', 'size', 'type'], 'name'),
        driveDirection: readPreference('direction', ['asc', 'desc'], 'asc'),
        searchQuery: '',
        searchResults: [],
        searchType: 'all',
        searchSort: 'name',
        searchDirection: 'asc',
        searchTruncated: false,
        isSearchLoading: false,
        activeSearchRequest: null,
        isLoading: false,
        isUploading: false,
        uploadProgress: 0,
        uploadBytesSent: 0,
        uploadBytesTotal: 0,
        uploadCurrentFile: '',
        uploadFileIndex: 0,
        uploadFileCount: 0,
        activeUploadRequest: null,
        uploadCancelled: false,
        isDragging: false,
        activeMenu: null,
        selectedPaths: [],
        selectionAnchor: null,
        isBulkDownloading: false,
        showTransferModal: false,
        transferOperation: 'move',
        transferItems: [],
        pickerPath: '',
        pickerParentPath: '',
        pickerBreadcrumbs: [{ name: 'My Drive', path: '' }],
        pickerFolders: [],
        isPickerLoading: false,
        isTransferring: false,
        showDetailsModal: false,
        detailsItem: null,
        stats: {
            percent_used: 0,
            used_human: '0 B',
            free_human: '20 GB',
            quota_human: '20 GB'
        },
        showNewFolderModal: false,
        newFolderName: '',
        showDeleteModal: false,
        targetDeleteItem: null,
        showPermanentDeleteModal: false,
        pendingPermanentTrashItem: null,
        showEmptyTrashModal: false,
        isTrashLoading: false,
        isTrashMutating: false,
        showPreviewModal: false,
        previewFile: null,
        showRenameModal: false,
        renameTarget: null,
        renameName: '',
        toasts: [],

        init() {
            this.$watch('viewMode', value => writePreference('view', value));
            this.$watch('driveFilter', value => writePreference('filter', value));
            this.$watch('driveSort', value => writePreference('sort', value));
            this.$watch('driveDirection', value => writePreference('direction', value));
            this.loadHome();
            this.loadSharedLinks();
            this.loadTrash();
        },

        get folders() {
            return this.filteredItems.filter(i => i.is_dir);
        },

        get files() {
            return this.filteredItems.filter(i => !i.is_dir);
        },

        get selectedItems() {
            const selected = new Set(this.selectedPaths);
            return this.items.filter(item => selected.has(item.path));
        },

        get selectionCount() {
            return this.selectedPaths.length;
        },

        get allVisibleSelected() {
            return this.filteredItems.length > 0
                && this.filteredItems.every(item => this.selectedPaths.includes(item.path));
        },

        get filteredItems() {
            const filtered = this.items.filter(item => this.matchesDriveFilter(item));
            const direction = this.driveDirection === 'desc' ? -1 : 1;

            return [...filtered].sort((left, right) => {
                if (left.is_dir !== right.is_dir) return left.is_dir ? -1 : 1;

                const comparison = {
                    modified: () => String(left.modified_at).localeCompare(String(right.modified_at)),
                    size: () => Number(left.size || 0) - Number(right.size || 0),
                    type: () => String(left.category).localeCompare(String(right.category))
                        || left.name.localeCompare(right.name, undefined, { numeric: true, sensitivity: 'base' }),
                    name: () => left.name.localeCompare(right.name, undefined, { numeric: true, sensitivity: 'base' })
                }[this.driveSort]();

                return comparison * direction;
            });
        },

        matchesDriveFilter(item) {
            if (this.driveFilter === 'all') return true;
            if (this.driveFilter === 'folder') return item.is_dir;
            if (this.driveFilter === 'document') {
                return ['document', 'pdf', 'code'].includes(item.category);
            }

            return item.category === this.driveFilter;
        },

        resetDriveFilters() {
            this.driveFilter = 'all';
            this.driveSort = 'name';
            this.driveDirection = 'asc';
            this.clearSelection();
        },

        get filteredSharedItems() {
            if (!this.searchQuery) return this.sharedItems;
            const query = this.searchQuery.toLowerCase();
            return this.sharedItems.filter(i => i.name.toLowerCase().includes(query));
        },


        get filteredTrashItems() {
            if (!this.searchQuery) return this.trashItems;
            const query = this.searchQuery.toLowerCase();
            return this.trashItems.filter(item =>
                item.name.toLowerCase().includes(query)
                || item.original_path.toLowerCase().includes(query)
            );
        },
        switchSection(section) {
            this.viewSection = section;
            this.searchQuery = '';

            if (section === 'home') {
                this.loadHome();
            } else if (section === 'shared') {
                this.loadSharedLinks();
            } else if (section === 'trash') {
                this.loadTrash();
            } else if (section === 'search') {
                this.searchResults = [];
                this.searchTruncated = false;
            } else {
                this.loadFiles(this.currentPath);
            }
        },

        async loadHome(refresh = false) {
            this.isHomeLoading = true;
            try {
                const suffix = refresh ? '?refresh=1' : '';
                const res = await fetch('/api/home' + suffix);
                const data = await res.json();
                if (data.success) {
                    this.home = data;
                    this.stats = { ...data.storage, upload_limits: data.upload_limits };
                } else {
                    this.showToast('Home unavailable', data.message || 'Unable to load Home.', 'error');
                }
            } catch (err) {
                this.showToast('Network Error', err.message, 'error');
            } finally {
                this.isHomeLoading = false;
            }
        },


        async loadFiles(path = '') {
            this.isLoading = true;
            try {
                const res = await fetch(`/api/files?path=${encodeURIComponent(path)}`);
                const data = await res.json();
                if (data.success) {
                    this.items = data.items;
                    this.currentPath = data.current_path;
                    this.parentPath = data.parent_path;
                    this.breadcrumbs = data.breadcrumbs;
                    this.clearSelection();
                } else {
                    this.showToast('Error', data.message || 'Failed to load directory.', 'error');
                }
            } catch (err) {
                this.showToast('Network Error', err.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        async loadSharedLinks() {
            try {
                const res = await fetch('/api/shared');
                const data = await res.json();
                if (data.success) {
                    this.sharedItems = data.items;
                }
            } catch (e) {
                console.error('Failed to load shared links', e);
            }
        },

        async loadTrash() {
            this.isTrashLoading = true;
            try {
                const res = await fetch('/api/trash');
                const data = await res.json();
                if (res.ok && data.success) {
                    this.trashItems = data.items;
                } else {
                    this.showToast('Trash unavailable', data.message || 'Unable to load Trash.', 'error');
                }
            } catch (err) {
                this.showToast('Network Error', err.message, 'error');
            } finally {
                this.isTrashLoading = false;
            }
        },

        async loadStats() {
            try {
                const res = await fetch('/api/stats');
                const data = await res.json();
                if (data.success) {
                    this.stats = data;
                }
            } catch (e) {
                console.error('Failed to load drive stats', e);
            }
        },

        handleSearchInput() {
            if (!['drive', 'search'].includes(this.viewSection)) return;

            if (!this.searchQuery.trim()) {
                this.searchResults = [];
                this.searchTruncated = false;
                return;
            }

            this.viewSection = 'search';
            this.runSearch();
        },

        async runSearch() {
            const query = this.searchQuery.trim();
            if (!query) {
                this.searchResults = [];
                this.searchTruncated = false;
                return;
            }

            this.activeSearchRequest?.abort();
            const controller = new AbortController();
            this.activeSearchRequest = controller;
            this.isSearchLoading = true;

            try {
                const params = new URLSearchParams({
                    q: query,
                    type: this.searchType,
                    sort: this.searchSort,
                    direction: this.searchDirection
                });
                const res = await fetch('/api/search?' + params.toString(), { signal: controller.signal });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Search failed.');
                this.searchResults = data.items;
                this.searchTruncated = data.truncated;
            } catch (err) {
                if (err.name !== 'AbortError') {
                    this.showToast('Search unavailable', err.message, 'error');
                }
            } finally {
                if (this.activeSearchRequest === controller) {
                    this.activeSearchRequest = null;
                    this.isSearchLoading = false;
                }
            }
        },

        openSearchResult(item) {
            if (item.is_dir) {
                this.navigateTo(item.path);
            } else if (item.preview_url) {
                this.openMediaModal(item);
            } else {
                this.showItemDetails(item);
            }
        },

        navigateTo(path) {
            this.viewSection = 'drive';
            this.loadFiles(path);
        },

        openItemLocation(item) {
            this.viewSection = 'drive';
            this.searchQuery = '';
            this.loadFiles(item.parent_path || '');
        },

        categoryColor(key) {
            return {
                image: '#22d3ee',
                video: '#a78bfa',
                document: '#60a5fa',
                audio: '#34d399',
                archive: '#fbbf24',
                other: '#64748b'
            }[key] || '#64748b';
        },

        openMediaModal(file) {
            if (!file.preview_url) return;
            this.previewFile = file;
            this.showPreviewModal = true;
        },

        closeMediaModal() {
            this.showPreviewModal = false;
            this.previewFile = null;
        },

        toggleMenu(path) {
            this.activeMenu = this.activeMenu === path ? null : path;
        },

        isSelected(path) {
            return this.selectedPaths.includes(path);
        },

        toggleSelection(item, event = null) {
            const selected = new Set(this.selectedPaths);

            if (event?.shiftKey && this.selectionAnchor) {
                const anchorIndex = this.filteredItems.findIndex(candidate => candidate.path === this.selectionAnchor);
                const itemIndex = this.filteredItems.findIndex(candidate => candidate.path === item.path);

                if (anchorIndex !== -1 && itemIndex !== -1) {
                    const [start, end] = anchorIndex < itemIndex
                        ? [anchorIndex, itemIndex]
                        : [itemIndex, anchorIndex];
                    this.filteredItems.slice(start, end + 1).forEach(candidate => selected.add(candidate.path));
                }
            } else if (selected.has(item.path)) {
                selected.delete(item.path);
            } else {
                selected.add(item.path);
            }

            this.selectedPaths = Array.from(selected);
            this.selectionAnchor = item.path;
            this.activeMenu = null;
        },

        toggleSelectAll() {
            if (this.allVisibleSelected) {
                const visible = new Set(this.filteredItems.map(item => item.path));
                this.selectedPaths = this.selectedPaths.filter(path => !visible.has(path));
            } else {
                this.selectedPaths = Array.from(new Set([
                    ...this.selectedPaths,
                    ...this.filteredItems.map(item => item.path)
                ]));
            }
        },

        clearSelection() {
            this.selectedPaths = [];
            this.selectionAnchor = null;
        },

        openTransferDialog(items, operation) {
            const selection = Array.isArray(items) ? items : [items];
            this.transferItems = selection.filter(Boolean);
            if (this.transferItems.length === 0) return;
            this.transferOperation = operation;
            this.showTransferModal = true;
            this.activeMenu = null;
            this.loadPickerFolder('');
        },

        closeTransferDialog() {
            if (this.isTransferring) return;
            this.showTransferModal = false;
            this.transferItems = [];
            this.pickerFolders = [];
        },

        async loadPickerFolder(path = '') {
            this.isPickerLoading = true;
            try {
                const res = await fetch(`/api/files?path=${encodeURIComponent(path)}`);
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Unable to load folders.');
                this.pickerPath = data.current_path;
                this.pickerParentPath = data.parent_path;
                this.pickerBreadcrumbs = data.breadcrumbs;
                this.pickerFolders = data.items.filter(item => item.is_dir);
            } catch (err) {
                this.showToast('Folder picker unavailable', err.message, 'error');
            } finally {
                this.isPickerLoading = false;
            }
        },

        transferDestinationInvalid(path) {
            return this.transferItems.some(item =>
                item.is_dir && (path === item.path || path.startsWith(item.path + '/'))
            );
        },

        async executeTransfer() {
            if (this.isTransferring || this.transferDestinationInvalid(this.pickerPath)) return;
            this.isTransferring = true;
            try {
                const res = await fetch('/api/transfer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        operation: this.transferOperation,
                        paths: this.transferItems.map(item => item.path),
                        destination: this.pickerPath
                    })
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'The operation failed.');
                this.showToast(
                    this.transferOperation === 'move' ? 'Items moved' : 'Items copied',
                    data.message,
                    'success'
                );
                this.showTransferModal = false;
                this.transferItems = [];
                this.clearSelection();
                await Promise.all([
                    this.loadFiles(this.currentPath),
                    this.loadStats(),
                    this.loadHome(true),
                    this.loadSharedLinks()
                ]);
            } catch (err) {
                this.showToast(
                    this.transferOperation === 'move' ? 'Move failed' : 'Copy failed',
                    err.message,
                    'error'
                );
            } finally {
                this.isTransferring = false;
            }
        },

        activateItem(item, event = null) {
            if (this.selectionCount > 0 || event?.ctrlKey || event?.metaKey || event?.shiftKey) {
                this.toggleSelection(item, event);
                return;
            }

            if (item.is_dir) {
                this.navigateTo(item.path);
            } else if (item.preview_url) {
                this.openMediaModal(item);
            } else {
                this.showItemDetails(item);
            }
        },

        showItemDetails(item) {
            this.detailsItem = item;
            this.showDetailsModal = true;
            this.activeMenu = null;
            this.$nextTick(() => this.$refs.detailsClose?.focus());
        },

        closeItemDetails() {
            this.showDetailsModal = false;
            this.detailsItem = null;
        },

        itemLocation(item) {
            if (!item?.path || !item.path.includes('/')) return 'My Drive';
            const parts = item.path.split('/');
            parts.pop();
            return 'My Drive / ' + parts.join(' / ');
        },

        formatDate(value) {
            if (!value) return 'Unknown';
            const parsed = new Date(value.replace(' ', 'T'));
            return Number.isNaN(parsed.getTime())
                ? value
                : parsed.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
        },

        async downloadSelected() {
            const selected = this.selectedItems;
            if (selected.length === 0 || this.isBulkDownloading) return;

            if (selected.length === 1 && !selected[0].is_dir) {
                window.location.assign(selected[0].download_url);
                return;
            }

            this.isBulkDownloading = true;
            try {
                const res = await fetch('/api/download-selection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/zip, application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ paths: selected.map(item => item.path) })
                });

                if (!res.ok) {
                    const data = await res.json().catch(() => ({}));
                    throw new Error(data.message || 'The selection could not be downloaded.');
                }

                const blob = await res.blob();
                const disposition = res.headers.get('Content-Disposition') || '';
                const match = disposition.match(/filename="?([^";]+)"?/i);
                const filename = match?.[1] || 'jhn-drive-selection.zip';
                const url = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = url;
                anchor.download = filename;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                URL.revokeObjectURL(url);
                this.showToast('Download ready', selected.length + ' items were packaged successfully.', 'success');
            } catch (err) {
                this.showToast('Download failed', err.message, 'error');
            } finally {
                this.isBulkDownloading = false;
            }
        },

        openNewFolderDialog() {
            this.newFolderName = '';
            this.showNewFolderModal = true;
            this.$nextTick(() => {
                this.$refs.newFolderInput && this.$refs.newFolderInput.focus();
            });
        },

        async createFolder() {
            if (!this.newFolderName.trim()) return;
            try {
                const res = await fetch('/api/mkdir', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        path: this.currentPath,
                        name: this.newFolderName.trim()
                    })
                });
                const data = await res.json();
                if (data.success) {
                    this.showNewFolderModal = false;
                    this.showToast('Success', data.message, 'success');
                    if (this.viewSection === 'home') {
                        this.loadHome(true);
                    } else {
                        this.loadFiles(this.currentPath);
                    }
                } else {
                    this.showToast('Error', data.message || 'Could not create folder.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        confirmDelete(item) {
            this.targetDeleteItem = item;
            this.showDeleteModal = true;
        },

        async executeDelete() {
            if (!this.targetDeleteItem) return;
            try {
                const res = await fetch('/api/delete', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: this.targetDeleteItem.path })
                });
                const data = await res.json();
                if (data.success) {
                    this.showDeleteModal = false;
                    this.showToast('Moved to Trash', data.message, 'success');
                    if (this.viewSection === 'home') {
                        this.loadHome(true);
                    } else {
                        this.loadFiles(this.currentPath);
                    }
                    this.loadStats();
                    this.loadSharedLinks();
                    this.loadTrash();
                    this.targetDeleteItem = null;
                } else {
                    this.showToast('Error', data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        async restoreTrashItem(item) {
            if (this.isTrashMutating) return;
            this.isTrashMutating = true;
            try {
                const res = await fetch(`/api/trash/${item.id}/restore`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Restore failed.');
                this.showToast('Restored', data.message, 'success');
                await Promise.all([this.loadTrash(), this.loadStats(), this.loadHome(true)]);
            } catch (err) {
                this.showToast('Restore failed', err.message, 'error');
            } finally {
                this.isTrashMutating = false;
            }
        },

        confirmPermanentDelete(item) {
            this.pendingPermanentTrashItem = item;
            this.showPermanentDeleteModal = true;
        },

        async permanentlyDeleteTrashItem() {
            if (!this.pendingPermanentTrashItem || this.isTrashMutating) return;
            this.isTrashMutating = true;
            try {
                const res = await fetch(`/api/trash/${this.pendingPermanentTrashItem.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Permanent deletion failed.');
                this.showPermanentDeleteModal = false;
                this.pendingPermanentTrashItem = null;
                this.showToast('Permanently deleted', data.message, 'success');
                await Promise.all([this.loadTrash(), this.loadStats(), this.loadHome(true)]);
            } catch (err) {
                this.showToast('Delete failed', err.message, 'error');
            } finally {
                this.isTrashMutating = false;
            }
        },

        async emptyTrash() {
            if (this.isTrashMutating) return;
            this.isTrashMutating = true;
            try {
                const res = await fetch('/api/trash', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                const data = await res.json();
                if (!res.ok || !data.success) throw new Error(data.message || 'Trash could not be emptied.');
                this.showEmptyTrashModal = false;
                this.showToast('Trash emptied', data.message, 'success');
                await Promise.all([this.loadTrash(), this.loadStats(), this.loadHome(true)]);
            } catch (err) {
                this.showToast('Empty Trash failed', err.message, 'error');
            } finally {
                this.isTrashMutating = false;
            }

        },
        renameItem(item) {
            this.renameTarget = item;
            this.renameName = item.name;
            this.showRenameModal = true;
            this.$nextTick(() => {
                if (this.$refs.renameInput) {
                    this.$refs.renameInput.focus();
                    this.$refs.renameInput.select();
                }
            });
        },

        async executeRename() {
            if (!this.renameTarget || !this.renameName.trim()) return;
            try {
                const res = await fetch('/api/rename', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: this.renameTarget.path, new_name: this.renameName.trim() })
                });
                const data = await res.json();
                if (data.success) {
                    this.showRenameModal = false;
                    this.renameTarget = null;
                    this.renameName = '';
                    this.showToast('Renamed', data.message, 'success');
                    this.loadFiles(this.currentPath);
                    this.loadSharedLinks();
                } else {
                    this.showToast('Error', data.message || 'Rename failed.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
            }
        },

        async shareItem(item) {
            try {
                const res = await fetch('/api/share', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ path: item.path })
                });
                const data = await res.json();
                if (data.success) {
                    item.share_token = data.token;
                    item.share_url = data.share_url;
                    await this.copyDirectLink(data.share_url);
                    this.loadSharedLinks();
                } else {
                    this.showToast('Share Failed', data.message || 'Unable to generate share link.', 'error');
                }
            } catch (err) {
                this.showToast('Error', err.message, 'error');
            }
        },

        async unshareItem(item) {
            try {
                const res = await fetch('/api/unshare', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ 
                        path: item.path,
                        token: item.token || item.share_token 
                    })
                });
                const data = await res.json();
                if (data.success) {
                    item.share_token = null;
                    item.share_url = null;
                    this.showToast('Unshared', 'Public link revoked successfully.', 'info');
                    this.loadSharedLinks();
                    if (this.viewSection === 'drive') {
                        this.loadFiles(this.currentPath);
                    }
                } else {
                    this.showToast('Unshare Failed', data.message, 'error');
                }
            } catch (err) {
                this.showToast('Error', err.message, 'error');
            }
        },

        async copyDirectLink(url) {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                }
                this.showToast('Link Copied to Clipboard!', url, 'success');
            } catch (e) {
                this.showToast('Copied', url, 'info');
            }
        },

        handleFileInputChange(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                this.uploadFiles(files);
            }
            e.target.value = '';
        },

        onDragLeave(e) {
            if (e.clientX <= 0 || e.clientY <= 0 || e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
                this.isDragging = false;
            }
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                this.uploadFiles(files);
            }
        },

        async uploadFiles(fileList) {
            if (this.isUploading) {
                this.showToast('Upload in progress', 'Finish or cancel the current upload first.', 'info');
                return;
            }

            const files = Array.from(fileList);
            const limits = this.stats.upload_limits || {};
            const totalBytes = files.reduce((sum, file) => sum + file.size, 0);
            const oversized = files.find(file => limits.max_file_bytes && file.size > limits.max_file_bytes);

            if (oversized) {
                this.showToast('File too large', `${oversized.name} exceeds the server limit of ${limits.max_file_human}.`, 'error');
                return;
            }

            if (this.stats.free_bytes !== undefined && totalBytes > this.stats.free_bytes) {
                this.showToast('Storage full', `These files need ${this.formatBytes(totalBytes)}, but only ${this.stats.free_human} is available.`, 'error');
                return;
            }

            this.isUploading = true;
            this.uploadCancelled = false;
            this.uploadProgress = 0;
            this.uploadBytesSent = 0;
            this.uploadBytesTotal = totalBytes;
            this.uploadFileCount = files.length;

            let completedBytes = 0;
            let completedFiles = 0;

            try {
                for (let index = 0; index < files.length; index++) {
                    if (this.uploadCancelled) break;

                    const file = files[index];
                    this.uploadFileIndex = index + 1;
                    this.uploadCurrentFile = file.name;

                    const data = await this.uploadSingleFile(file, completedBytes);
                    completedBytes += file.size;
                    completedFiles++;

                    const stored = data.files?.[0];
                    if (stored?.name && stored.name !== file.name) {
                        this.showToast('Duplicate renamed', `${file.name} was saved as ${stored.name}.`, 'info');
                    }
                }

                if (!this.uploadCancelled) {
                    this.uploadProgress = 100;
                    this.uploadBytesSent = totalBytes;
                    this.showToast('Upload complete', `${completedFiles} file(s) uploaded successfully.`, 'success');
                }

                if (completedFiles > 0) {
                    const contentReload = this.viewSection === 'home'
                        ? this.loadHome(true)
                        : this.loadFiles(this.currentPath);
                    await Promise.all([contentReload, this.loadStats()]);
                }
            } catch (err) {
                if (!this.uploadCancelled) {
                    this.showToast('Upload failed', err.message || 'The upload could not be completed.', 'error');
                }
            } finally {
                this.activeUploadRequest = null;
                this.isUploading = false;
                this.uploadCurrentFile = '';
                this.uploadFileIndex = 0;
                this.uploadFileCount = 0;
            }
        },

        uploadSingleFile(file, completedBytes) {
            return new Promise((resolve, reject) => {
                const formData = new FormData();
                formData.append('path', this.currentPath);
                formData.append('files[]', file);

                const xhr = new XMLHttpRequest();
                this.activeUploadRequest = xhr;
                xhr.open('POST', '/api/upload', true);

                const csrf = document.querySelector('meta[name="csrf-token"]');
                if (csrf) {
                    xhr.setRequestHeader('X-CSRF-TOKEN', csrf.getAttribute('content'));
                }
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.upload.onprogress = (event) => {
                    if (!event.lengthComputable) return;
                    this.uploadBytesSent = Math.min(completedBytes + event.loaded, this.uploadBytesTotal);
                    this.uploadProgress = this.uploadBytesTotal > 0
                        ? Math.round((this.uploadBytesSent / this.uploadBytesTotal) * 100)
                        : 0;
                };

                xhr.onload = () => {
                    let data = {};
                    try {
                        data = JSON.parse(xhr.responseText || '{}');
                    } catch {
                        reject(new Error(`The server returned an unreadable response (HTTP ${xhr.status}).`));
                        return;
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && data.success) {
                        resolve(data);
                        return;
                    }

                    reject(new Error(data.message || `Upload failed (HTTP ${xhr.status}).`));
                };
                xhr.onerror = () => reject(new Error('The network connection was interrupted.'));
                xhr.onabort = () => reject(new DOMException('Upload cancelled.', 'AbortError'));
                xhr.send(formData);
            });
        },

        cancelUpload() {
            if (!this.isUploading) return;
            this.uploadCancelled = true;
            this.activeUploadRequest?.abort();
            this.showToast('Upload cancelled', 'No additional files will be uploaded.', 'info');
        },

        formatBytes(bytes) {
            if (!Number.isFinite(Number(bytes)) || Number(bytes) <= 0) return '0 B';
            const units = ['B', 'KB', 'MB', 'GB', 'TB'];
            const index = Math.min(Math.floor(Math.log(Number(bytes)) / Math.log(1024)), units.length - 1);
            return `${(Number(bytes) / Math.pow(1024, index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
        },

        showToast(title, message, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, title, message, type });
            setTimeout(() => {
                this.dismissToast(id);
            }, 4000);
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }
};
