<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Status Banner --}}
        @if ($isHealthy)
            <div class="p-6 text-center bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                <div class="text-4xl mb-2">&#10003;</div>
                <h3 class="text-xl font-semibold text-green-800 dark:text-green-200">Database is Healthy</h3>
                <p class="mt-1 text-sm text-green-600 dark:text-green-400">No issues detected. Your CMS database is in good shape.</p>
                <div class="mt-4">
                    <x-filament::button wire:click="runHealthCheck" color="gray" size="sm">
                        Re-check Now
                    </x-filament::button>
                </div>
            </div>
        @else
            <div class="p-6 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-red-800 dark:text-red-200">
                            {{ $issueCount }} Issue{{ $issueCount !== 1 ? 's' : '' }} Detected
                        </h3>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">
                            The following issues were found in your database. Click "Repair All" to fix them automatically.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button wire:click="runHealthCheck" color="gray" size="sm">
                            Re-check
                        </x-filament::button>
                        <x-filament::button wire:click="repairAll" color="danger" size="sm">
                            Repair All
                        </x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Issue Cards --}}
            @foreach ($issues as $key => $issue)
                @if (($issue['count'] ?? 0) > 0)
                    <div class="p-5 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $issue['label'] }}
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ $issue['count'] }}
                                    </span>
                                </h4>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $issue['description'] }}</p>
                            </div>
                            <x-filament::button wire:click="repairIssue('{{ $key }}')" color="warning" size="sm">
                                Repair
                            </x-filament::button>
                        </div>

                        @if (!empty($issue['items']))
                            <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <ul class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                                    @foreach (array_slice($issue['items'], 0, 10, true) as $id => $name)
                                        <li class="flex items-center gap-2">
                                            <span class="text-gray-400">#{{ $id }}</span>
                                            <span>{{ $name }}</span>
                                        </li>
                                    @endforeach
                                    @if (count($issue['items']) > 10)
                                        <li class="text-gray-400 italic">... and {{ count($issue['items']) - 10 }} more</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Repair Results --}}
        @if (!empty($repairResults))
            <div class="p-5 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                <h4 class="text-lg font-medium text-blue-800 dark:text-blue-200 mb-3">Repair Results</h4>
                <div class="space-y-2">
                    @foreach ($repairResults as $key => $result)
                        <div class="flex items-center gap-2 text-sm">
                            @if (!empty($result['error']))
                                <span class="text-red-600 dark:text-red-400">&#10007;</span>
                                <span class="text-red-700 dark:text-red-300">{{ $key }}: {{ $result['error'] }}</span>
                            @elseif (($result['repaired'] ?? 0) > 0)
                                <span class="text-green-600 dark:text-green-400">&#10003;</span>
                                <span class="text-green-700 dark:text-green-300">{{ $result['message'] ?? "Repaired {$result['repaired']} items" }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ $result['message'] ?? 'No repairs needed' }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
