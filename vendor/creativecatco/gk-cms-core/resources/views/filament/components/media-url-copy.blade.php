@php
    $record = $getRecord();
    $publicUrl = $record ? asset('storage/' . $record->path) : '';
    $storagePath = $record ? $record->path : '';
@endphp

<div class="space-y-3">
    {{-- Public URL --}}
    <div>
        <label class="text-sm font-medium text-gray-400 dark:text-gray-400 mb-1 block">Public URL</label>
        <div x-data="{ copied: false }" class="flex items-center gap-2">
            <input type="text" readonly
                   value="{{ $publicUrl }}"
                   class="fi-input block w-full rounded-lg border-none bg-white/5 px-3 py-1.5 text-sm shadow-sm ring-1 ring-white/10 focus:ring-2 focus:ring-primary-500 dark:text-gray-200 text-gray-700 dark:bg-white/5 bg-gray-100 dark:ring-white/10 ring-gray-300"
                   onclick="this.select()">
            <button type="button"
                    @click="
                        navigator.clipboard.writeText('{{ $publicUrl }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition whitespace-nowrap"
                    :class="copied
                        ? 'bg-green-600 text-white'
                        : 'bg-primary-600 text-white hover:bg-primary-500 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20'"
            >
                <template x-if="!copied">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Copy URL
                    </span>
                </template>
                <template x-if="copied">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Copied!
                    </span>
                </template>
            </button>
        </div>
    </div>

    {{-- Storage Path (for use in CMS fields) --}}
    <div>
        <label class="text-sm font-medium text-gray-400 dark:text-gray-400 mb-1 block">Storage Path <span class="text-xs text-gray-500">(for use in page fields)</span></label>
        <div x-data="{ copied: false }" class="flex items-center gap-2">
            <input type="text" readonly
                   value="{{ $storagePath }}"
                   class="fi-input block w-full rounded-lg border-none bg-white/5 px-3 py-1.5 text-sm shadow-sm ring-1 ring-white/10 focus:ring-2 focus:ring-primary-500 dark:text-gray-200 text-gray-700 dark:bg-white/5 bg-gray-100 dark:ring-white/10 ring-gray-300 font-mono"
                   onclick="this.select()">
            <button type="button"
                    @click="
                        navigator.clipboard.writeText('{{ $storagePath }}');
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition whitespace-nowrap dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20 bg-gray-200 text-gray-700 hover:bg-gray-300"
                    :class="copied ? 'bg-green-600 !text-white dark:bg-green-600' : ''"
            >
                <template x-if="!copied">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Copy Path
                    </span>
                </template>
                <template x-if="copied">
                    <span class="flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Copied!
                    </span>
                </template>
            </button>
        </div>
    </div>
</div>
