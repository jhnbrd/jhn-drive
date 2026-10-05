@extends('layouts.app')

@section('title', ($isFolder ? ($folderData['name'] . ' (Shared Folder)') : ($fileData['name'] . ' (Shared File)')) . ' | JHN Drive')

@section('content')
<div x-data="{
    copied: false,
    previewModal: false,
    previewSrc: '',
    previewType: '',
    previewTitle: '',
    openPreview(src, type, title) {
        this.previewSrc = src;
        this.previewType = type;
        this.previewTitle = title;
        this.previewModal = true;
    }
}" class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:px-6 lg:px-8 bg-[#0b0e14] text-[#f0f6fc]">
    

    @if($isFolder)
        <!-- Shared Folder View -->
        <div class="relative w-full max-w-4xl rounded-3xl border border-[#3b4b66] bg-[#161d2a] shadow-2xl shadow-black/80">
            <!-- Header -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#3b4b66] px-6 py-5 bg-[#0e1420] rounded-t-3xl">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-400/15 text-amber-300">
                        <svg class="h-6 w-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-bold text-white tracking-tight">{{ $folderData['name'] }}</h1>
                            <span class="rounded-lg bg-amber-500/20 px-2.5 py-0.5 text-xs font-bold text-amber-300 border border-amber-500/40">Shared Folder</span>
                        </div>
                        <p class="text-xs text-slate-300">{{ $folderData['total_items'] }} {{ Str::plural('item', $folderData['total_items']) }} • Shared via JHN Drive</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2.5">
                    <a href="{{ $folderData['zip_url'] }}" 
                       class="flex items-center gap-2 rounded-xl bg-sky-500 px-4 py-2.5 text-xs font-extrabold text-slate-950 shadow-lg shadow-sky-500/25 hover:bg-sky-400 transition-all">
                        <svg class="h-4 w-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Download as ZIP</span>
                    </a>
                </div>
            </div>

            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 border-b border-[#3b4b66] bg-[#0e1420] px-6 py-3 text-xs text-slate-300 overflow-x-auto font-medium">
                <a href="{{ route('share.show', ['token' => $folderData['token']]) }}" class="hover:text-sky-300 font-bold transition-colors">
                    Root
                </a>
                @foreach($folderData['breadcrumbs'] as $idx => $crumb)
                    @if($idx > 0)
                        <span class="text-slate-400 font-bold">/</span>
                        <a href="{{ route('share.show', ['token' => $folderData['token'], 'path' => $crumb['path']]) }}" 
                           class="{{ $loop->last ? 'text-white font-bold' : 'hover:text-sky-300 transition-colors' }}">
                            {{ $crumb['name'] }}
                        </a>
                    @endif
                @endforeach
            </div>

            <!-- Folder Contents Table -->
            <div class="divide-y divide-[#2d3a50] max-h-[60vh] overflow-y-auto">
                @if(count($folderData['folders']) === 0 && count($folderData['files']) === 0)
                    <div class="py-16 text-center text-slate-400">
                        <svg class="mx-auto h-12 w-12 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <p class="mt-3 text-sm">This folder is empty.</p>
                    </div>
                @else
                    <!-- Subfolders -->
                    @foreach($folderData['folders'] as $folder)
                        <div class="flex items-center justify-between px-6 py-3.5 hover:bg-[#182333] transition-colors group">
                            <a href="{{ route('share.show', ['token' => $folderData['token'], 'path' => $folder['subpath']]) }}" 
                               class="flex items-center gap-3 min-w-0 flex-1">
                                <svg class="h-6 w-6 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                                <span class="text-sm font-bold text-white truncate group-hover:text-sky-300 transition-colors">{{ $folder['name'] }}</span>
                            </a>
                            <div class="flex items-center gap-4 text-xs text-slate-300 font-medium">
                                <span>{{ $folder['human_size'] }}</span>
                                <span class="hidden sm:inline">{{ $folder['modified_at'] }}</span>
                            </div>
                        </div>
                    @endforeach

                    <!-- Files -->
                    @foreach($folderData['files'] as $file)
                        <div class="flex items-center justify-between px-6 py-3.5 hover:bg-[#182333] transition-colors group">
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                @if($file['category'] === 'image')
                                    <svg class="h-6 w-6 text-sky-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                @elseif($file['category'] === 'video')
                                    <svg class="h-6 w-6 text-purple-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                @else
                                    <svg class="h-6 w-6 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                @endif

                                <div class="truncate">
                                    <span class="text-sm font-bold text-white truncate block">{{ $file['name'] }}</span>
                                    <span class="text-xs text-slate-300 font-medium">{{ $file['human_size'] }} • {{ $file['modified_at'] }}</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if($file['preview_url'])
                                    <button @click="openPreview('{{ $file['preview_url'] }}', '{{ $file['category'] }}', '{{ $file['name'] }}')" 
                                            class="rounded-xl border border-sky-500/50 bg-[#162338] px-3.5 py-1.5 text-xs font-bold text-sky-300 hover:bg-sky-500 hover:text-slate-950 hover:border-sky-400 transition-all shadow-sm">
                                        Preview
                                    </button>
                                @endif
                                <a href="{{ $file['download_url'] }}" 
                                   class="rounded-xl border border-[#3b4b66] bg-[#1a2536] px-3.5 py-1.5 text-xs font-bold text-slate-200 hover:bg-[#263750] hover:text-white hover:border-slate-400 transition-all flex items-center gap-1.5 shadow-sm">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between border-t border-[#3b4b66] bg-[#0e1420] px-6 py-4 rounded-b-3xl text-xs text-slate-300">
                <span class="font-medium">{{ $folderData['downloads_count'] }} {{ Str::plural('download', $folderData['downloads_count']) }} recorded</span>
                <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)" 
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[#3b4b66] bg-[#1a2536] px-3.5 py-1.5 text-xs font-bold text-slate-200 hover:border-sky-400 hover:text-sky-300 transition-colors shadow-sm">
                    <span x-show="!copied">Copy Folder Link</span>
                    <span x-show="copied" class="text-emerald-400">✓ Link copied!</span>
                </button>
            </div>
        </div>

    @else
        <!-- Single Shared File View -->
        <div class="relative w-full max-w-xl rounded-3xl border border-[#3b4b66] bg-[#161d2a] p-8 shadow-2xl shadow-black/80">
            <!-- Header Branding -->
            <div class="flex items-center justify-between border-b border-[#3b4b66] pb-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-400 text-slate-950">
                        <svg class="h-5 w-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h10a4 4 0 004-4 4 4 0 00-3-3.87 5 5 0 00-9.6-1.5A4 4 0 003 15z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold tracking-tight text-white">JHN Drive</h2>
                        <p class="text-xs text-slate-300">Public File Share</p>
                    </div>
                </div>

                <!-- Total Downloads Pill -->
                <div class="flex items-center gap-1.5 rounded-full bg-[#1a2536] border border-[#3b4b66] px-3 py-1 text-xs text-slate-200 font-medium">
                    <svg class="h-3.5 w-3.5 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>{{ $fileData['downloads_count'] }} {{ Str::plural('download', $fileData['downloads_count']) }}</span>
                </div>
            </div>

            <!-- Media Preview Section -->
            @if($fileData['category'] === 'image' && $fileData['preview_url'])
                <div class="my-6 overflow-hidden rounded-2xl border border-[#3b4b66] bg-black/50 flex items-center justify-center max-h-80 shadow-inner">
                    <img src="{{ $fileData['preview_url'] }}" 
                         alt="{{ $fileData['name'] }}" 
                         class="max-h-80 w-full object-contain cursor-pointer hover:scale-105 transition-transform"
                         @click="openPreview('{{ $fileData['preview_url'] }}', 'image', '{{ $fileData['name'] }}')">
                </div>
            @elseif($fileData['category'] === 'video' && $fileData['preview_url'])
                <div class="my-6 overflow-hidden rounded-2xl border border-[#3b4b66] bg-black shadow-inner">
                    <video controls playsinline preload="metadata" class="w-full max-h-80">
                        <source src="{{ $fileData['preview_url'] }}" type="{{ $fileData['mime_type'] }}">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @else
                <!-- Category Icon for non-media -->
                <div class="flex flex-col items-center py-6 text-center">
                    <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-[#0b0e14] border border-[#3b4b66] shadow-inner">
                        @if($fileData['category'] === 'audio')
                            <svg class="h-10 w-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                            </svg>
                        @elseif($fileData['category'] === 'pdf')
                            <svg class="h-10 w-10 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        @elseif($fileData['category'] === 'archive')
                            <svg class="h-10 w-10 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        @else
                            <svg class="h-10 w-10 text-sky-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        @endif
                    </div>
                </div>
            @endif

            <!-- File Details -->
            <div class="text-center">
                <h1 class="text-lg font-bold text-white break-all px-2" title="{{ $fileData['name'] }}">
                    {{ $fileData['name'] }}
                </h1>
                <div class="mt-2.5 flex items-center justify-center gap-2 text-xs text-slate-300">
                    <span class="rounded-lg bg-[#0e1420] border border-[#3b4b66] px-3 py-1 font-bold text-white">
                        {{ $fileData['human_size'] }}
                    </span>
                    @if(!empty($fileData['extension']))
                        <span class="rounded-lg bg-[#0e1420] border border-[#3b4b66] px-3 py-1 uppercase font-mono font-bold text-sky-400">
                            .{{ $fileData['extension'] }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-xs text-slate-300 font-medium">Shared on {{ $fileData['modified_at'] }}</p>
            </div>

            <!-- Download Button -->
            <div class="mt-6">
                <a href="{{ $fileData['download_url'] }}" 
                   class="group flex w-full items-center justify-center gap-3 rounded-xl bg-sky-500 px-6 py-3.5 text-base font-extrabold text-slate-950 shadow-lg shadow-sky-500/25 hover:bg-sky-400 transition-all">
                    <svg class="h-5 w-5 stroke-2 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Download File</span>
                </a>
            </div>

            <!-- Copy Link -->
            <div class="mt-5 text-center">
                <button @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)" 
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[#3b4b66] bg-[#1a2536] px-4 py-2 text-xs font-bold text-slate-200 hover:border-sky-400 hover:text-sky-300 transition-colors shadow-sm">
                    <span x-show="!copied">Copy share link</span>
                    <span x-show="copied" class="text-emerald-400 font-bold">✓ Share link copied!</span>
                </button>
            </div>
        </div>
    @endif

    <!-- Preview Lightbox Modal -->
    <div x-show="previewModal" 
         x-cloak 
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
         @keydown.escape.window="previewModal = false">
        <div @click.away="previewModal = false" class="relative max-w-5xl w-full bg-[#161d2a] border border-[#3b4b66] rounded-3xl overflow-hidden shadow-2xl">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#3b4b66] bg-[#0e1420]">
                <h3 class="text-sm font-bold text-white truncate max-w-lg" x-text="previewTitle"></h3>
                <button @click="previewModal = false" 
                        class="rounded-xl border border-[#3b4b66] bg-[#1a2536] p-2 text-slate-200 hover:bg-rose-600 hover:border-rose-500 hover:text-white transition-colors shadow-sm"
                        title="Close">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="flex items-center justify-center p-4 min-h-[300px] max-h-[75vh] bg-black/60">
                <template x-if="previewType === 'image'">
                    <img :src="previewSrc" :alt="previewTitle" class="max-h-[70vh] w-auto object-contain rounded-xl">
                </template>
                <template x-if="previewType === 'video'">
                    <video :src="previewSrc" controls autoplay class="max-h-[70vh] w-full rounded-xl bg-black"></video>
                </template>
            </div>
        </div>
    </div>

    <!-- Footer Note -->
    <div class="mt-8 text-center text-xs text-slate-400 font-medium">
        Hosted with JHN Drive • Dedicated local port 8088
    </div>

</div>
@endsection

