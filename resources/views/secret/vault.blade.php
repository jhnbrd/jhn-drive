<!DOCTYPE html>
<html lang="en" style="background-color: #070a0f; color-scheme: dark;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Explicitly block all crawlers --}}
    <meta name="robots" content="noindex, nofollow, noarchive, noimageindex, nosnippet">
    <meta name="googlebot" content="noindex, nofollow">

    <title>Vault</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body {
            margin: 0; padding: 0; min-height: 100%;
            background-color: #070a0f;
            color: #e2e8f0;
            font-family: 'Inter', system-ui, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        [x-cloak] { display: none !important; }

        /* Subtle grid bg */
        body {
            background-image:
                linear-gradient(rgba(56,189,248,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(56,189,248,0.02) 1px, transparent 1px);
            background-size: 32px 32px;
        }

        /* File grid */
        .vault-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
        }

        /* File card */
        .vault-card {
            border-radius: 14px;
            border: 1px solid #1e2d3d;
            background: rgba(13, 20, 33, 0.85);
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s ease;
            backdrop-filter: blur(8px);
        }
        .vault-card:hover {
            border-color: rgba(56, 189, 248, 0.35);
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(56, 189, 248, 0.1);
        }

        /* Thumbnail */
        .vault-thumb {
            width: 100%;
            aspect-ratio: 4/3;
            object-fit: cover;
            display: block;
        }

        /* Video play overlay */
        .play-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.45);
            transition: background 0.2s;
        }
        .play-overlay:hover { background: rgba(0,0,0,0.25); }

        /* Modal */
        .modal-bg {
            position: fixed;
            inset: 0;
            z-index: 60;
            background: rgba(0,0,0,0.94);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(12px);
        }

        /* Video player */
        video { border-radius: 12px; max-height: 85vh; max-width: 100%; }
        img.preview-img { border-radius: 12px; max-height: 85vh; max-width: 100%; object-fit: contain; }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed #2d3748;
            border-radius: 16px;
            background: rgba(14,20,30,0.6);
            transition: all 0.2s ease;
        }
        .upload-zone.drag-over {
            border-color: #38bdf8;
            background: rgba(56,189,248,0.08);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0d1117; }
        ::-webkit-scrollbar-thumb { background: #2d3748; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #4a5568; }
    </style>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body>

<div x-data="vaultApp()"
     @dragover.prevent="isDragging = true"
     @dragleave.prevent="isDragging = false"
     @drop.prevent="handleDrop($event)"
     class="min-h-screen">

    <!-- DRAG OVERLAY -->
    <div x-show="isDragging" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#070a0f]/90 backdrop-blur-md border-4 border-dashed border-sky-400 pointer-events-none">
        <div class="text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl bg-sky-500/20 text-sky-400 border border-sky-400/30 animate-bounce">
                <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h3 class="mt-4 text-xl font-bold text-white">Drop to encrypt & store</h3>
        </div>
    </div>

    <!-- MEDIA PREVIEW MODAL -->
    <div x-show="showModal" x-cloak @click.self="closeModal()"
         class="modal-bg"
         @keydown.escape.window="closeModal()"
         @keydown.arrow-left.window="prevFile()"
         @keydown.arrow-right.window="nextFile()">
        <div class="relative w-full flex flex-col items-center max-w-5xl">
            
            <!-- Close -->
            <button @click="closeModal()"
                    class="absolute -top-10 right-0 text-slate-400 hover:text-white transition-colors z-10">
                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Prev / Next -->
            <button x-show="previewIndex > 0" x-cloak @click="prevFile()"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-14 text-slate-400 hover:text-white transition-colors hidden md:block">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button x-show="previewIndex < previewableFiles.length - 1" x-cloak @click="nextFile()"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-14 text-slate-400 hover:text-white transition-colors hidden md:block">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Image preview -->
            <template x-if="modalFile && modalFile.category === 'image'">
                <img :src="modalFile.preview_url" :alt="modalFile.name" class="preview-img" loading="lazy">
            </template>

            <!-- Video preview -->
            <template x-if="modalFile && modalFile.category === 'video'">
                <video controls autoplay x-ref="vaultVideo" class="max-w-full">
                    <source :src="modalFile.preview_url" :type="modalFile.mime">
                    Your browser does not support the video tag.
                </video>
            </template>

            <!-- Audio preview -->
            <template x-if="modalFile && modalFile.category === 'audio'">
                <div class="flex flex-col items-center gap-4 p-8">
                    <div class="flex h-28 w-28 items-center justify-center rounded-3xl bg-sky-500/20 border border-sky-500/40">
                        <svg class="h-14 w-14 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                    <p class="text-white font-semibold" x-text="modalFile.name"></p>
                    <audio controls autoplay class="w-full max-w-sm">
                        <source :src="modalFile.preview_url" :type="modalFile.mime">
                    </audio>
                </div>
            </template>

            <!-- File info + download -->
            <template x-if="modalFile">
                <div class="mt-4 flex items-center justify-between w-full px-1">
                    <div>
                        <p class="text-sm font-semibold text-white" x-text="modalFile.name"></p>
                        <p class="text-xs text-slate-400" x-text="modalFile.human_size + ' · ' + modalFile.modified_human"></p>
                    </div>
                    <a :href="modalFile.download_url" download
                       class="flex items-center gap-2 rounded-xl bg-sky-500/20 border border-sky-500/40 px-4 py-2 text-xs font-semibold text-sky-300 hover:bg-sky-500/30 transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download
                    </a>
                </div>
            </template>
        </div>
    </div>

    <!-- UPLOAD PROGRESS MODAL -->
    <div x-show="isUploading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="w-80 rounded-3xl border border-[#2d3748] bg-[#0d1117] p-6 text-center">
            <div class="mb-4 text-sky-400">
                <svg class="h-10 w-10 mx-auto animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-white">Encrypting & uploading…</p>
            <p class="mt-1 text-xs text-slate-400" x-text="uploadingFileName"></p>
        </div>
    </div>

    <!-- ── MAIN LAYOUT ─────────────────────────────────────────────────────── -->
    <div class="min-h-screen flex flex-col">

        <!-- TOP BAR -->
        <header class="sticky top-0 z-30 flex items-center justify-between px-6 py-4 border-b border-[#1e2d3d] bg-[#070a0f]/90 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-xl flex items-center justify-center bg-sky-500/20 border border-sky-500/30">
                    <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-200">Vault</span>
                <span class="text-xs text-slate-500">·</span>
                <span class="text-xs text-slate-500" x-text="files.length + ' file' + (files.length !== 1 ? 's' : '')"></span>
            </div>

            <div class="flex items-center gap-3">
                <!-- Upload button -->
                <label class="flex items-center gap-2 rounded-xl bg-sky-500/20 border border-sky-500/40 px-4 py-2 text-xs font-semibold text-sky-300 hover:bg-sky-500/30 transition-colors cursor-pointer">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload
                    <input type="file" class="hidden" multiple @change="handleFileInput($event)">
                </label>

                <!-- Lock / Exit -->
                <form method="POST" action="{{ route('logout') }}" id="vault-exit-form">
                    @csrf
                </form>
                <button @click="lockVault()"
                        class="flex items-center gap-2 rounded-xl border border-[#2d3748] bg-[#0d1117] px-4 py-2 text-xs font-semibold text-slate-400 hover:text-slate-200 hover:border-slate-500 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Lock
                </button>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="flex-1 p-6">

            @if (count($files) === 0)
            <!-- Empty state -->
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <div class="upload-zone flex flex-col items-center justify-center p-16 w-full max-w-lg mx-auto"
                     :class="isDragging ? 'drag-over' : ''">
                    <div class="h-20 w-20 rounded-3xl flex items-center justify-center bg-sky-500/10 border border-sky-500/20 mb-5">
                        <svg class="h-10 w-10 text-sky-400/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-slate-300 mb-2">Vault is empty</h2>
                    <p class="text-sm text-slate-500 mb-6">Drop files here or use the Upload button to encrypt &amp; store them.</p>
                    <label class="flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-sky-400 transition-colors cursor-pointer">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Choose Files
                        <input type="file" class="hidden" multiple @change="handleFileInput($event)">
                    </label>
                </div>
            </div>
            @else
            <!-- File grid -->
            <div class="vault-grid">
                @foreach ($files as $i => $file)
                <div class="vault-card group"
                     @click="openFile({{ $i }})"
                     data-index="{{ $i }}">
                    
                    <!-- Thumbnail / Preview -->
                    <div class="relative" style="aspect-ratio:4/3; overflow:hidden;">
                        @if ($file['category'] === 'image')
                            <img src="{{ $file['preview_url'] }}"
                                 alt="{{ $file['name'] }}"
                                 class="vault-thumb group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        @elseif ($file['category'] === 'video')
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #0d1a2a, #0a1220);">
                                <svg class="h-12 w-12 text-sky-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div class="play-overlay">
                                    <div class="h-12 w-12 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                        <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M8 5v14l11-7z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        @elseif ($file['category'] === 'audio')
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #1a0d2a, #120a1a);">
                                <svg class="h-12 w-12 text-purple-400/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                            </div>
                        @else
                            <div class="w-full h-full flex items-center justify-center"
                                 style="background: linear-gradient(135deg, #0d1117, #131a26);">
                                <svg class="h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="p-3">
                        <p class="truncate text-xs font-semibold text-slate-200 group-hover:text-sky-300 transition-colors"
                           title="{{ $file['name'] }}">{{ $file['name'] }}</p>
                        <div class="mt-1 flex items-center justify-between text-[10px] text-slate-500">
                            <span>{{ $file['human_size'] }}</span>
                            <span>{{ $file['modified_human'] }}</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

        </main>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="transform translate-y-4 opacity-0"
                 x-transition:enter-end="transform translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-end="transform translate-y-4 opacity-0"
                 class="pointer-events-auto flex w-80 items-start gap-3 rounded-2xl border border-[#2d3748] bg-[#0d1117] p-4 shadow-2xl">
                <div class="flex-1">
                    <p class="text-xs font-bold text-white" x-text="toast.title"></p>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="toast.message"></p>
                </div>
                <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="text-slate-500 hover:text-white">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

</div>

<script>
const FILES = @json($files);

function vaultApp() {
    return {
        files: FILES,
        previewableFiles: FILES.filter(f => ['image','video','audio'].includes(f.category)),
        showModal: false,
        modalFile: null,
        previewIndex: 0,
        isDragging: false,
        isUploading: false,
        uploadingFileName: '',
        toasts: [],

        openFile(index) {
            const file = this.files[index];
            if (file.preview_url) {
                this.modalFile = file;
                this.previewIndex = this.previewableFiles.findIndex(f => f.name === file.name);
                this.showModal = true;
            } else {
                // Non-previewable: trigger download
                window.location.href = file.download_url;
            }
        },

        closeModal() {
            // Pause video if any
            const v = this.$refs.vaultVideo;
            if (v) { v.pause(); }
            this.showModal = false;
            this.modalFile = null;
        },

        prevFile() {
            if (this.previewIndex > 0) {
                this.previewIndex--;
                this.modalFile = this.previewableFiles[this.previewIndex];
            }
        },

        nextFile() {
            if (this.previewIndex < this.previewableFiles.length - 1) {
                this.previewIndex++;
                this.modalFile = this.previewableFiles[this.previewIndex];
            }
        },

        lockVault() {
            fetch('/secret/lock', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } })
                .finally(() => window.location.href = '/secret');
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = Array.from(e.dataTransfer.files);
            if (files.length) this.uploadFiles(files);
        },

        handleFileInput(e) {
            const files = Array.from(e.target.files);
            if (files.length) this.uploadFiles(files);
        },

        async uploadFiles(files) {
            for (const file of files) {
                this.isUploading = true;
                this.uploadingFileName = file.name;
                try {
                    const fd = new FormData();
                    fd.append('file', file);
                    const res = await fetch('/secret/upload', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: fd,
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showToast('Encrypted', data.message, 'success');
                    } else {
                        this.showToast('Upload Failed', data.message, 'error');
                    }
                } catch (err) {
                    this.showToast('Error', err.message, 'error');
                } finally {
                    this.isUploading = false;
                    this.uploadingFileName = '';
                }
            }
            // Reload to reflect new files
            window.location.reload();
        },

        showToast(title, message, type = 'info') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, title, message, type });
            setTimeout(() => this.toasts = this.toasts.filter(t => t.id !== id), 4500);
        },
    }
}
</script>

</body>
</html>
