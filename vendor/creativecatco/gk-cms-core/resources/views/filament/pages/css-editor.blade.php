<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Theme Variables Preview --}}
        <x-filament::section heading="Theme Variables" description="These CSS variables are auto-generated from your Theme settings. Use them in your CSS with var(--primary-color), etc." collapsible collapsed>
            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg text-sm font-mono overflow-x-auto">{{ $theme_variables }}</pre>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Edit these values in Settings &rarr; Theme tab.</p>
        </x-filament::section>

        {{-- Global CSS --}}
        <x-filament::section heading="Global CSS" description="Applied to every page on the site. Use this for site-wide styles, reusable classes, and overrides.">
            <div class="space-y-3">
                <textarea wire:model="global_css" rows="18"
                    class="w-full font-mono text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    placeholder="/* Global styles */&#10;.container { max-width: 1200px; margin: 0 auto; }&#10;.btn-primary { background: var(--primary-color); }"></textarea>

                <div class="flex justify-end">
                    <x-filament::button wire:click="saveGlobalCss" icon="heroicon-o-check" color="primary">
                        Save Global CSS
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Page-Specific CSS --}}
        <x-filament::section heading="Page CSS" description="CSS that applies only to a specific page. Select a page to edit its custom styles.">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Select Page</label>
                    <select wire:model="page_id" wire:change="loadPageCss"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <option value="">-- Choose a page --</option>
                        @foreach(\CreativeCatCo\GkCmsCore\Models\Page::orderBy('title')->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->title }} ({{ $p->slug }})</option>
                        @endforeach
                    </select>
                </div>

                @if($page_id)
                    <textarea wire:model="page_css" rows="12"
                        class="w-full font-mono text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="/* Page-specific styles */"></textarea>

                    <div class="flex justify-end">
                        <x-filament::button wire:click="savePageCss" icon="heroicon-o-check" color="primary">
                             Save Page CSS                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
