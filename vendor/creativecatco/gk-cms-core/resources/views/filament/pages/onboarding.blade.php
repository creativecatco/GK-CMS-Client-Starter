<x-filament-panels::page>
    <div class="max-w-3xl mx-auto">
        {{-- Progress Bar --}}
        <div class="mb-8">
            <div class="flex items-center justify-between mb-2">
                @foreach(['Company Info', 'Branding', 'Email', 'AI Setup', 'Review'] as $i => $label)
                    <div class="flex items-center {{ $i > 0 ? 'flex-1' : '' }}">
                        @if($i > 0)
                            <div class="flex-1 h-1 mx-2 rounded {{ $step > $i ? 'bg-primary-500' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                            {{ $step > $i + 1 ? 'bg-primary-500 text-white' : ($step === $i + 1 ? 'bg-primary-500 text-white ring-2 ring-primary-300' : 'bg-gray-200 dark:bg-gray-700 text-gray-500') }}">
                            {{ $i + 1 }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Company Info</span>
                <span>Branding</span>
                <span>Email</span>
                <span>AI Setup</span>
                <span>Review</span>
            </div>
        </div>

        {{-- Step 1: Company Info --}}
        @if($step === 1)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold mb-1">Tell us about your company</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">This information will be used across your website.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Company Name *</label>
                    <input type="text" wire:model="data.company_name" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="Growth Keys">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Tagline</label>
                    <input type="text" wire:model="data.company_tagline" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="Unlock Your Digital Potential">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address *</label>
                    <input type="email" wire:model="data.company_email" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="hello@growthkeys.com">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone Number</label>
                    <input type="text" wire:model="data.company_phone" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="+1 (555) 123-4567">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Address</label>
                    <textarea wire:model="data.company_address" rows="2" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="123 Main St, City, State 12345"></textarea>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button wire:click="skip" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">Skip Setup</button>
                <button wire:click="nextStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 hover:bg-primary-500">
                    Next &rarr;
                </button>
            </div>
        </div>
        @endif

        {{-- Step 2: Branding --}}
        @if($step === 2)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold mb-1">Set up your brand</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Configure your visual identity. You can change these later in Theme Settings.</p>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Logo</label>
                    <input type="text" wire:model="data.logo" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="Path to logo file (upload via Media Library)">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">You can upload your logo via the Media Library after setup.</p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Primary Color</label>
                        <div class="flex gap-2">
                            <input type="color" wire:model="data.primary_color" class="w-10 h-10 rounded cursor-pointer border-0">
                            <input type="text" wire:model="data.primary_color" class="fi-input flex-1 rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="#cfff2e">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Secondary Color</label>
                        <div class="flex gap-2">
                            <input type="color" wire:model="data.secondary_color" class="w-10 h-10 rounded cursor-pointer border-0">
                            <input type="text" wire:model="data.secondary_color" class="fi-input flex-1 rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="#293726">
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Heading Font</label>
                        <select wire:model="data.font_heading" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <option value="Inter">Inter</option>
                            <option value="Poppins">Poppins</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Playfair Display">Playfair Display</option>
                            <option value="Lato">Lato</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Raleway">Raleway</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Body Font</label>
                        <select wire:model="data.font_body" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                            <option value="Inter">Inter</option>
                            <option value="Poppins">Poppins</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Open Sans">Open Sans</option>
                            <option value="Lato">Lato</option>
                            <option value="Source Sans Pro">Source Sans Pro</option>
                            <option value="Nunito">Nunito</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button wire:click="previousStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-gray-50">
                    &larr; Back
                </button>
                <button wire:click="nextStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 hover:bg-primary-500">
                    Next &rarr;
                </button>
            </div>
        </div>
        @endif

        {{-- Step 3: Email Setup --}}
        @if($step === 3)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold mb-1">Email Configuration</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Set up SMTP for sending emails (contact forms, notifications). You can skip this and configure later.</p>

            <div class="space-y-4">
                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <input type="checkbox" wire:model.live="data.smtp_enabled" class="rounded border-gray-300">
                    <div>
                        <span class="font-medium">Enable Custom SMTP</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Use a custom SMTP provider (smtp.com, GoHighLevel, SendGrid, etc.)</p>
                    </div>
                </div>

                @if($data['smtp_enabled'] ?? false)
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">SMTP Host</label>
                        <input type="text" wire:model="data.smtp_host" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="smtp.gmail.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">SMTP Port</label>
                        <input type="text" wire:model="data.smtp_port" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="587">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">SMTP Username</label>
                    <input type="text" wire:model="data.smtp_username" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">SMTP Password</label>
                    <input type="password" wire:model="data.smtp_password" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Encryption</label>
                    <select wire:model="data.smtp_encryption" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <option value="tls">TLS</option>
                        <option value="ssl">SSL</option>
                        <option value="">None</option>
                    </select>
                </div>
                @endif
            </div>

            <div class="flex justify-between mt-6">
                <button wire:click="previousStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-gray-50">
                    &larr; Back
                </button>
                <button wire:click="nextStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 hover:bg-primary-500">
                    Next &rarr;
                </button>
            </div>
        </div>
        @endif

        {{-- Step 4: AI Setup --}}
        @if($step === 4)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold mb-1">AI Website Builder</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Connect your AI provider to enable the AI website builder. You'll need your own API key. You can skip this and add one later in Settings.</p>

            <div class="space-y-4">
                {{-- Info Banner --}}
                <div class="p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">Why do I need an API key?</p>
                            <p>The AI assistant uses your own API key to communicate with AI providers like OpenAI, Google, or Anthropic. This keeps your costs transparent and your data private. The CMS never stores or routes your requests through our servers.</p>
                        </div>
                    </div>
                </div>

                {{-- LLM Provider --}}
                <div>
                    <label class="block text-sm font-medium mb-1">AI Provider</label>
                    <select wire:model.live="data.ai_provider" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        <option value="openai">OpenAI (GPT-4.1, GPT-4.1-mini)</option>
                        <option value="anthropic">Anthropic (Claude)</option>
                        <option value="google">Google (Gemini)</option>
                        <option value="xai">xAI (Grok)</option>
                    </select>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        @if(($data['ai_provider'] ?? 'openai') === 'openai')
                            Get your key at <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary-500 hover:underline">platform.openai.com/api-keys</a>
                        @elseif(($data['ai_provider'] ?? '') === 'anthropic')
                            Get your key at <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-primary-500 hover:underline">console.anthropic.com</a>
                        @elseif(($data['ai_provider'] ?? '') === 'google')
                            Get your key at <a href="https://aistudio.google.com/apikey" target="_blank" class="text-primary-500 hover:underline">aistudio.google.com/apikey</a>
                        @elseif(($data['ai_provider'] ?? '') === 'xai')
                            Get your key at <a href="https://console.x.ai" target="_blank" class="text-primary-500 hover:underline">console.x.ai</a>
                        @endif
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">API Key</label>
                    <input type="password" wire:model="data.ai_api_key" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700"
                        placeholder="{{ match($data['ai_provider'] ?? 'openai') { 'openai' => 'sk-...', 'anthropic' => 'sk-ant-...', 'google' => 'AIza...', 'xai' => 'xai-...', default => 'Enter your API key' } }}">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Model</label>
                    <select wire:model="data.ai_model" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                        @if(($data['ai_provider'] ?? 'openai') === 'openai')
                            <option value="gpt-4.1-mini">GPT-4.1 Mini (Fast & affordable)</option>
                            <option value="gpt-4.1">GPT-4.1 (Most capable)</option>
                            <option value="gpt-4.1-nano">GPT-4.1 Nano (Fastest)</option>
                        @elseif(($data['ai_provider'] ?? '') === 'anthropic')
                            <option value="claude-sonnet-4-20250514">Claude Sonnet 4 (Latest)</option>
                            <option value="claude-3-5-sonnet-20241022">Claude 3.5 Sonnet</option>
                            <option value="claude-3-5-haiku-20241022">Claude 3.5 Haiku (Fast)</option>
                        @elseif(($data['ai_provider'] ?? '') === 'google')
                            <option value="gemini-2.5-flash">Gemini 2.5 Flash (Fast)</option>
                            <option value="gemini-2.5-pro">Gemini 2.5 Pro (Most capable)</option>
                        @elseif(($data['ai_provider'] ?? '') === 'xai')
                            <option value="grok-3-mini">Grok 3 Mini (Fast)</option>
                            <option value="grok-3">Grok 3 (Most capable)</option>
                        @endif
                    </select>
                </div>

                {{-- Image Generation (collapsible) --}}
                <details class="group">
                    <summary class="cursor-pointer text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 flex items-center gap-2">
                        <svg class="w-4 h-4 transition-transform group-open:rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        Image Generation (Optional)
                    </summary>
                    <div class="mt-3 space-y-3 pl-6">
                        <p class="text-xs text-gray-400 dark:text-gray-500">Enable AI image generation so the assistant can create custom hero banners, illustrations, and photos for your website.</p>
                        <div>
                            <label class="block text-sm font-medium mb-1">Image Provider</label>
                            <select wire:model.live="data.image_gen_provider" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                <option value="auto">Auto (use best available)</option>
                                <option value="nano_banana">Nano Banana (Google Gemini)</option>
                                <option value="dalle">OpenAI DALL-E 3</option>
                                <option value="none">Disabled</option>
                            </select>
                        </div>
                        @if(in_array($data['image_gen_provider'] ?? 'auto', ['auto', 'nano_banana']))
                        <div>
                            <label class="block text-sm font-medium mb-1">Google AI API Key</label>
                            <input type="password" wire:model="data.google_ai_api_key" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="AIza...">
                            <p class="text-xs text-gray-400 mt-1">From <a href="https://aistudio.google.com/apikey" target="_blank" class="text-primary-500 hover:underline">aistudio.google.com/apikey</a>. Same key as Google Gemini LLM if you chose that above.</p>
                        </div>
                        @endif
                        @if(in_array($data['image_gen_provider'] ?? 'auto', ['auto', 'dalle']))
                        <div>
                            <label class="block text-sm font-medium mb-1">OpenAI API Key (for DALL-E)</label>
                            <input type="password" wire:model="data.openai_image_api_key" class="fi-input block w-full rounded-lg border-gray-300 shadow-sm dark:bg-gray-800 dark:border-gray-700" placeholder="sk-...">
                            <p class="text-xs text-gray-400 mt-1">Same key as OpenAI LLM if you chose that above.</p>
                        </div>
                        @endif
                    </div>
                </details>
            </div>

            <div class="flex justify-between mt-6">
                <button wire:click="previousStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-gray-50">
                    &larr; Back
                </button>
                <div class="flex gap-2">
                    <button wire:click="nextStep" class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 px-3 py-2">Skip for now</button>
                    <button wire:click="nextStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 hover:bg-primary-500">
                        Next &rarr;
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Step 5: Review --}}
        @if($step === 5)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h2 class="text-xl font-bold mb-1">Review & Complete</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Review your settings before completing setup.</p>

            <div class="space-y-4">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="font-semibold mb-2">Company</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Name:</span>
                        <span>{{ $data['company_name'] ?: 'Not set' }}</span>
                        <span class="text-gray-500 dark:text-gray-400">Email:</span>
                        <span>{{ $data['company_email'] ?: 'Not set' }}</span>
                        <span class="text-gray-500 dark:text-gray-400">Phone:</span>
                        <span>{{ $data['company_phone'] ?: 'Not set' }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="font-semibold mb-2">Branding</h3>
                    <div class="flex items-center gap-4">
                        <div class="flex gap-2">
                            <div class="w-8 h-8 rounded" style="background: {{ $data['primary_color'] }}"></div>
                            <div class="w-8 h-8 rounded" style="background: {{ $data['secondary_color'] }}"></div>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $data['font_heading'] }} / {{ $data['font_body'] }}</span>
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="font-semibold mb-2">Email</h3>
                    <span class="text-sm">{{ ($data['smtp_enabled'] ?? false) ? 'Custom SMTP: ' . ($data['smtp_host'] ?: 'Not configured') : 'Using default mail driver' }}</span>
                </div>

                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <h3 class="font-semibold mb-2">AI Assistant</h3>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Provider:</span>
                        <span>{{ match($data['ai_provider'] ?? '') { 'openai' => 'OpenAI', 'anthropic' => 'Anthropic', 'google' => 'Google Gemini', 'xai' => 'xAI', default => 'Not set' } }}</span>
                        <span class="text-gray-500 dark:text-gray-400">API Key:</span>
                        <span class="{{ !empty($data['ai_api_key']) ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ !empty($data['ai_api_key']) ? 'Configured' : 'Not configured (AI will be unavailable)' }}
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">Image Generation:</span>
                        <span>{{ match($data['image_gen_provider'] ?? 'auto') { 'auto' => 'Auto', 'nano_banana' => 'Nano Banana', 'dalle' => 'DALL-E', 'none' => 'Disabled', default => 'Auto' } }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <button wire:click="previousStep" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-300 dark:ring-gray-700 hover:bg-gray-50">
                    &larr; Back
                </button>
                <button wire:click="complete" class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-1 font-semibold rounded-lg px-4 py-2 text-sm text-white shadow-sm bg-primary-600 hover:bg-primary-500">
                    Complete Setup
                </button>
            </div>
        </div>
        @endif
    </div>
</x-filament-panels::page>
