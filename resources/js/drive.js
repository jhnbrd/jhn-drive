window.driveApp = function driveApp() {
    return {
        viewSection: 'drive', // 'drive' or 'shared'
        mobileSidebarOpen: false,
        items: [],
        sharedItems: [],
        currentPath: '',
        parentPath: '',
        breadcrumbs: [{ name: 'My Drive', path: '' }],
        viewMode: localStorage.getItem('jhn_drive_view') || 'grid',
        searchQuery: '',
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
        showPreviewModal: false,
        previewFile: null,
        showRenameModal: false,
        renameTarget: null,
        renameName: '',
        toasts: [],

        init() {
            this.$watch('viewMode', val => localStorage.setItem('jhn_drive_view', val));
            this.loadFiles('');
            this.loadStats();
            this.loadSharedLinks();
        },

        get folders() {
            return this.filteredItems.filter(i => i.is_dir);
        },

        get files() {
            return this.filteredItems.filter(i => !i.is_dir);
        },

        get filteredItems() {
            if (!this.searchQuery) return this.items;
            const query = this.searchQuery.toLowerCase();
            return this.items.filter(i => i.name.toLowerCase().includes(query));
        },

        get filteredSharedItems() {
            if (!this.searchQuery) return this.sharedItems;
            const query = this.searchQuery.toLowerCase();
            return this.sharedItems.filter(i => i.name.toLowerCase().includes(query));
        },

        switchSection(section) {
            this.viewSection = section;
            this.searchQuery = '';
            if (section === 'shared') {
                this.loadSharedLinks();
            } else {
                this.loadFiles(this.currentPath);
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

        navigateTo(path) {
            this.viewSection = 'drive';
            this.loadFiles(path);
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
                    this.loadFiles(this.currentPath);
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
                    this.showToast('Deleted', data.message, 'success');
                    this.loadFiles(this.currentPath);
                    this.loadStats();
                    this.loadSharedLinks();
                } else {
                    this.showToast('Error', data.message || 'Delete failed.', 'error');
                }
            } catch (err) {
                this.showToast('Request Failed', err.message, 'error');
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
                    await Promise.all([this.loadFiles(this.currentPath), this.loadStats()]);
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
