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
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.25rem;
        }

        /* File card */
        .vault-card {
            border-radius: 16px;
            border: 1px solid #1e2d3d;
            background: rgba(13, 20, 33, 0.85);
            overflow: hidden;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(8px);
        }
        .vault-card:hover {
            border-color: rgba(56, 189, 248, 0.45);
            transform: translateY(-3px);
            box-shadow: 0 12px 36px rgba(56, 189, 248, 0.15);
        }

        /* Thumbnail */
        .vault-thumb {
            width: 100%;
            height: 100%;
            aspect-ratio: 16/10;
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
            background: rgba(0,0,0,0.35);
            transition: background 0.2s ease, transform 0.2s ease;
        }
        .vault-card:hover .play-overlay {
            background: rgba(0,0,0,0.20);
        }

        /* Modal */
        .modal-bg {
            position: fixed;
            inset: 0;
            z-index: 60;
            background: rgba(0,0,0,0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            backdrop-filter: blur(14px);
        }

        /* Video player */
        video.vault-player {
            border-radius: 16px;
            max-height: 80vh;
            max-width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
            background: #000;
        }
        img.preview-img {
            border-radius: 16px;
            max-height: 82vh;
            max-width: 100%;
            object-fit: contain;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
        }

        /* Upload zone */
        .upload-zone {
            border: 2px dashed #2d3748;
            border-radius: 20px;
            background: rgba(14,20,30,0.6);
            transition: all 0.2s ease;
        }
        .upload-zone.drag-over {
            border-color: #38bdf8;
            background: rgba(56,189,248,0.08);
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #070a0f; }
        ::-webkit-scrollbar-thumb { background: #1e2d3d; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #38bdf8; }
    </style>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body>
<div x-data="vaultApp()"
     x-init="initApp()"
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
            <h3 class="mt-4 text-xl font-bold text-white">Drop to encrypt & store in vault</h3>
            <p class="text-sm text-slate-400 mt-1">Automatic poster thumbnail will be generated</p>
        </div>
    </div>

    <!-- MEDIA PREVIEW MODAL -->
    <div x-show="showModal" x-cloak @click.self="closeModal()"
         class="modal-bg"
         @keydown.escape.window="closeModal()"
         @keydown.arrow-left.window="prevFile()"
         @keydown.arrow-right.window="nextFile()"
         @keydown.space.window="toggleVideoPlay($event)">
        <div class="relative w-full flex flex-col items-center max-w-5xl">
            
            <!-- Top Controls Bar -->
            <div class="absolute -top-12 right-0 flex items-center gap-3 z-10">
                <span class="text-xs font-mono text-slate-400 bg-slate-900/80 px-2.5 py-1 rounded-lg border border-slate-700/50">
                    <span x-text="previewIndex + 1"></span> / <span x-text="previewableFiles.length"></span>
                </span>
                <button @click="closeModal()"
                        class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800/80 transition-colors"
                        title="Close (Esc)">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Prev / Next Navigation Buttons -->
            <button x-show="previewIndex > 0" x-cloak @click="prevFile()"
                    class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-16 text-slate-400 hover:text-white bg-slate-900/80 p-3 rounded-full border border-slate-800 hover:border-sky-500/50 transition-all hidden md:flex items-center justify-center shadow-2xl"
                    title="Previous (Left Arrow)">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <button x-show="previewIndex < previewableFiles.length - 1" x-cloak @click="nextFile()"
                    class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-16 text-slate-400 hover:text-white bg-slate-900/80 p-3 rounded-full border border-slate-800 hover:border-sky-500/50 transition-all hidden md:flex items-center justify-center shadow-2xl"
                    title="Next (Right Arrow)">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <!-- Image preview -->
            <template x-if="modalFile && modalFile.category === 'image'">
                <img :src="modalFile.preview_url" :alt="modalFile.name" class="preview-img" loading="eager">
            </template>

            <!-- Optimized Video preview player with fast Range stream -->
            <template x-if="modalFile && modalFile.category === 'video'">
                <div class="relative w-full flex flex-col items-center">
                    <video controls
                           autoplay
                           preload="auto"
                           playsinline
                           x-ref="vaultVideo"
                           :poster="videoThumbnails[modalFile.name] || (modalFile.has_thumb ? modalFile.thumbnail_url : '')"
                           class="vault-player w-full max-h-[75vh]">
                        <source :src="modalFile.preview_url" :type="modalFile.mime">
                        Your browser does not support the video tag.
                    </video>
                    <!-- Video Shortcuts Hint -->
                    <div class="mt-2 flex items-center gap-4 text-[11px] text-slate-400">
                        <span><kbd class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 text-slate-300">Space</kbd> Play/Pause</span>
                        <span><kbd class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 text-slate-300">←</kbd> <kbd class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 text-slate-300">→</kbd> Seek ±5s</span>
                        <span><kbd class="bg-slate-800 px-1.5 py-0.5 rounded border border-slate-700 text-slate-300">Esc</kbd> Close</span>
                    </div>
                </div>
            </template>

            <!-- Audio preview -->
            <template x-if="modalFile && modalFile.category === 'audio'">
                <div class="flex flex-col items-center gap-4 p-8 bg-[#0d1421] rounded-2xl border border-[#1e2d3d] w-full max-w-lg shadow-2xl">
                    <div class="flex h-24 w-24 items-center justify-center rounded-3xl bg-purple-500/20 border border-purple-500/40">
                        <svg class="h-12 w-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                        </svg>
                    </div>
                    <p class="text-white font-semibold text-center truncate max-w-sm" x-text="modalFile.name"></p>
                    <audio controls autoplay preload="metadata" class="w-full">
                        <source :src="modalFile.preview_url" :type="modalFile.mime">
                    </audio>
                </div>
            </template>

            <!-- File info + download -->
            <template x-if="modalFile">
                <div class="mt-4 flex items-center justify-between w-full px-2">
                    <div class="min-w-0 flex-1 pr-4">
                        <p class="text-sm font-semibold text-white truncate" x-text="modalFile.name"></p>
                        <p class="text-xs text-slate-400" x-text="modalFile.human_size + ' · ' + modalFile.modified_human"></p>
                    </div>
                    <a :href="modalFile.download_url" download
                       class="flex items-center gap-2 rounded-xl bg-sky-500/20 border border-sky-500/40 px-4 py-2 text-xs font-semibold text-sky-300 hover:bg-sky-500/30 transition-colors shadow-lg">
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
    <div x-show="isUploading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="w-88 rounded-3xl border border-[#2d3748] bg-[#0d1117] p-6 text-center shadow-2xl">
            <div class="mb-4 text-sky-400">
                <svg class="h-10 w-10 mx-auto animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-white" x-text="uploadProgressText || 'Encrypting & uploading...'"></p>
            <p class="mt-1 text-xs text-slate-400 truncate px-2" x-text="uploadingFileName"></p>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="min-h-screen flex flex-col">

        <!-- TOP BAR -->
        <header class="sticky top-0 z-30 flex items-center justify-between px-6 py-4 border-b border-[#1e2d3d] bg-[#070a0f]/90 backdrop-blur-md">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-xl flex items-center justify-center bg-sky-500/20 border border-sky-500/30 shadow-sm">
                    <svg class="h-4 w-4 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <span class="text-sm font-bold text-slate-200">Secret Vault</span>
                <span class="text-xs text-slate-500">·</span>
                <span class="text-xs text-slate-400 font-mono" x-text="files.length + ' item' + (files.length !== 1 ? 's' : '')"></span>
            </div>

            <div class="flex items-center gap-3">
                <!-- Upload button -->
                <label class="flex items-center gap-2 rounded-xl bg-sky-500/20 border border-sky-500/40 px-4 py-2 text-xs font-semibold text-sky-300 hover:bg-sky-500/30 transition-colors cursor-pointer shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    Upload
                    <input type="file" class="hidden" multiple @change="handleFileInput($event)">
                </label>

                <!-- Lock Vault -->
                <button @click="lockVault()"
                        class="flex items-center gap-2 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-2 text-xs font-semibold text-rose-300 hover:bg-rose-500/20 hover:border-rose-500/50 transition-colors shadow-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Lock Vault
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
                    <p class="text-sm text-slate-500 mb-6">Drop files here or click Choose Files to encrypt and store them securely.</p>
                    <label class="flex items-center gap-2 rounded-xl bg-sky-500 px-5 py-2.5 text-xs font-bold text-slate-950 hover:bg-sky-400 transition-colors cursor-pointer shadow-lg shadow-sky-500/20">
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
                    
                    <!-- Thumbnail / Preview Container -->
                    <div class="relative bg-[#0a0f18]" style="aspect-ratio:16/10; overflow:hidden;">
                        @if ($file['category'] === 'image')
                            <!-- Image Thumbnail (Cached lightweight image) -->
                            <img src="{{ $file['thumbnail_url'] ?? $file['preview_url'] }}"
                                 alt="{{ $file['name'] }}"
                                 class="vault-thumb group-hover:scale-105 transition-transform duration-300"
                                 loading="lazy">
                        @elseif ($file['category'] === 'video')
                            <!-- Video Thumbnail Display (Server poster or Dynamic Client Frame Capture) -->
                            @if ($file['has_thumb'] && !empty($file['thumbnail_url']))
                                <img src="{{ $file['thumbnail_url'] }}"
                                     alt="{{ $file['name'] }}"
                                     class="vault-thumb group-hover:scale-105 transition-transform duration-300"
                                     loading="lazy">
                            @else
                                <template x-if="videoThumbnails['{{ addslashes($file['name']) }}']">
                                    <img :src="videoThumbnails['{{ addslashes($file['name']) }}']"
                                         alt="{{ $file['name'] }}"
                                         class="vault-thumb group-hover:scale-105 transition-transform duration-300">
                                </template>
                                <template x-if="!videoThumbnails['{{ addslashes($file['name']) }}']">
                                    <div class="w-full h-full flex items-center justify-center"
                                         style="background: linear-gradient(135deg, #0d1a2a, #0a1220);">
                                        <svg class="h-10 w-10 text-sky-400/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                </template>
                            @endif
                            <!-- Play Overlay Badge -->
                            <div class="play-overlay">
                                <div class="h-11 w-11 rounded-full bg-black/40 border border-white/20 flex items-center justify-center backdrop-blur-md group-hover:scale-110 group-hover:bg-sky-500 group-hover:border-sky-400 transition-all duration-200">
                                    <svg class="h-5 w-5 text-white ml-0.5 group-hover:text-slate-950" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <span class="absolute bottom-2 right-2 px-1.5 py-0.5 rounded bg-black/70 text-[9px] font-mono text-white font-bold border border-white/10 uppercase">
                                {{ $file['ext'] }}
                            </span>
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

                    <!-- Footer Details -->
                    <div class="p-3 bg-[#0d1421]">
                        <p class="truncate text-xs font-semibold text-slate-200 group-hover:text-sky-300 transition-colors"
                           title="{{ $file['name'] }}">{{ $file['name'] }}</p>
                        <div class="mt-1 flex items-center justify-between text-[10px] text-slate-500 font-mono">
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
                 class="pointer-events-auto flex items-center gap-3 rounded-2xl border px-4 py-3 shadow-2xl backdrop-blur-md max-w-sm"
                 :class="{
                     'bg-[#0d2137]/90 border-sky-500/40 text-sky-200': toast.type === 'success',
                     'bg-[#2d1217]/90 border-rose-500/40 text-rose-200': toast.type === 'error',
                     'bg-[#161d2a]/90 border-slate-700 text-slate-200': toast.type === 'info',
                 }">
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold" x-text="toast.title"></p>
                    <p class="text-[11px] opacity-80 truncate" x-text="toast.message"></p>
                </div>
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
        uploadProgressText: '',
        toasts: [],
        videoThumbnails: {},

        initApp() {
            this.loadCachedThumbnails();
            this.generateMissingVideoThumbnails();
        },

        loadCachedThumbnails() {
            try {
                const cached = localStorage.getItem('vault_video_thumbs');
                if (cached) {
                    this.videoThumbnails = JSON.parse(cached);
                }
            } catch (e) {
                console.warn('Failed to parse cached video thumbnails', e);
            }
        },

        saveCachedThumbnail(fileName, dataUrl) {
            this.videoThumbnails[fileName] = dataUrl;
            try {
                localStorage.setItem('vault_video_thumbs', JSON.stringify(this.videoThumbnails));
            } catch (e) {
                // Storage quota exceeded or disabled
            }
        },

        // Client-side automatic frame extractor for videos without thumbnails
        generateMissingVideoThumbnails() {
            const videosToCapture = this.files.filter(f => 
                f.category === 'video' && 
                !f.has_thumb && 
                !this.videoThumbnails[f.name] &&
                f.preview_url
            );

            if (!videosToCapture.length) return;

            let queue = [...videosToCapture];
            const processNext = () => {
                if (!queue.length) return;
                const file = queue.shift();
                this.captureVideoFrame(file.preview_url, 1.0)
                    .then(thumbDataUrl => {
                        if (thumbDataUrl) {
                            this.saveCachedThumbnail(file.name, thumbDataUrl);
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        setTimeout(processNext, 200);
                    });
            };

            setTimeout(processNext, 150);
        },

        captureVideoFrame(videoUrl, seekTime = 1.0) {
            return new Promise((resolve, reject) => {
                const video = document.createElement('video');
                video.src = videoUrl;
                video.crossOrigin = 'anonymous';
                video.muted = true;
                video.preload = 'metadata';

                let timeout = setTimeout(() => {
                    cleanup();
                    reject('timeout');
                }, 8000);

                const cleanup = () => {
                    clearTimeout(timeout);
                    video.removeAttribute('src');
                    video.load();
                };

                video.onloadedmetadata = () => {
                    const targetTime = Math.min(seekTime, (video.duration || 2) / 2);
                    video.currentTime = targetTime;
                };

                video.onseeked = () => {
                    try {
                        const canvas = document.createElement('canvas');
                        const maxDim = 320;
                        let w = video.videoWidth || 320;
                        let h = video.videoHeight || 180;
                        if (w > maxDim) {
                            h = Math.round((h / w) * maxDim);
                            w = maxDim;
                        }
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(video, 0, 0, w, h);
                        const dataUrl = canvas.toDataURL('image/jpeg', 0.75);
                        cleanup();
                        resolve(dataUrl);
                    } catch (e) {
                        cleanup();
                        reject(e);
                    }
                };

                video.onerror = () => {
                    cleanup();
                    reject('video error');
                };
            });
        },

        openFile(index) {
            const file = this.files[index];
            if (file.preview_url) {
                this.modalFile = file;
                this.previewIndex = this.previewableFiles.findIndex(f => f.name === file.name);
                this.showModal = true;
            } else {
                window.location.href = file.download_url;
            }
        },

        closeModal() {
            const v = this.$refs.vaultVideo;
            if (v) { v.pause(); }
            this.showModal = false;
            this.modalFile = null;
        },

        prevFile() {
            if (this.previewIndex > 0) {
                const v = this.$refs.vaultVideo;
                if (v) { v.pause(); }
                this.previewIndex--;
                this.modalFile = this.previewableFiles[this.previewIndex];
            }
        },

        nextFile() {
            if (this.previewIndex < this.previewableFiles.length - 1) {
                const v = this.$refs.vaultVideo;
                if (v) { v.pause(); }
                this.previewIndex++;
                this.modalFile = this.previewableFiles[this.previewIndex];
            }
        },

        toggleVideoPlay(e) {
            if (!this.showModal || !this.modalFile || this.modalFile.category !== 'video') return;
            const v = this.$refs.vaultVideo;
            if (!v) return;
            if (e) e.preventDefault();
            if (v.paused) {
                v.play();
            } else {
                v.pause();
            }
        },

        lockVault() {
            fetch('/secret/lock', { 
                method: 'POST', 
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } 
            }).finally(() => {
                window.location.href = '/secret';
            });
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
                this.uploadProgressText = 'Encrypting & uploading...';

                try {
                    let thumbnailData = null;

                    if (file.type.startsWith('video/')) {
                        this.uploadProgressText = 'Generating video thumbnail...';
                        try {
                            const blobUrl = URL.createObjectURL(file);
                            thumbnailData = await this.captureVideoFrame(blobUrl, 1.0);
                            URL.revokeObjectURL(blobUrl);
                        } catch (thumbErr) {
                            console.warn('Could not generate client video thumbnail', thumbErr);
                        }
                    }

                    this.uploadProgressText = 'Encrypting and storing...';
                    const fd = new FormData();
                    fd.append('file', file);
                    if (thumbnailData) {
                        fd.append('thumbnail', thumbnailData);
                    }

                    const res = await fetch('/secret/upload', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: fd,
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (thumbnailData && data.file) {
                            this.saveCachedThumbnail(data.file, thumbnailData);
                        }
                        this.showToast('Encrypted', data.message, 'success');
                    } else {
                        this.showToast('Upload Failed', data.message, 'error');
                    }
                } catch (err) {
                    this.showToast('Error', err.message, 'error');
                } finally {
                    this.isUploading = false;
                    this.uploadingFileName = '';
                    this.uploadProgressText = '';
                }
            }
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