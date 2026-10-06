    <!-- ========================================================================= -->
    <!-- MAIN VIEWPORT -->
    <!-- ========================================================================= -->
    <div class="flex flex-1 flex-col overflow-hidden min-w-0">
        
        @include('drive.partials.toolbar')

        <!-- Main Scrollable Content Area -->
        <main class="flex-1 overflow-y-auto px-3 py-4 sm:px-5 sm:py-6 lg:px-8" @click="activeMenu = null">
            
            @include('drive.partials.upload-progress')
            @include('drive.partials.home')
            @include('drive.partials.drive-browser')
            @include('drive.partials.search')
            @include('drive.partials.shared-links')
            @include('drive.partials.trash')
        </main>
    </div>

