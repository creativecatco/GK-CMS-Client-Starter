<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Installed Plugins</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Extend your GKeys CMS with plugins.</p>
                </div>
                <button disabled class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 opacity-50 cursor-not-allowed">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Add Plugin
                </button>
            </div>
        </div>

        {{-- Empty State --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-12 text-center">
            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center mb-4">
                <x-heroicon-o-puzzle-piece class="w-8 h-8 text-gray-400 dark:text-gray-500" />
            </div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">No Plugins Installed</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-md mx-auto">
                The plugin system is coming soon. You'll be able to install plugins to add features like SEO tools, analytics, e-commerce, and more.
            </p>
            <div class="mt-6 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400 text-sm">
                <x-heroicon-o-clock class="w-4 h-4" />
                Plugin marketplace coming in a future update
            </div>
        </div>

        {{-- Planned Plugins Preview --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="font-semibold mb-4">Planned Plugins</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['SEO Tools', 'heroicon-o-magnifying-glass', 'Meta tags, sitemaps, and search optimization'],
                    ['Analytics', 'heroicon-o-chart-bar', 'Google Analytics and custom tracking integration'],
                    ['Forms', 'heroicon-o-document-text', 'Contact forms, surveys, and lead capture'],
                    ['E-Commerce', 'heroicon-o-shopping-cart', 'Product listings, cart, and checkout'],
                    ['Blog Pro', 'heroicon-o-newspaper', 'Advanced blogging with categories and tags'],
                    ['Backup', 'heroicon-o-cloud-arrow-up', 'Automated backups and one-click restore'],
                ] as [$name, $icon, $desc])
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 opacity-75">
                    <div class="flex items-center gap-3 mb-2">
                        <x-dynamic-component :component="$icon" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                        <span class="font-medium text-sm">{{ $name }}</span>
                        <span class="ml-auto text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">Coming Soon</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $desc }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
