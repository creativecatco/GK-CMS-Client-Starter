<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-4 rounded-lg" style="background: #272a36; border: 1px solid #333645;">
            <p class="text-sm text-gray-400">
                Theme Builder lets you manage reusable layout components — header, footer, archive pages, and single post templates.
                These are shared across your entire site and only need to be built once.
            </p>
        </div>

        @php
            $typeLabels = [
                'header' => ['label' => 'Headers', 'icon' => 'heroicon-o-bars-3', 'desc' => 'Site-wide navigation headers'],
                'footer' => ['label' => 'Footers', 'icon' => 'heroicon-o-bars-3-bottom-left', 'desc' => 'Site-wide footer sections'],
                'archive' => ['label' => 'Archive Pages', 'icon' => 'heroicon-o-rectangle-stack', 'desc' => 'Blog listing, portfolio grid, product catalog layouts'],
                'single_post' => ['label' => 'Single Post Templates', 'icon' => 'heroicon-o-document-text', 'desc' => 'Individual blog post layout'],
                'single_portfolio' => ['label' => 'Portfolio Templates', 'icon' => 'heroicon-o-photo', 'desc' => 'Individual portfolio item layout'],
            ];
        @endphp

        @foreach($typeLabels as $type => $info)
            <div class="rounded-lg overflow-hidden" style="background: #272a36; border: 1px solid #333645;">
                <div class="px-6 py-4 flex items-center justify-between" style="border-bottom: 1px solid #333645;">
                    <div>
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <x-dynamic-component :component="$info['icon']" class="w-5 h-5 text-gray-400 dark:text-gray-300" />
                            {{ $info['label'] }}
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">{{ $info['desc'] }}</p>
                    </div>
                    <a href="{{ route('filament.admin.resources.pages.create', ['page_type' => $type]) }}"
                       class="inline-flex items-center gap-1 px-4 py-2 rounded-lg text-sm font-medium transition"
                       style="background: #293726; color: #d2eb00;">
                        <x-heroicon-o-plus class="w-4 h-4" />
                        Create New
                    </a>
                </div>

                <div class="p-4">
                    @if(isset($grouped[$type]) && $grouped[$type]->count() > 0)
                        <div class="space-y-3">
                            @foreach($grouped[$type] as $template)
                                <div class="flex items-center justify-between p-4 rounded-lg" style="background: #15171e; border: 1px solid #333645;">
                                    <div>
                                        <h4 class="font-medium text-white">{{ $template->title }}</h4>
                                        <p class="text-xs text-gray-400 mt-1">
                                            Template: {{ $template->template }} &middot;
                                            Status: <span class="{{ $template->status === 'published' ? 'text-green-400' : 'text-yellow-400' }}">{{ ucfirst($template->status) }}</span>
                                            &middot; Last updated: {{ $template->updated_at->diffForHumans() }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($template->status === 'published')
                                            <span class="px-2 py-1 text-xs rounded" style="background: #293726; color: #d2eb00;">Active</span>
                                        @endif
                                        <a href="{{ route('filament.admin.resources.pages.edit', $template) }}"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-sm transition"
                                           style="background: #272a36; color: #e5e7eb; border: 1px solid #333645;">
                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                            Edit
                                        </a>
                                        @if(in_array($type, ['header', 'footer']))
                                            <a href="{{ url($template->slug) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded text-sm transition"
                                               style="background: #272a36; color: #e5e7eb; border: 1px solid #333645;">
                                                <x-heroicon-o-eye class="w-4 h-4" />
                                                Preview
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-document-plus class="w-12 h-12 text-gray-500 mx-auto mb-3" />
                            <p class="text-gray-400 text-sm">No {{ strtolower($info['label']) }} created yet.</p>
                            <p class="text-gray-400 text-xs mt-1">Create one to get started, or let the AI generate it.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
