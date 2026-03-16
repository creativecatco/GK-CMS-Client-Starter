<x-filament-widgets::widget>
    <x-filament::section heading="Quick Actions">
        <div class="flex flex-wrap gap-3">
            {{-- New Page: Lime background with dark text --}}
            <a href="{{ \CreativeCatCo\GkCmsCore\Filament\Resources\PageResource::getUrl('create') }}"
               class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-150 hover:opacity-90"
               style="background-color: #cfff2e; color: #293726; border: none;">
                <x-heroicon-o-plus class="h-5 w-5" style="color: #293726;" />
                New Page
            </a>

            {{-- New Post: Forest green background with white text --}}
            <a href="{{ \CreativeCatCo\GkCmsCore\Filament\Resources\PostResource::getUrl('create') }}"
               class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-150 hover:opacity-90"
               style="background-color: #3f524e; color: #f6f1e6; border: none;">
                <x-heroicon-o-plus class="h-5 w-5" style="color: #d2eb00;" />
                New Post
            </a>

            {{-- Upload Media: Dark blue background with lime accent text --}}
            <a href="{{ \CreativeCatCo\GkCmsCore\Filament\Resources\MediaResource::getUrl('create') }}"
               class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition-all duration-150 hover:opacity-90"
               style="background-color: #272a36; color: #d2eb00; border: 1px solid #3f524e;">
                <x-heroicon-o-arrow-up-tray class="h-5 w-5" style="color: #cfff2e;" />
                Upload Media
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
