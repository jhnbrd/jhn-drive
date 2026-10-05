@extends('layouts.app')

@section('title', 'JHN Drive | Minimalist Cloud Storage')

@section('content')
<div x-data="driveApp()" 
     x-init="init()" 
     @dragover.prevent="isDragging = true" 
     @dragleave.prevent="onDragLeave($event)" 
     @drop.prevent="handleDrop($event)"
     @keydown.escape.window="showPreviewModal = false; showNewFolderModal = false; showDeleteModal = false"
     class="flex h-screen w-screen overflow-hidden bg-[#0b0e14] text-[#f8fafc]">

    @include('drive.partials.drag-overlay')
    @include('drive.partials.sidebar')
    {{--
                    <span class="font-medium text-[#cbd5e1]">Port 8088 • Ready</span>
    --}}
    @include('drive.partials.main-viewport')
    @include('drive.partials.overlays')
</div>
@endsection
