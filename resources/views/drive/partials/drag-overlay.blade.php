    <!-- ========================================================================= -->
    <!-- DRAG AND DROP OVERLAY -->
    <!-- ========================================================================= -->
    <div x-show="isDragging" 
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-[#0b0e14]/90 backdrop-blur-md border-4 border-dashed border-sky-400 pointer-events-none">
        <div class="text-center">
            <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-3xl bg-sky-500/20 text-sky-400 border border-sky-400/40 shadow-2xl shadow-sky-500/20 animate-bounce">
                <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
            <h3 class="mt-5 text-2xl font-bold text-white">Drop files to upload</h3>
            <p class="mt-2 text-sm text-[#94a3b8]">Files will be stored in your isolated 20 GB drive at <span class="font-mono text-sky-400" x-text="'/' + currentPath"></span></p>
        </div>
    </div>

