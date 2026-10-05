@extends('layouts.app')

@section('title', 'JHN Drive | Minimalist Cloud Storage')

@section('content')
<div x-data="driveApp()" 
     x-init="init()" 
     @dragover.prevent="isDragging = true" 
     @dragleave.prevent="onDragLeave($event)" 
     @drop.prevent="handleDrop($event)"
     @keydown.escape.window="mobileSidebarOpen = false; showPreviewModal = false; showNewFolderModal = false; showRenameModal = false; showDeleteModal = false; closeItemDetails(); activeMenu = null"
     class="drive-shell relative flex h-[100dvh] w-full overflow-hidden">

    @include('drive.partials.drag-overlay')
    @include('drive.partials.sidebar')
    {{--
                    <span class="font-medium text-[#cbd5e1]">Port 8088 • Ready</span>
    --}}
    @include('drive.partials.main-viewport')
    @include('drive.partials.overlays')
    @include('drive.partials.details-dialog')
</div>
@endsection
