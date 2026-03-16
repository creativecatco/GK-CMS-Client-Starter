<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section heading="Search & Replace" description="Search and replace text across your entire website — pages, posts, settings, and menus.">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search For</label>
                    <input type="text" wire:model="search_term"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="e.g., olddomain.com" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Replace With</label>
                    <input type="text" wire:model="replace_term"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="e.g., newdomain.com" />
                </div>

                <div class="flex gap-3">
                    <x-filament::button wire:click="scan" icon="heroicon-o-magnifying-glass" color="primary">
                        Scan for Matches
                    </x-filament::button>

                    @if($has_scanned && !empty($scan_results))
                        <x-filament::button wire:click="executeReplace" icon="heroicon-o-arrow-path" color="danger"
                            wire:confirm="Are you sure? This will replace ALL occurrences across the entire site. This cannot be undone.">
                            Replace All ({{ count($scan_results) }} items)
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        @if($has_scanned)
            <x-filament::section heading="Scan Results">
                @if(empty($scan_results))
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p>No matches found for "<strong>{{ $search_term }}</strong>"</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                <tr>
                                    <th class="px-4 py-3">Type</th>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Found In</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scan_results as $result)
                                    <tr class="border-b dark:border-gray-600">
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                                {{ $result['type'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $result['name'] }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $result['locations'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        Found <strong>{{ count($scan_results) }}</strong> item(s) containing "<strong>{{ $search_term }}</strong>"
                    </div>
                @endif
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
