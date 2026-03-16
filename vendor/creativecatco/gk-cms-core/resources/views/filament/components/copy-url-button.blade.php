<div x-data="{ copied: false }" class="flex items-center gap-2">
    @if($record instanceof \Closure ? ($record = ($record)()) : $record)
        <input type="text" readonly
               value="{{ asset('storage/' . $record->path) }}"
               class="fi-input block w-full rounded-lg border-none bg-white/5 px-3 py-1.5 text-sm text-white shadow-sm ring-1 ring-white/10 focus:ring-2 focus:ring-primary-500"
               id="media-url-{{ $record->id }}">
        <button type="button"
                @click="
                    navigator.clipboard.writeText('{{ asset('storage/' . $record->path) }}');
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                "
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium transition whitespace-nowrap"
                :class="copied ? 'bg-green-600 text-white' : 'bg-white/10 text-gray-300 hover:bg-white/20'"
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
    @endif
</div>
