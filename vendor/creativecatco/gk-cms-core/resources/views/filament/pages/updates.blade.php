<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current Version Card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold">GKeys CMS</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Installed Version: <span class="font-mono font-semibold text-gray-700 dark:text-gray-300">{{ $this->displayVersion }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                        Update Channel: <span class="font-mono">{{ $updateChannel }}</span>
                    </p>
                </div>
                <button wire:click="checkForUpdates" wire:loading.attr="disabled" wire:target="checkForUpdates"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 dark:bg-custom-500 dark:hover:bg-custom-400 focus-visible:ring-custom-500/50 dark:focus-visible:ring-custom-400/50"
                    style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);">
                    <span wire:loading.remove wire:target="checkForUpdates">
                        <svg class="w-5 h-5 inline -mt-0.5 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                        </svg>
                        Check for Updates
                    </span>
                    <span wire:loading wire:target="checkForUpdates">Checking...</span>
                </button>
            </div>
        </div>

        {{-- Update Available Card --}}
        @if($updateAvailable)
        <div class="fi-section rounded-xl bg-amber-50 dark:bg-amber-900/20 shadow-sm ring-1 ring-amber-200 dark:ring-amber-800 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15M9 12l3 3m0 0 3-3m-3 3V2.25" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-amber-700 dark:text-amber-400">
                            Update Available: {{ $this->displayLatestVersion }}
                        </h3>
                        <p class="text-sm text-amber-600 dark:text-amber-500">
                            {{ $this->displayVersion }} &rarr; {{ $this->displayLatestVersion }}
                            @if($latestDate) &middot; Released {{ $latestDate }} @endif
                        </p>
                        @if(!empty($modifiedTemplates))
                        <p class="text-xs text-amber-500 dark:text-amber-600 mt-1">
                            <svg class="w-3.5 h-3.5 inline -mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            {{ count($modifiedTemplates) }} custom template(s) will be preserved during update.
                        </p>
                        @endif
                    </div>
                </div>
                <button id="applyUpdateBtn" onclick="runUpdate()"
                    class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg gap-1.5 px-4 py-2 text-sm inline-grid shadow-sm bg-green-600 text-white hover:bg-green-500 dark:bg-green-500 dark:hover:bg-green-400">
                    <span id="updateBtnText">Update Now</span>
                    <span id="updateBtnLoading" style="display:none;">
                        <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Updating...
                    </span>
                </button>
            </div>
        </div>
        @elseif($latestVersion)
        <div class="fi-section rounded-xl bg-green-50 dark:bg-green-900/20 shadow-sm ring-1 ring-green-200 dark:ring-green-800 p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-green-700 dark:text-green-400">Up to Date</h3>
                    <p class="text-sm text-green-600 dark:text-green-500">
                        You are running the latest version of GKeys CMS ({{ $this->displayVersion }}).
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- Update Log (shown during/after update via JS) --}}
        <div id="updateLogSection" style="display:none;" class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold">Update Log</h3>
                <span id="updateStatus" class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-400 font-medium">Starting...</span>
            </div>
            <pre id="updateLogOutput" class="text-xs font-mono bg-gray-950 text-green-400 p-4 rounded-lg overflow-x-auto max-h-96 overflow-y-auto whitespace-pre-wrap">Preparing update...\n</pre>
        </div>

        {{-- Customer-Modified Templates --}}
        @if(!empty($modifiedTemplates))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
                <h3 class="font-semibold">Protected Templates</h3>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                The following templates have been customized and will not be overwritten during updates:
            </p>
            <div class="space-y-1">
                @foreach($modifiedTemplates as $template)
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-2 h-2 rounded-full bg-blue-400 flex-shrink-0"></span>
                    <code class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $template }}</code>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Changelog --}}
        @if(!empty($changelog))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="font-semibold mb-4">Changelog</h3>
            <div class="prose prose-sm dark:prose-invert max-w-none text-sm">
                {!! \Illuminate\Support\Str::markdown($changelog) !!}
            </div>
        </div>
        @endif

        {{-- Manual Update Instructions --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="font-semibold mb-3">Manual Update</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                If the one-click update doesn't work, you can update manually via SSH:
            </p>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                @if($updateChannel === 'composer')
                <p class="text-xs font-mono text-gray-600 dark:text-gray-400">
                    <span class="text-gray-400 dark:text-gray-500"># Composer channel (development):</span><br>
                    cd /path/to/your/laravel-app<br>
                    composer update creativecatco/gk-cms-core<br>
                    php artisan migrate --force<br>
                    php artisan cms:safe-publish-templates<br>
                    php artisan cache:clear && php artisan view:clear<br>
                    php artisan vendor:publish --tag=cms-assets --force
                </p>
                @else
                <p class="text-xs font-mono text-gray-600 dark:text-gray-400">
                    <span class="text-gray-400 dark:text-gray-500"># Download the latest release zip and extract:</span><br>
                    cd /path/to/your/laravel-app<br>
                    <span class="text-gray-400 dark:text-gray-500"># Replace vendor/ with the new vendor/ from the zip</span><br>
                    php artisan migrate --force<br>
                    php artisan cms:safe-publish-templates<br>
                    php artisan cache:clear && php artisan view:clear<br>
                    php artisan vendor:publish --tag=cms-assets --force
                </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Hidden data for JS --}}
    <div id="updateData"
         data-channel="{{ $updateChannel }}"
         data-download-url="{{ $downloadUrl }}"
         style="display:none;"></div>

    <script>
    var _pollTimer = null;

    function runUpdate() {
        var btn = document.getElementById('applyUpdateBtn');
        var btnText = document.getElementById('updateBtnText');
        var btnLoading = document.getElementById('updateBtnLoading');
        var logSection = document.getElementById('updateLogSection');
        var logOutput = document.getElementById('updateLogOutput');
        var statusBadge = document.getElementById('updateStatus');
        var updateData = document.getElementById('updateData');

        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
        logSection.style.display = 'block';
        logOutput.textContent = 'Running pre-flight checks...\n';
        statusBadge.textContent = 'Pre-flight...';
        statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-400 font-medium';

        // Build the request body
        var body = {};
        var channel = updateData.getAttribute('data-channel');
        if (channel === 'release') {
            body.download_url = updateData.getAttribute('data-download-url');
        }

        // Trigger the background update
        fetch('/admin/api/cms-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(body)
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.status === 'started') {
                var channelMsg = channel === 'release'
                    ? 'Downloading and installing release...'
                    : 'Running composer update...';
                logOutput.textContent = 'Update started. ' + channelMsg + '\n\nThis may take 1-3 minutes. Do not close this page.\n';
                statusBadge.textContent = 'Running...';
                _pollTimer = setInterval(pollStatus, 3000);
            } else if (data.status === 'running') {
                logOutput.textContent = 'An update is already in progress. Monitoring...\n';
                statusBadge.textContent = 'Running...';
                _pollTimer = setInterval(pollStatus, 3000);
            } else if (data.status === 'preflight_failed') {
                logOutput.textContent = 'Pre-flight checks failed:\n\n' + (data.error || 'Unknown error') + '\n\nPlease resolve the issues and try again.';
                statusBadge.textContent = 'Pre-flight Failed';
                statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-400 font-medium';
                resetButton();
            } else {
                logOutput.textContent = 'Error: ' + (data.error || data.message || 'Unknown error');
                statusBadge.textContent = 'Failed';
                statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-400 font-medium';
                resetButton();
            }
        })
        .catch(function(err) {
            logOutput.textContent = 'Request failed: ' + err.message;
            statusBadge.textContent = 'Failed';
            statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-400 font-medium';
            resetButton();
        });
    }

    function pollStatus() {
        var logOutput = document.getElementById('updateLogOutput');
        var statusBadge = document.getElementById('updateStatus');

        fetch('/admin/api/cms-update-status', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.log) {
                logOutput.textContent = data.log;
                logOutput.scrollTop = logOutput.scrollHeight;
            }

            if (data.status === 'complete') {
                clearInterval(_pollTimer);
                statusBadge.textContent = 'Complete';
                statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-400 font-medium';

                var newVersion = data.new_version ? ' (v' + data.new_version + ')' : '';
                logOutput.textContent += '\n\nUpdate complete!' + newVersion + ' Reloading page...';

                setTimeout(function() { window.location.reload(); }, 3000);
            } else if (data.status === 'failed') {
                clearInterval(_pollTimer);
                statusBadge.textContent = 'Failed';
                statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-400 font-medium';
                resetButton();
            } else if (data.status === 'idle') {
                clearInterval(_pollTimer);
                statusBadge.textContent = 'Finished';
                statusBadge.className = 'text-xs px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-400 font-medium';
                resetButton();
            }
        })
        .catch(function(err) {
            console.log('Poll error:', err.message);
        });
    }

    function resetButton() {
        var btn = document.getElementById('applyUpdateBtn');
        var btnText = document.getElementById('updateBtnText');
        var btnLoading = document.getElementById('updateBtnLoading');
        if (btn) {
            btn.disabled = false;
            btnText.style.display = 'inline';
            btnLoading.style.display = 'none';
        }
    }
    </script>
</x-filament-panels::page>
