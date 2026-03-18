<x-filament-panels::page>
    @if(!$isConfigured)
        {{-- Not Configured State --}}
        <div class="flex items-center justify-center min-h-[60vh]">
            <div class="text-center max-w-md">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-warning-100 dark:bg-warning-900 flex items-center justify-center">
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-warning-500" />
                </div>
                <h2 class="text-xl font-bold mb-2">AI Not Configured</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    To use the AI Website Builder, you need to configure an AI provider and API key in your settings.
                </p>
                <a href="/admin/settings"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg font-semibold text-sm text-white"
                   style="background-color: var(--primary-600, #6366f1);">
                    <x-heroicon-o-cog-6-tooth class="w-4 h-4" />
                    Go to Settings
                </a>
            </div>
        </div>
    @else
        {{-- Full-screen AI Assistant Interface --}}
        <style>
            /* Override Filament's main content container — this is the actual constraining element */
            .fi-main { max-width: none !important; padding: 0 !important; width: 100% !important; }
            /* Override the page content container */
            .fi-page-content-container { max-width: none !important; padding: 0 !important; }
            .fi-page > .fi-page-content { padding: 0 !important; }
            /* Hide the Filament page header ("AI Website Builder" title) to maximize space */
            .fi-header { display: none !important; }
            /* Also remove the py-8 gap from the section wrapper */
            .fi-page > section { padding-top: 0 !important; padding-bottom: 0 !important; gap: 0 !important; }
            /* Remove any gap/spacing from the page body */
            .fi-page > div { gap: 0 !important; }
            /* Make the main content area fill available width */
            .fi-main-ctn { width: 100% !important; }

            /* ── Message formatting ── */
            .ai-msg-content p { margin-bottom: 0.75em; }
            .ai-msg-content p:last-child { margin-bottom: 0; }
            .ai-msg-content ul, .ai-msg-content ol { margin-bottom: 0.75em; padding-left: 1.5em; }
            .ai-msg-content li { margin-bottom: 0.25em; }
            .ai-msg-content h1, .ai-msg-content h2, .ai-msg-content h3 { margin-top: 1em; margin-bottom: 0.5em; }
            .ai-msg-content pre { margin: 0.75em 0; }

            /* ── Collapsible code blocks ── */
            .ai-code-accordion { margin: 0.75em 0; border-radius: 0.75rem; overflow: hidden; border: 1px solid rgba(107, 114, 128, 0.2); }
            .ai-code-accordion summary {
                cursor: pointer; padding: 0.5rem 0.75rem; font-size: 0.75rem; font-weight: 500;
                background: rgba(107, 114, 128, 0.08); color: #6b7280;
                display: flex; align-items: center; gap: 0.5rem; user-select: none;
                list-style: none;
            }
            .ai-code-accordion summary::-webkit-details-marker { display: none; }
            .ai-code-accordion summary::before {
                content: '\25B6'; font-size: 0.6em; transition: transform 0.2s;
            }
            .ai-code-accordion[open] summary::before { transform: rotate(90deg); }
            .ai-code-accordion pre { margin: 0 !important; border-radius: 0 !important; max-height: 400px; overflow: auto; }

            /* ── Pulsing/Glowing activity indicator (Manus-style) ── */
            @keyframes ai-pulse-glow {
                0%, 100% { box-shadow: 0 0 8px rgba(99, 102, 241, 0.15); }
                50% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.35), 0 0 40px rgba(99, 102, 241, 0.1); }
            }
            @keyframes ai-shimmer {
                0% { background-position: -200% center; }
                100% { background-position: 200% center; }
            }
            @keyframes ai-dot-pulse {
                0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
                40% { opacity: 1; transform: scale(1.2); }
            }
            .ai-activity-card {
                animation: ai-pulse-glow 2s ease-in-out infinite;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(139, 92, 246, 0.08));
                border: 1px solid rgba(99, 102, 241, 0.2);
                border-radius: 1rem; padding: 0.75rem 1rem;
            }
            .dark .ai-activity-card {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.15));
                border-color: rgba(99, 102, 241, 0.3);
            }
            .ai-status-text {
                background: linear-gradient(90deg, currentColor 0%, rgba(99,102,241,0.6) 50%, currentColor 100%);
                background-size: 200% auto;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                animation: ai-shimmer 3s linear infinite;
            }
            .ai-dot {
                width: 6px; height: 6px; border-radius: 50%;
                background: var(--primary-500, #6366f1);
                animation: ai-dot-pulse 1.4s ease-in-out infinite;
            }
            .ai-dot:nth-child(2) { animation-delay: 0.2s; }
            .ai-dot:nth-child(3) { animation-delay: 0.4s; }

            /* ── Tool call indicators ── */
            .ai-tool-running { animation: ai-pulse-glow 2s ease-in-out infinite; }

            /* ── Image citation badge ── */
            .ai-img-citation {
                display: inline-flex; align-items: center; gap: 0.25rem;
                padding: 0.125rem 0.5rem; border-radius: 9999px;
                font-size: 0.65rem; background: rgba(99, 102, 241, 0.1);
                color: #6366f1; margin-top: 0.25rem;
            }

            /* ── Inline success/error result messages ── */
            .ai-tool-success {
                display: flex; align-items: center; gap: 0.5rem;
                padding: 0.375rem 0.75rem; border-radius: 0.75rem;
                font-size: 0.75rem; font-weight: 500;
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.2);
                color: #059669;
                margin-top: 0.25rem;
                animation: ai-fade-in 0.3s ease-out;
            }
            .dark .ai-tool-success {
                background: rgba(16, 185, 129, 0.12);
                border-color: rgba(16, 185, 129, 0.25);
                color: #34d399;
            }
            .ai-tool-error-msg {
                display: flex; align-items: center; gap: 0.5rem;
                padding: 0.375rem 0.75rem; border-radius: 0.75rem;
                font-size: 0.75rem; font-weight: 500;
                background: rgba(239, 68, 68, 0.08);
                border: 1px solid rgba(239, 68, 68, 0.2);
                color: #dc2626;
                margin-top: 0.25rem;
                animation: ai-fade-in 0.3s ease-out;
            }
            .dark .ai-tool-error-msg {
                background: rgba(239, 68, 68, 0.12);
                border-color: rgba(239, 68, 68, 0.25);
                color: #f87171;
            }
            @keyframes ai-fade-in {
                from { opacity: 0; transform: translateY(-4px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* ── Generated image preview in chat ── */
            .ai-gen-image-preview {
                margin-top: 0.5rem;
                border-radius: 0.75rem;
                overflow: hidden;
                border: 1px solid rgba(107, 114, 128, 0.2);
                max-width: 280px;
                animation: ai-fade-in 0.4s ease-out;
            }
            .ai-gen-image-preview img {
                width: 100%; height: auto; display: block;
                max-height: 200px; object-fit: cover;
            }
            .ai-gen-image-preview .ai-gen-image-meta {
                padding: 0.375rem 0.625rem;
                background: rgba(107, 114, 128, 0.05);
                border-top: 1px solid rgba(107, 114, 128, 0.1);
            }

            /* ── Collapsible HTML in tool results ── */
            .ai-html-accordion { margin: 0.5rem 0; border-radius: 0.75rem; overflow: hidden; border: 1px solid rgba(107, 114, 128, 0.2); }
            .ai-html-accordion summary {
                cursor: pointer; padding: 0.5rem 0.75rem; font-size: 0.7rem; font-weight: 500;
                background: rgba(107, 114, 128, 0.06); color: #6b7280;
                display: flex; align-items: center; gap: 0.5rem; user-select: none;
                list-style: none;
            }
            .ai-html-accordion summary::-webkit-details-marker { display: none; }
            .ai-html-accordion summary::before {
                content: '\25B6'; font-size: 0.55em; transition: transform 0.2s;
            }
            .ai-html-accordion[open] summary::before { transform: rotate(90deg); }
            .ai-html-accordion pre { margin: 0 !important; border-radius: 0 !important; max-height: 300px; overflow: auto; font-size: 0.7rem; }
        </style>

        <div x-data="aiAssistant()" x-init="init()" class="flex gap-0 w-full" style="height: calc(100vh - 4rem);">

            {{-- Left Side: Chat Panel --}}
            <div class="flex flex-col border-r border-gray-200 dark:border-gray-700"
                 :class="showPreview ? 'w-[420px] flex-shrink-0' : 'flex-1'"
                 :style="showPreview ? 'min-width: 380px; max-width: 520px;' : ''">

                {{-- Top Bar: Conversation Controls --}}
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <button @click="toggleSidebar()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :class="showSidebar ? 'bg-primary-100 dark:bg-primary-900' : ''"
                            title="Conversations">
                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5" />
                    </button>
                    <button @click="newConversation()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            title="New Conversation">
                        <x-heroicon-o-plus class="w-5 h-5" />
                    </button>
                    <div class="flex-1 text-sm font-medium truncate" x-text="currentTitle"></div>
                    <button @click="toggleActions()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :class="showActions ? 'bg-primary-100 dark:bg-primary-900' : ''"
                            title="Action History">
                        <x-heroicon-o-clock class="w-5 h-5" />
                    </button>
                    <button @click="togglePreview()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            :class="showPreview ? 'bg-primary-100 dark:bg-primary-900' : ''"
                            title="Toggle Preview">
                        <x-heroicon-o-eye class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex flex-1 overflow-hidden">

                    {{-- Conversation Sidebar --}}
                    <div x-show="showSidebar" x-transition
                         class="w-56 flex-shrink-0 border-r border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col bg-gray-50 dark:bg-gray-900">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-sm">Conversations</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-1">
                            <template x-for="conv in conversations" :key="conv.id">
                                <div @click="loadConversation(conv.id)"
                                     class="group flex items-center gap-2 p-2 rounded-lg cursor-pointer text-sm transition"
                                     :class="conv.id === conversationId ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                                    <x-heroicon-o-chat-bubble-left class="w-4 h-4 flex-shrink-0" />
                                    <span class="truncate flex-1" x-text="conv.title"></span>
                                    <button @click.stop="deleteConversation(conv.id)"
                                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-red-100 dark:hover:bg-red-900 transition">
                                        <x-heroicon-o-trash class="w-3 h-3 text-red-500" />
                                    </button>
                                </div>
                            </template>
                            <div x-show="conversations.length === 0" class="text-center text-gray-400 text-sm py-4">
                                No conversations yet
                            </div>
                        </div>
                    </div>

                    {{-- Chat Area --}}
                    <div class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-gray-800">

                        {{-- Error Banner --}}
                        <div x-show="errorMessage" x-transition
                             class="px-4 py-2 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800 flex items-center gap-2">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 text-red-500 flex-shrink-0" />
                            <span class="text-sm text-red-700 dark:text-red-300 flex-1" x-text="errorMessage"></span>
                            <button @click="errorMessage = ''" class="text-red-500 hover:text-red-700 p-1">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>

                        {{-- Messages --}}
                        <div x-ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">

                            {{-- Welcome Message --}}
                            <div x-show="messages.length === 0 && !isStreaming" class="flex flex-col items-center justify-center h-full text-center">
                                <div class="w-16 h-16 mb-4 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <x-heroicon-o-sparkles class="w-8 h-8 text-primary-500" />
                                </div>
                                <h3 class="text-lg font-bold mb-2">AI Website Builder</h3>
                                <p class="text-gray-500 dark:text-gray-400 text-sm max-w-sm mb-6">
                                    Describe the website you want to build, and I'll create it for you. I can generate pages, set up themes, write content, and more.
                                </p>
                                <div class="grid grid-cols-2 gap-2 max-w-sm">
                                    <button @click="sendQuickPrompt('Build me a modern homepage for a digital marketing agency')"
                                            class="text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs transition">
                                        <span class="font-semibold block mb-1">Marketing Agency</span>
                                        <span class="text-gray-500 dark:text-gray-400">Build a modern homepage</span>
                                    </button>
                                    <button @click="sendQuickPrompt('Create a portfolio website for a photographer with a gallery page')"
                                            class="text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs transition">
                                        <span class="font-semibold block mb-1">Photography Portfolio</span>
                                        <span class="text-gray-500 dark:text-gray-400">Gallery & portfolio pages</span>
                                    </button>
                                    <button @click="sendQuickPrompt('Set up a professional services website for a law firm with a contact page')"
                                            class="text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs transition">
                                        <span class="font-semibold block mb-1">Law Firm</span>
                                        <span class="text-gray-500 dark:text-gray-400">Professional services site</span>
                                    </button>
                                    <button @click="sendQuickPrompt('Update the theme colors to use a blue and white color scheme')"
                                            class="text-left p-3 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs transition">
                                        <span class="font-semibold block mb-1">Update Theme</span>
                                        <span class="text-gray-500 dark:text-gray-400">Change colors & fonts</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Message List --}}
                            <template x-for="(msg, index) in messages" :key="index">
                                <div>
                                    {{-- User Message --}}
                                    <div x-show="msg.role === 'user'" class="flex justify-end">
                                        <div class="max-w-[85%] px-4 py-3 rounded-2xl rounded-br-md text-sm text-white"
                                             style="background-color: var(--primary-600, #6366f1);">
                                            {{-- File attachment badges --}}
                                            <template x-if="msg.files && msg.files.length > 0">
                                                <div class="flex flex-wrap gap-1.5 mb-2">
                                                    <template x-for="fname in msg.files" :key="fname">
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-white/20">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                            <span x-text="fname"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </template>
                                            {{-- Image attachment thumbnails --}}
                                            <template x-if="msg.images && msg.images.length > 0">
                                                <div class="flex flex-wrap gap-2 mb-2">
                                                    <template x-for="img in msg.images" :key="img.url">
                                                        <div class="relative">
                                                            <img :src="img.url" :alt="img.filename" class="h-16 w-16 object-cover rounded-lg border border-white/30" />
                                                            <span class="absolute bottom-0 left-0 right-0 text-[9px] text-center bg-black/50 rounded-b-lg px-1 truncate" x-text="img.filename"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                            <div x-text="msg.content"></div>
                                        </div>
                                    </div>

                                    {{-- Assistant Message --}}
                                    <div x-show="msg.role === 'assistant'" class="flex justify-start">
                                        <div class="max-w-[90%]">
                                            <div class="flex items-center gap-2 mb-1">
                                                <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                                    <x-heroicon-o-sparkles class="w-3 h-3 text-primary-500" />
                                                </div>
                                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI Assistant</span>
                                            </div>
                                            <div class="ai-msg-content px-4 py-3 rounded-2xl rounded-bl-md bg-gray-100 dark:bg-gray-700 text-sm prose prose-sm dark:prose-invert max-w-none"
                                                 x-html="renderMarkdown(msg.content)">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Tool Call Indicator --}}
                                    <div x-show="msg.role === 'tool_call'" class="flex justify-start pl-8">
                                        <div class="w-full max-w-[85%]">
                                            {{-- Tool status bar --}}
                                            <div class="flex items-center gap-2 px-3 py-2 rounded-lg text-xs border transition-all duration-300"
                                                 :class="{
                                                     'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 ai-tool-running': msg.status === 'running',
                                                     'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': msg.status === 'done',
                                                     'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800': msg.status === 'error',
                                                 }">
                                                <svg class="w-3 h-3 text-blue-500 animate-spin" x-show="msg.status === 'running'" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <x-heroicon-o-check-circle x-show="msg.status === 'done'" class="w-3 h-3 text-green-500" />
                                                <x-heroicon-o-x-circle x-show="msg.status === 'error'" class="w-3 h-3 text-red-500" />
                                                <span class="font-mono" x-text="formatToolName(msg.tool_name)"></span>
                                                <button @click="msg.expanded = !msg.expanded" class="text-blue-500 hover:text-blue-700 ml-auto">
                                                    <span x-text="msg.expanded ? 'Hide' : 'Details'"></span>
                                                </button>
                                            </div>

                                            {{-- Success message with context --}}
                                            <template x-if="msg.status === 'done' && msg.result?.success && msg.result?.message">
                                                <div class="ai-tool-success mt-1">
                                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    <span x-text="msg.result.message"></span>
                                                </div>
                                            </template>

                                            {{-- Error message --}}
                                            <template x-if="msg.status === 'error' && msg.result?.error">
                                                <div class="ai-tool-error-msg mt-1">
                                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                    <span x-text="msg.result.error"></span>
                                                </div>
                                            </template>

                                            {{-- Generated image preview with citation --}}
                                            <template x-if="msg.tool_name === 'generate_image' && msg.status === 'done' && msg.result?.success && msg.result?.data?.url">
                                                <div class="ai-gen-image-preview">
                                                    <a :href="msg.result.data.url" target="_blank">
                                                        <img :src="msg.result.data.url" :alt="msg.result.data.alt_text || 'Generated image'" loading="lazy" />
                                                    </a>
                                                    <div class="ai-gen-image-meta">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-[11px] font-medium text-gray-600 dark:text-gray-400" x-text="msg.result.data.filename || 'generated-image.png'"></span>
                                                            <span class="text-[10px] text-gray-400" x-text="msg.result.data.aspect_ratio || ''"></span>
                                                        </div>
                                                        <template x-if="msg.result.data.citation">
                                                            <div class="ai-img-citation mt-1">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                                <span x-text="msg.result.data.citation"></span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>

                                            {{-- Uploaded image preview --}}
                                            <template x-if="msg.tool_name === 'upload_image' && msg.status === 'done' && msg.result?.success && msg.result?.data?.url">
                                                <div class="ai-gen-image-preview">
                                                    <a :href="msg.result.data.url" target="_blank">
                                                        <img :src="msg.result.data.url" :alt="msg.result.data.alt_text || 'Uploaded image'" loading="lazy" />
                                                    </a>
                                                    <div class="ai-gen-image-meta">
                                                        <span class="text-[11px] font-medium text-gray-600 dark:text-gray-400" x-text="msg.result.data.filename || 'image'"></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Expanded tool details (collapsible) --}}
                                    <div x-show="msg.role === 'tool_call' && msg.expanded" x-transition class="pl-8 mt-1 max-w-[85%]">
                                        {{-- Tool params - collapsible if large --}}
                                        <template x-if="msg.params && hasLargeHtml(msg.params)">
                                            <details class="ai-html-accordion">
                                                <summary>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                                                    View tool parameters
                                                </summary>
                                                <pre class="text-xs bg-gray-900 text-gray-100 p-3 overflow-x-auto" x-text="JSON.stringify(msg.params, null, 2)"></pre>
                                            </details>
                                        </template>
                                        <template x-if="msg.params && !hasLargeHtml(msg.params)">
                                            <pre class="text-xs bg-gray-900 text-gray-100 p-3 rounded-lg overflow-x-auto max-h-40" x-text="JSON.stringify(msg.params, null, 2)"></pre>
                                        </template>
                                        {{-- Tool result - collapsible if large --}}
                                        <div x-show="msg.result" class="mt-1">
                                            <template x-if="msg.result && hasLargeHtml(msg.result)">
                                                <details class="ai-html-accordion">
                                                    <summary>
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                                        View tool result
                                                    </summary>
                                                    <pre class="text-xs bg-gray-900 text-gray-100 p-3 overflow-x-auto" x-text="JSON.stringify(msg.result, null, 2)"></pre>
                                                </details>
                                            </template>
                                            <template x-if="msg.result && !hasLargeHtml(msg.result)">
                                                <pre class="text-xs bg-gray-900 text-gray-100 p-3 rounded-lg overflow-x-auto max-h-40" x-text="JSON.stringify(msg.result, null, 2)"></pre>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            {{-- Plan Progress Card --}}
                            <div x-show="activePlan" x-transition class="flex justify-start mb-2">
                                <div class="max-w-[90%] w-full">
                                    <div class="px-4 py-3 rounded-2xl rounded-bl-md bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40 border border-indigo-200/50 dark:border-indigo-800/50">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">Build Plan</span>
                                            </div>
                                            <span class="text-[10px] font-medium text-indigo-500 dark:text-indigo-400" x-show="currentPlanStep > 0" x-text="`Step ${currentPlanStep} of ${activePlan?.totalSteps || 0}`"></span>
                                        </div>
                                        {{-- Progress bar --}}
                                        <div class="w-full h-1.5 bg-indigo-100 dark:bg-indigo-900/50 rounded-full mb-3 overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                                                 :style="`width: ${activePlan ? (activePlan.steps.filter(s => s.status === 'done').length / activePlan.totalSteps * 100) : 0}%`"></div>
                                        </div>
                                        {{-- Plan steps --}}
                                        <div class="space-y-1.5">
                                            <template x-for="(step, stepIdx) in (activePlan?.steps || [])" :key="stepIdx">
                                                <div class="flex items-center gap-2 text-xs transition-all duration-300"
                                                     :class="{'opacity-50': step.status === 'done'}">
                                                    {{-- Status icon --}}
                                                    <template x-if="step.status === 'done'">
                                                        <svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    </template>
                                                    <template x-if="step.status === 'running'">
                                                        <svg class="w-3.5 h-3.5 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    </template>
                                                    <template x-if="step.status === 'pending'">
                                                        <div class="w-3.5 h-3.5 rounded-full border-2 border-gray-300 dark:border-gray-600 flex-shrink-0"></div>
                                                    </template>
                                                    {{-- Step text --}}
                                                    <span :class="{
                                                        'text-gray-400 dark:text-gray-500 line-through': step.status === 'done',
                                                        'text-indigo-700 dark:text-indigo-300 font-medium': step.status === 'running',
                                                        'text-gray-600 dark:text-gray-400': step.status === 'pending',
                                                    }" x-text="step.text"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Thinking / Progress Indicator (Manus-style) --}}
                            <div x-show="isStreaming && (!currentStreamText || isThinking || activeToolName)" class="flex justify-start">
                                <div class="max-w-[90%] w-full">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                            <x-heroicon-o-sparkles class="w-3 h-3 text-primary-500" />
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI Assistant</span>
                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                    </div>
                                    <div class="ai-activity-card">
                                        {{-- Activity log showing what the AI is doing --}}
                                        <div class="space-y-2" x-show="thinkingSteps.length > 0">
                                            <template x-for="(step, sIdx) in thinkingSteps" :key="sIdx">
                                                <div class="flex items-center gap-2.5 text-xs transition-all duration-300"
                                                     :class="{'opacity-50': step.status === 'done'}">
                                                    <template x-if="step.status === 'done'">
                                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    </template>
                                                    <template x-if="step.status === 'running'">
                                                        <svg class="w-4 h-4 text-indigo-500 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    </template>
                                                    <template x-if="step.status === 'error'">
                                                        <svg class="w-4 h-4 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </template>
                                                    <span class="text-gray-700 dark:text-gray-300" :class="{
                                                        'text-gray-400 dark:text-gray-500 line-through': step.status === 'done',
                                                        'font-medium': step.status === 'running'
                                                    }" x-text="step.text"></span>
                                                </div>
                                            </template>
                                        </div>
                                        {{-- Current activity with pulsing dots (Manus-style) --}}
                                        <div class="flex items-center gap-3" :class="{'mt-3': thinkingSteps.length > 0}" x-show="isThinking || activeToolName">
                                            <div class="flex gap-1">
                                                <div class="ai-dot"></div>
                                                <div class="ai-dot"></div>
                                                <div class="ai-dot"></div>
                                            </div>
                                            <span class="text-xs font-medium ai-status-text" x-text="statusMessage || 'Thinking...'"></span>
                                        </div>
                                        {{-- Fallback when no steps yet --}}
                                        <div class="flex items-center gap-3" x-show="thinkingSteps.length === 0 && !isThinking && !activeToolName">
                                            <div class="flex gap-1">
                                                <div class="ai-dot"></div>
                                                <div class="ai-dot"></div>
                                                <div class="ai-dot"></div>
                                            </div>
                                            <span class="text-xs font-medium ai-status-text">Processing your request...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Streaming Text --}}
                            <div x-show="isStreaming && currentStreamText" class="flex justify-start">
                                <div class="max-w-[90%]">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                            <x-heroicon-o-sparkles class="w-3 h-3 text-primary-500" />
                                        </div>
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">AI Assistant</span>
                                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                    </div>
                                    <div class="ai-msg-content px-4 py-3 rounded-2xl rounded-bl-md bg-gray-100 dark:bg-gray-700 text-sm prose prose-sm dark:prose-invert max-w-none"
                                         x-html="renderMarkdown(currentStreamText)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Input Area --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 p-3 bg-white dark:bg-gray-800">
                            {{-- Uploaded Files Preview --}}
                            <div x-show="uploadedFiles.length > 0" class="flex flex-wrap gap-2 mb-2">
                                <template x-for="(uf, ufIdx) in uploadedFiles" :key="ufIdx">
                                    <div class="relative group">
                                        {{-- Image preview chip --}}
                                        <template x-if="uf.type === 'image'">
                                            <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-xs">
                                                <img :src="uf.url" class="h-10 w-10 object-cover rounded" />
                                                <div class="max-w-[120px]">
                                                    <div class="truncate font-medium text-gray-700 dark:text-gray-300" x-text="uf.filename"></div>
                                                    <div class="text-gray-400" x-text="'Saved to gallery'"></div>
                                                </div>
                                                <button @click="removeFile(ufIdx)" class="ml-1 p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-red-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                        {{-- Document preview chip --}}
                                        <template x-if="uf.type !== 'image'">
                                            <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-xs">
                                                <div class="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center" :class="uf.error ? 'bg-red-100 dark:bg-red-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
                                                    <template x-if="uf.uploading">
                                                        <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                                    </template>
                                                    <template x-if="!uf.uploading && !uf.error">
                                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                    </template>
                                                    <template x-if="uf.error">
                                                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                                    </template>
                                                </div>
                                                <div class="max-w-[120px]">
                                                    <div class="truncate font-medium text-gray-700 dark:text-gray-300" x-text="uf.filename"></div>
                                                    <div class="text-gray-400">
                                                        <span x-show="uf.uploading">Processing...</span>
                                                        <span x-show="!uf.uploading && !uf.error" x-text="formatChars(uf.chars)"></span>
                                                        <span x-show="uf.error" class="text-red-500" x-text="uf.error"></span>
                                                    </div>
                                                </div>
                                                <button @click="removeFile(ufIdx)" class="ml-1 p-0.5 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-400 hover:text-red-500">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            {{-- Google Doc URL Input --}}
                            <div x-show="showGdocInput" x-transition class="flex gap-2 mb-2">
                                <input x-model="gdocUrl"
                                       @keydown.enter.prevent="importGoogleDoc()"
                                       @keydown.escape="showGdocInput = false; gdocUrl = '';"
                                       type="url"
                                       placeholder="Paste Google Docs URL (must be publicly shared)..."
                                       class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none" />
                                <button @click="importGoogleDoc()" :disabled="!gdocUrl.trim()"
                                        class="px-3 py-2 rounded-lg text-sm font-medium text-white transition disabled:opacity-50"
                                        style="background-color: var(--primary-600, #6366f1);">
                                    Import
                                </button>
                                <button @click="showGdocInput = false; gdocUrl = '';"
                                        class="px-3 py-2 rounded-lg text-sm text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    Cancel
                                </button>
                            </div>

                            {{-- Main Input Row --}}
                            <div class="flex gap-2">
                                {{-- Hidden file input --}}
                                <input x-ref="fileInput" type="file" class="hidden"
                                       accept=".txt,.md,.csv,.html,.htm,.rtf,.pdf,.docx,.doc,.jpg,.jpeg,.png,.gif,.webp,.svg"
                                       multiple
                                       @change="handleFileSelect($event)" />

                                {{-- Attach Button (dropdown) --}}
                                <div x-data="{ showAttachMenu: false }" class="relative flex-shrink-0">
                                    <button @click="showAttachMenu = !showAttachMenu"
                                            :disabled="isStreaming"
                                            class="p-3 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 transition disabled:opacity-50"
                                            title="Attach files or import content">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                    </button>
                                    {{-- Dropdown Menu --}}
                                    <div x-show="showAttachMenu" @click.away="showAttachMenu = false" x-transition
                                         class="absolute bottom-full left-0 mb-2 w-56 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 shadow-lg py-1 z-50">
                                        <button @click="triggerFileUpload(); showAttachMenu = false;"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Upload Document
                                            <span class="text-[10px] text-gray-400 ml-auto">PDF, DOCX, TXT...</span>
                                        </button>
                                        <button @click="$refs.imageInput.click(); showAttachMenu = false;"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            Upload Image
                                            <span class="text-[10px] text-gray-400 ml-auto">PNG, JPG, SVG...</span>
                                        </button>
                                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>
                                        <button @click="showGdocInput = true; showAttachMenu = false; $nextTick(() => { if(document.querySelector('[x-model=gdocUrl]')) document.querySelector('[x-model=gdocUrl]').focus(); });"
                                                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                            <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                            Import Google Doc
                                            <span class="text-[10px] text-gray-400 ml-auto">Public link</span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Hidden image-only input --}}
                                <input x-ref="imageInput" type="file" class="hidden"
                                       accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml,.jpg,.jpeg,.png,.gif,.webp,.svg"
                                       multiple
                                       @change="handleFileSelect($event)" />

                                {{-- Text Input --}}
                                <textarea x-ref="messageInput"
                                          x-model="inputMessage"
                                          @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                          :disabled="isStreaming"
                                          placeholder="Describe what you want to build..."
                                          rows="1"
                                          class="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-4 py-3 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent outline-none transition"
                                          style="min-height: 44px; max-height: 120px;"
                                          @input="autoResize($event.target)"></textarea>

                                {{-- Stop Button (visible during streaming) --}}
                                <button x-show="isStreaming"
                                        @click="stopGeneration()"
                                        class="flex-shrink-0 p-3 rounded-xl bg-red-500 hover:bg-red-600 text-white transition"
                                        title="Stop generating">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <rect x="6" y="6" width="12" height="12" rx="1" />
                                    </svg>
                                </button>

                                {{-- Send Button (visible when not streaming) --}}
                                <button x-show="!isStreaming"
                                        @click="sendMessage()"
                                        :disabled="!inputMessage.trim() && uploadedFiles.length === 0"
                                        class="flex-shrink-0 p-3 rounded-xl text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
                                        style="background-color: var(--primary-600, #6366f1);">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-2 text-xs text-gray-400 dark:text-gray-500">
                                {{-- Model Picker Dropdown --}}
                                <div class="relative" @click.outside="showModelPicker = false">
                                    <button @click="showModelPicker = !showModelPicker"
                                            class="flex items-center gap-1.5 px-2 py-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 dark:text-gray-400"
                                            :disabled="isStreaming">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="font-medium" x-text="getModelLabel()"></span>
                                        <svg class="w-3 h-3 transition-transform" :class="showModelPicker ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    {{-- Dropdown Panel --}}
                                    <div x-show="showModelPicker" x-transition
                                         class="absolute bottom-full left-0 mb-2 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-200 dark:border-gray-700 overflow-hidden z-50">
                                        <div class="p-2 border-b border-gray-100 dark:border-gray-700">
                                            <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Select Model</span>
                                        </div>
                                        <div class="max-h-64 overflow-y-auto p-1">
                                            <template x-for="prov in configuredProviders" :key="prov.slug">
                                                <div class="mb-1">
                                                    <div class="px-3 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500" x-text="prov.name"></div>
                                                    <template x-for="model in prov.models" :key="model.id">
                                                        <button @click="selectModel(prov.slug, model.id)"
                                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition"
                                                                :class="selectedProvider === prov.slug && selectedModel === model.id
                                                                    ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 font-medium'
                                                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'">
                                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center flex-shrink-0"
                                                                 :class="selectedProvider === prov.slug && selectedModel === model.id
                                                                     ? 'border-primary-500'
                                                                     : 'border-gray-300 dark:border-gray-600'">
                                                                <div x-show="selectedProvider === prov.slug && selectedModel === model.id"
                                                                     class="w-2 h-2 rounded-full bg-primary-500"></div>
                                                            </div>
                                                            <span x-text="model.name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </template>
                                            <template x-if="configuredProviders.length === 0">
                                                <div class="px-3 py-4 text-center text-gray-400 text-sm">
                                                    No AI providers configured.<br>
                                                    <a href="/admin/settings" class="text-primary-500 hover:underline">Add API keys in Settings</a>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <span>Press Enter to send, Shift+Enter for new line</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Sidebar --}}
                    <div x-show="showActions" x-transition
                         class="w-64 flex-shrink-0 border-l border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col bg-gray-50 dark:bg-gray-900">
                        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-semibold text-sm">Action History</h3>
                        </div>
                        <div class="flex-1 overflow-y-auto p-2 space-y-2">
                            <template x-for="action in actions" :key="action.id">
                                <div class="p-2 rounded-lg border border-gray-200 dark:border-gray-700 text-xs bg-white dark:bg-gray-800">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-mono font-semibold" x-text="formatToolName(action.tool_name)"></span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300': action.status === 'success',
                                                  'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300': action.status === 'failed',
                                                  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300': action.status === 'rolled_back',
                                              }"
                                              x-text="action.status"></span>
                                    </div>
                                    <div class="text-gray-500 dark:text-gray-400 mb-1" x-text="action.created_at ? new Date(action.created_at).toLocaleTimeString() : ''"></div>
                                    <button x-show="action.can_rollback"
                                            @click="undoAction(action.id)"
                                            class="text-red-500 hover:text-red-700 font-medium">
                                        Undo
                                    </button>
                                </div>
                            </template>
                            <div x-show="actions.length === 0" class="text-center text-gray-400 text-sm py-4">
                                No actions yet
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Side: Preview Panel (fills remaining space) --}}
            <div x-show="showPreview" x-transition class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-gray-800">
                {{-- Preview Toolbar --}}
                <div class="flex items-center gap-2 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex gap-1.5 px-1">
                        <div class="w-3 h-3 rounded-full bg-red-400"></div>
                        <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                    </div>
                    <div class="flex-1 flex items-center gap-2">
                        <select x-model="previewUrl" @change="refreshPreview()"
                                class="flex-1 text-xs rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 px-3 py-1.5">
                            <option value="/">Home Page</option>
                            <template x-for="page in previewPages" :key="page.slug">
                                <option :value="'/' + page.slug" x-text="page.title"></option>
                            </template>
                        </select>
                    </div>
                    <div class="flex gap-1">
                        <button @click="previewMode = 'desktop'" class="p-1.5 rounded-lg transition"
                                :class="previewMode === 'desktop' ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2" stroke-width="2"/>
                                <line x1="8" y1="21" x2="16" y2="21" stroke-width="2"/>
                                <line x1="12" y1="17" x2="12" y2="21" stroke-width="2"/>
                            </svg>
                        </button>
                        <button @click="previewMode = 'tablet'" class="p-1.5 rounded-lg transition"
                                :class="previewMode === 'tablet' ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="4" y="2" width="16" height="20" rx="2" ry="2" stroke-width="2"/>
                                <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                        <button @click="previewMode = 'mobile'" class="p-1.5 rounded-lg transition"
                                :class="previewMode === 'mobile' ? 'bg-primary-100 dark:bg-primary-900' : 'hover:bg-gray-100 dark:hover:bg-gray-700'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <rect x="6" y="2" width="12" height="20" rx="2" ry="2" stroke-width="2"/>
                                <line x1="12" y1="18" x2="12" y2="18" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    <button @click="refreshPreview()" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Refresh">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                    </button>
                    <a :href="previewUrl" target="_blank" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition" title="Open in new tab">
                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                    </a>
                </div>

                {{-- Preview iFrame --}}
                <div class="flex-1 flex items-center justify-center bg-gray-100 dark:bg-gray-900 overflow-hidden"
                     :class="previewMode === 'desktop' ? 'p-0' : 'p-4'">
                    <iframe x-ref="previewFrame"
                            :src="previewUrl"
                            class="bg-white transition-all duration-300"
                            :class="previewMode === 'desktop' ? '' : 'shadow-2xl rounded-lg'"
                            :style="{
                                width: previewMode === 'desktop' ? '100%' : (previewMode === 'tablet' ? '768px' : '375px'),
                                height: '100%',
                                maxWidth: '100%',
                            }"
                            sandbox="allow-same-origin allow-scripts allow-forms"
                            loading="lazy">
                    </iframe>
                </div>
            </div>
        </div>

        {{-- Alpine.js Component --}}
        <script>
        function aiAssistant() {
            return {
                // State
                messages: [],
                inputMessage: '',
                isStreaming: false,
                currentStreamText: '',
                conversationId: null,
                currentTitle: 'New Conversation',
                conversations: [],
                actions: [],
                previewPages: [],
                errorMessage: '',
                abortController: null,
                retryCount: 0,
                maxRetries: 2,
                statusMessage: '',
                isThinking: false,
                activeToolName: '',
                thinkingSteps: [],     // Array of {text, status} for the activity log

                // LLM provider/model selection
                configuredProviders: @json($configuredProviders ?? []),
                selectedProvider: '',   // Will be set in init()
                selectedModel: '',      // Will be set in init()
                showModelPicker: false,

                // Plan tracking state
                activePlan: null,        // {steps: [{text, status, toolMatch}], totalSteps: 0}
                currentPlanStep: 0,      // Current step being executed

                // File upload state
                uploadedFiles: [],       // Array of {filename, content, chars, uploading, error}
                isUploading: false,
                showGdocInput: false,
                gdocUrl: '',

                // UI toggles
                showSidebar: false,
                showActions: false,
                showPreview: true,
                previewUrl: '/',
                previewMode: 'desktop',

                // Initialize
                init() {
                    this.initProviderSelection();
                    this.loadConversations();
                    this.loadPreviewPages();
                    this.loadMarkedJs();

                    // Auto-collapse the Filament sidebar for maximum workspace
                    // Use a short delay to ensure Filament's Alpine stores are fully initialized
                    const collapseSidebar = () => {
                        try {
                            if (window.Alpine && Alpine.store && Alpine.store('sidebar')) {
                                Alpine.store('sidebar').close();
                                return true;
                            }
                        } catch(e) {}
                        
                        // Fallback: find and click the collapse button
                        const collapseBtn = document.querySelector('button[x-tooltip\.raw="Collapse sidebar"]')
                            || document.querySelector('button[title="Collapse sidebar"]')
                            || document.querySelector('.fi-sidebar-close-btn');
                        if (collapseBtn) {
                            collapseBtn.click();
                            return true;
                        }
                        return false;
                    };

                    this.$nextTick(() => {
                        if (!collapseSidebar()) {
                            // Retry after a short delay if stores aren't ready yet
                            setTimeout(collapseSidebar, 200);
                            setTimeout(collapseSidebar, 500);
                        }

                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.focus();
                        }
                    });
                },

                // Initialize provider/model selection from configured providers
                initProviderSelection() {
                    if (!this.configuredProviders || this.configuredProviders.length === 0) return;
                    // Find the default provider
                    const defaultProv = this.configuredProviders.find(p => p.is_default) || this.configuredProviders[0];
                    this.selectedProvider = defaultProv.slug;
                    // Find the default model for this provider
                    const defaultModel = defaultProv.models.find(m => m.default) || defaultProv.models[0];
                    this.selectedModel = defaultModel ? defaultModel.id : '';
                },

                // Get the currently selected provider object
                getSelectedProviderObj() {
                    return this.configuredProviders.find(p => p.slug === this.selectedProvider) || null;
                },

                // Get display label for the current selection
                getModelLabel() {
                    const prov = this.getSelectedProviderObj();
                    if (!prov) return 'Select Model';
                    const model = prov.models.find(m => m.id === this.selectedModel);
                    return model ? model.name : prov.name;
                },

                // Select a provider and model
                selectModel(providerSlug, modelId) {
                    this.selectedProvider = providerSlug;
                    this.selectedModel = modelId;
                    this.showModelPicker = false;
                },

                // Load marked.js for Markdown rendering with fallback
                loadMarkedJs() {
                    if (window.marked) return;
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/marked/marked.min.js';
                    script.onerror = () => {
                        // Fallback: try unpkg
                        const fallback = document.createElement('script');
                        fallback.src = 'https://unpkg.com/marked/marked.min.js';
                        document.head.appendChild(fallback);
                    };
                    document.head.appendChild(script);
                },

                // Load conversation list
                async loadConversations() {
                    try {
                        const res = await fetch('/admin/api/ai-conversations', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        this.conversations = data.conversations || [];
                    } catch (e) {
                        console.error('Failed to load conversations:', e);
                    }
                },

                // Load available pages for preview dropdown
                async loadPreviewPages() {
                    try {
                        const res = await fetch('/api/cms/pages', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        this.previewPages = data.pages || data || [];
                    } catch (e) {
                        console.error('Failed to load pages:', e);
                    }
                },

                // Load a specific conversation
                async loadConversation(id) {
                    try {
                        const res = await fetch(`/admin/api/ai-conversations/${id}`, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (!res.ok) throw new Error(`HTTP ${res.status}`);
                        const data = await res.json();
                        const conv = data.conversation;

                        this.conversationId = conv.id;
                        this.currentTitle = conv.title;
                        this.actions = conv.actions || [];

                        // Convert stored messages to display format
                        this.messages = [];
                        for (const msg of conv.messages) {
                            if (msg.role === 'user') {
                                this.messages.push({ role: 'user', content: msg.content });
                            } else if (msg.role === 'assistant') {
                                this.messages.push({ role: 'assistant', content: msg.content || '' });
                                if (msg.tool_calls) {
                                    for (const tc of msg.tool_calls) {
                                        const args = typeof tc.function.arguments === 'string'
                                            ? JSON.parse(tc.function.arguments || '{}')
                                            : tc.function.arguments;
                                        this.messages.push({
                                            role: 'tool_call',
                                            tool_name: tc.function.name,
                                            params: args,
                                            status: 'done',
                                            expanded: false,
                                        });
                                    }
                                }
                            }
                        }

                        this.showSidebar = false;
                        this.scrollToBottom();
                    } catch (e) {
                        console.error('Failed to load conversation:', e);
                        this.showError('Failed to load conversation. Please try again.');
                    }
                },

                // Start a new conversation
                newConversation() {
                    this.conversationId = null;
                    this.currentTitle = 'New Conversation';
                    this.messages = [];
                    this.actions = [];
                    this.currentStreamText = '';
                    this.errorMessage = '';
                    this.uploadedFiles = [];
                    this.showGdocInput = false;
                    this.gdocUrl = '';
                    this.activePlan = null;
                    this.currentPlanStep = 0;
                    this.showSidebar = false;
                    this.$nextTick(() => {
                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.focus();
                        }
                    });
                },

                // Delete a conversation
                async deleteConversation(id) {
                    if (!confirm('Delete this conversation?')) return;
                    try {
                        await fetch(`/admin/api/ai-conversations/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                            }
                        });
                        this.conversations = this.conversations.filter(c => c.id !== id);
                        if (this.conversationId === id) {
                            this.newConversation();
                        }
                    } catch (e) {
                        console.error('Failed to delete conversation:', e);
                        this.showError('Failed to delete conversation.');
                    }
                },

                // Send a message
                async sendMessage(retrying = false) {
                    const userText = retrying ? this._lastMessage : this.inputMessage.trim();
                    if ((!userText && this.uploadedFiles.length === 0) || this.isStreaming) return;

                    // Build the full message: user text + any file context
                    let message = userText;
                    if (!retrying && this.uploadedFiles.length > 0) {
                        const docParts = [];
                        const imageParts = [];
                        for (const f of this.uploadedFiles) {
                            if (f.type === 'image' && f.url) {
                                imageParts.push(`- Image "${f.filename}" uploaded to gallery: ${f.url}`);
                            } else if (f.content) {
                                docParts.push(`--- Uploaded File: ${f.filename} ---\n${f.content}\n--- End of ${f.filename} ---`);
                            }
                        }

                        const contextBlocks = [];
                        if (imageParts.length > 0) {
                            contextBlocks.push('[UPLOADED IMAGES - saved to media gallery, use these URLs in page templates]\n\n' + imageParts.join('\n'));
                        }
                        if (docParts.length > 0) {
                            contextBlocks.push('[ATTACHED DOCUMENTS FOR CONTEXT]\n\n' + docParts.join('\n\n'));
                        }

                        if (contextBlocks.length > 0) {
                            const context = contextBlocks.join('\n\n');
                            message = message
                                ? `${message}\n\n${context}`
                                : `Please use the following uploaded content:\n\n${context}`;
                        }
                    }

                    if (!message) return;

                    if (!retrying) {
                        this._lastMessage = message;
                        this.inputMessage = '';
                        this.retryCount = 0;
                        // Add user message to display (show only the user's typed text, not the full context)
                        const displayText = userText || ('Uploaded ' + this.uploadedFiles.map(f => f.filename).join(', '));
                        const docNames = this.uploadedFiles.filter(f => f.type !== 'image' && f.content).map(f => f.filename);
                        const imageAttachments = this.uploadedFiles.filter(f => f.type === 'image' && f.url).map(f => ({ filename: f.filename, url: f.url }));
                        this.messages.push({
                            role: 'user',
                            content: displayText,
                            files: docNames.length > 0 ? docNames : undefined,
                            images: imageAttachments.length > 0 ? imageAttachments : undefined,
                        });
                        // Clear uploaded files after sending
                        this.uploadedFiles = [];
                    }

                    this.isStreaming = true;
                    this.currentStreamText = '';
                    this.errorMessage = '';
                    this.scrollToBottom();

                    // Create an AbortController for the stop button
                    this.abortController = new AbortController();

                    try {
                        const response = await fetch('/admin/api/ai-chat', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'text/event-stream',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                message: message,
                                conversation_id: this.conversationId,
                                provider: this.selectedProvider || undefined,
                                model: this.selectedModel || undefined,
                            }),
                            signal: this.abortController.signal,
                        });

                        // Check for HTTP errors before trying to read the stream
                        if (!response.ok) {
                            const errorText = await response.text();
                            let errorMsg = `Server error (${response.status})`;
                            try {
                                const errorJson = JSON.parse(errorText);
                                errorMsg = errorJson.error || errorJson.message || errorMsg;
                            } catch (e) {}

                            if (response.status === 401 || response.status === 403) {
                                errorMsg = 'Authentication error. Please refresh the page and try again.';
                            } else if (response.status === 429) {
                                errorMsg = 'Rate limited. Please wait a moment and try again.';
                            } else if (response.status === 500) {
                                errorMsg = 'Server error. The AI provider may be temporarily unavailable.';
                            }

                            throw new Error(errorMsg);
                        }

                        const reader = response.body.getReader();
                        const decoder = new TextDecoder();
                        let buffer = '';
                        let toolsUsed = false;
                        let receivedDone = false;

                        while (true) {
                            const { done, value } = await reader.read();
                            if (done) break;

                            buffer += decoder.decode(value, { stream: true });
                            const lines = buffer.split('\n');
                            buffer = lines.pop(); // Keep incomplete line in buffer

                            let eventType = '';
                            for (const line of lines) {
                                if (line.startsWith('event: ')) {
                                    eventType = line.substring(7).trim();
                                } else if (line.startsWith('data: ') && eventType) {
                                    const data = line.substring(6);
                                    this.handleSseEvent(eventType, data);

                                    if (eventType === 'tool_start' || eventType === 'tool_result') {
                                        toolsUsed = true;
                                    }
                                    if (eventType === 'done') {
                                        receivedDone = true;
                                    }
                                    eventType = '';
                                }
                            }
                        }

                        // Process any remaining buffer after stream ends
                        if (buffer.trim()) {
                            const remainingLines = buffer.split('\n');
                            let eventType = '';
                            for (const line of remainingLines) {
                                if (line.startsWith('event: ')) {
                                    eventType = line.substring(7).trim();
                                } else if (line.startsWith('data: ') && eventType) {
                                    const data = line.substring(6);
                                    this.handleSseEvent(eventType, data);
                                    if (eventType === 'done') receivedDone = true;
                                    eventType = '';
                                }
                            }
                        }

                        // Finalize streaming text as a message
                        if (this.currentStreamText) {
                            this.messages.push({ role: 'assistant', content: this.currentStreamText });
                            this.currentStreamText = '';
                        }

                        // If we never received a 'done' event, the connection may have been cut
                        // (e.g., server proxy timeout). Show a notice to the user.
                        if (!receivedDone && toolsUsed) {
                            this.messages.push({
                                role: 'assistant',
                                content: '⏳ **Connection timed out** — but don\'t worry! The tool actions above were completed successfully. The server connection was interrupted before I could finish my response. Just send a follow-up message (like "continue" or "what\'s next?") and I\'ll pick up where I left off.',
                            });
                        }

                        // Refresh preview if tools were used
                        if (toolsUsed) {
                            setTimeout(() => this.refreshPreview(), 500);
                            this.loadPreviewPages();
                        }

                        // Refresh conversations list
                        this.loadConversations();

                        // Reset retry count on success
                        this.retryCount = 0;

                    } catch (e) {
                        if (e.name === 'AbortError') {
                            // User clicked stop — finalize any partial text
                            if (this.currentStreamText) {
                                this.messages.push({ role: 'assistant', content: this.currentStreamText + '\n\n*(Generation stopped)*' });
                                this.currentStreamText = '';
                            }
                        } else {
                            console.error('Chat error:', e);

                            // Auto-retry on network errors
                            if (this.retryCount < this.maxRetries && (e.message.includes('fetch') || e.message.includes('network') || e.message.includes('Failed to fetch'))) {
                                this.retryCount++;
                                this.isStreaming = false;
                                this.showError(`Connection lost. Retrying (${this.retryCount}/${this.maxRetries})...`);
                                setTimeout(() => this.sendMessage(true), 2000 * this.retryCount);
                                return;
                            }

                            // Show error with retry option
                            this.showError(e.message || 'An unexpected error occurred.');
                            this.messages.push({
                                role: 'assistant',
                                content: 'Sorry, I encountered an error. ' + (e.message || 'Please try again.'),
                            });
                        }
                    }

                    this.isStreaming = false;
                    this.abortController = null;
                    this.scrollToBottom();

                    // Re-focus input
                    this.$nextTick(() => {
                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.focus();
                        }
                    });
                },

                // Stop generation
                stopGeneration() {
                    if (this.abortController) {
                        this.abortController.abort();
                    }
                },

                // Handle SSE events
                handleSseEvent(type, data) {
                    switch (type) {
                        case 'text':
                            this.isThinking = false;
                            this.activeToolName = '';
                            this.statusMessage = '';
                            // Clear thinking steps when AI starts its final text response
                            if (this.thinkingSteps.length > 0 && !this.activeToolName) {
                                this.thinkingSteps = [];
                            }
                            this.currentStreamText += data;
                            // Detect plan in streamed text — look for numbered markdown list
                            this.detectPlanInText(this.currentStreamText);
                            this.scrollToBottom();
                            break;

                        case 'tool_start_hint':
                            // Early notification that a tool call is being generated
                            // Show status immediately while arguments are still streaming
                            try {
                                const hintData = JSON.parse(data);
                                this.isThinking = true;
                                this.activeToolName = hintData.name;
                                const hintMsg = this.getToolStatusMessage(hintData.name, 'running');
                                this.statusMessage = 'Preparing: ' + hintMsg;
                                // Save any accumulated text as a message
                                if (this.currentStreamText) {
                                    this.messages.push({ role: 'assistant', content: this.currentStreamText });
                                    this.currentStreamText = '';
                                }
                                this.scrollToBottom();
                            } catch (e) {}
                            break;

                        case 'tool_start':
                            // Save any accumulated text as a message first
                            if (this.currentStreamText) {
                                this.messages.push({ role: 'assistant', content: this.currentStreamText });
                                this.currentStreamText = '';
                            }
                            try {
                                const toolData = JSON.parse(data);
                                this.activeToolName = toolData.name;
                                const stepMsg = this.getToolStatusMessage(toolData.name, 'running');

                                // Plan-aware step tracking
                                let planStepLabel = '';
                                if (this.activePlan) {
                                    this.advancePlanStep(toolData.name, toolData.params);
                                    if (this.currentPlanStep > 0) {
                                        planStepLabel = `Step ${this.currentPlanStep} of ${this.activePlan.totalSteps}: `;
                                    }
                                }

                                this.statusMessage = planStepLabel + stepMsg;
                                // Add to thinking steps log
                                this.thinkingSteps.push({ text: planStepLabel + stepMsg, status: 'running', tool: toolData.name });
                                this.messages.push({
                                    role: 'tool_call',
                                    tool_name: toolData.name,
                                    params: toolData.params,
                                    status: 'running',
                                    expanded: false,
                                    result: null,
                                });
                            } catch (e) {}
                            this.scrollToBottom();
                            break;

                        case 'tool_result':
                            try {
                                const resultData = JSON.parse(data);
                                this.activeToolName = '';
                                this.statusMessage = 'Analyzing results...';
                                // Update plan step status
                                if (this.activePlan) {
                                    this.completePlanStep();
                                }
                                const isSuccess = resultData.result?.success;
                                // Update the thinking step status
                                for (let i = this.thinkingSteps.length - 1; i >= 0; i--) {
                                    if (this.thinkingSteps[i].tool === resultData.name && this.thinkingSteps[i].status === 'running') {
                                        this.thinkingSteps[i].status = isSuccess ? 'done' : 'error';
                                        this.thinkingSteps[i].text = this.getToolStatusMessage(resultData.name, 'done');
                                        break;
                                    }
                                }
                                // Find the last matching tool call and update it
                                for (let i = this.messages.length - 1; i >= 0; i--) {
                                    if (this.messages[i].role === 'tool_call' && this.messages[i].tool_name === resultData.name && this.messages[i].status === 'running') {
                                        this.messages[i].status = isSuccess ? 'done' : 'error';
                                        this.messages[i].result = resultData.result;
                                        break;
                                    }
                                }
                            } catch (e) {}
                            break;

                        case 'thinking':
                            // Show a thinking indicator between tool loops
                            this.isThinking = true;
                            this.statusMessage = 'Planning next steps...';
                            if (this.currentStreamText) {
                                this.messages.push({ role: 'assistant', content: this.currentStreamText });
                                this.currentStreamText = '';
                            }
                            this.scrollToBottom();
                            break;

                        case 'done':
                            this.isThinking = false;
                            this.activeToolName = '';
                            this.statusMessage = '';
                            this.thinkingSteps = [];
                            // Mark all remaining plan steps as done
                            if (this.activePlan) {
                                for (const step of this.activePlan.steps) {
                                    if (step.status === 'pending' || step.status === 'running') {
                                        step.status = 'done';
                                    }
                                }
                            }
                            try {
                                const doneData = JSON.parse(data);
                                if (doneData.conversation_id) {
                                    this.conversationId = doneData.conversation_id;
                                }
                                if (doneData.title && doneData.title !== this.currentTitle) {
                                    this.currentTitle = doneData.title;
                                }
                            } catch (e) {}
                            break;

                        case 'error':
                            if (this.currentStreamText) {
                                this.messages.push({ role: 'assistant', content: this.currentStreamText });
                                this.currentStreamText = '';
                            }
                            this.showError(data);
                            break;
                    }
                },

                // ─── File Upload Methods ───

                // Trigger the hidden file input
                triggerFileUpload() {
                    this.$refs.fileInput.click();
                },

                // Handle file selection
                async handleFileSelect(event) {
                    const files = event.target.files;
                    if (!files || files.length === 0) return;

                    for (const file of files) {
                        await this.uploadFile(file);
                    }

                    // Reset the input so the same file can be re-selected
                    event.target.value = '';
                },

                // Upload a single file and extract its text (or save image to gallery)
                async uploadFile(file) {
                    const fileEntry = {
                        filename: file.name,
                        type: null,       // 'image' or 'document'
                        content: null,    // text content (documents only)
                        url: null,        // image URL (images only)
                        chars: 0,
                        uploading: true,
                        error: null,
                    };
                    this.uploadedFiles.push(fileEntry);
                    this.isUploading = true;

                    try {
                        const formData = new FormData();
                        formData.append('file', file);

                        const res = await fetch('/admin/api/ai-upload', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                        const data = await res.json();

                        if (data.success) {
                            if (data.type === 'image') {
                                // Image was saved to gallery
                                fileEntry.type = 'image';
                                fileEntry.url = data.url;
                                fileEntry.filename = data.filename;
                            } else {
                                // Document text was extracted
                                fileEntry.type = 'document';
                                fileEntry.content = data.content;
                                fileEntry.chars = data.chars;
                            }
                            fileEntry.uploading = false;
                        } else {
                            fileEntry.error = data.error || 'Failed to process file';
                            fileEntry.uploading = false;
                        }
                    } catch (e) {
                        fileEntry.error = 'Upload failed: ' + e.message;
                        fileEntry.uploading = false;
                    }

                    this.isUploading = this.uploadedFiles.some(f => f.uploading);
                },

                // Remove an uploaded file
                removeFile(index) {
                    this.uploadedFiles.splice(index, 1);
                },

                // Import from Google Docs
                async importGoogleDoc() {
                    const url = this.gdocUrl.trim();
                    if (!url) return;

                    const fileEntry = {
                        filename: 'Google Doc',
                        content: null,
                        chars: 0,
                        uploading: true,
                        error: null,
                    };
                    this.uploadedFiles.push(fileEntry);
                    this.showGdocInput = false;
                    this.gdocUrl = '';

                    try {
                        const res = await fetch('/admin/api/ai-import-gdoc', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ url }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            // Try to extract a title from the URL
                            const match = url.match(/\/document\/d\/([a-zA-Z0-9_-]+)/);
                            fileEntry.filename = 'Google Doc' + (match ? ` (${match[1].substring(0, 8)}...)` : '');
                            fileEntry.content = data.content;
                            fileEntry.chars = data.content.length;
                            fileEntry.uploading = false;
                        } else {
                            fileEntry.error = data.error || 'Failed to import Google Doc';
                            fileEntry.uploading = false;
                        }
                    } catch (e) {
                        fileEntry.error = 'Import failed: ' + e.message;
                        fileEntry.uploading = false;
                    }
                },

                // Format file size for display
                formatChars(chars) {
                    if (chars < 1000) return chars + ' chars';
                    if (chars < 1000000) return (chars / 1000).toFixed(1) + 'k chars';
                    return (chars / 1000000).toFixed(1) + 'M chars';
                },

                // Quick prompt buttons
                sendQuickPrompt(prompt) {
                    this.inputMessage = prompt;
                    this.sendMessage();
                },

                // Undo an action
                async undoAction(actionId) {
                    if (!confirm('Undo this action? This will revert the changes made by this tool.')) return;
                    try {
                        const res = await fetch(`/admin/api/ai-actions/${actionId}/undo`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            }
                        });
                        const data = await res.json();
                        if (data.success) {
                            const action = this.actions.find(a => a.id === actionId);
                            if (action) {
                                action.status = 'rolled_back';
                                action.can_rollback = false;
                            }
                            this.refreshPreview();
                        } else {
                            this.showError(data.error || 'Failed to undo action.');
                        }
                    } catch (e) {
                        console.error('Undo failed:', e);
                        this.showError('Failed to undo action. Please try again.');
                    }
                },

                // Error handling
                showError(msg) {
                    this.errorMessage = msg;
                    // Auto-dismiss after 10 seconds
                    setTimeout(() => {
                        if (this.errorMessage === msg) {
                            this.errorMessage = '';
                        }
                    }, 10000);
                },

                // CSRF token helper
                getCsrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.content || '';
                },

                // UI helpers
                toggleSidebar() { this.showSidebar = !this.showSidebar; },
                toggleActions() { this.showActions = !this.showActions; },
                togglePreview() { this.showPreview = !this.showPreview; },

                refreshPreview() {
                    const frame = this.$refs.previewFrame;
                    if (frame) {
                        const currentSrc = frame.src;
                        frame.src = 'about:blank';
                        setTimeout(() => { frame.src = currentSrc; }, 100);
                    }
                },

                scrollToBottom() {
                    this.$nextTick(() => {
                        const container = this.$refs.messagesContainer;
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                },

                autoResize(el) {
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
                },

                renderMarkdown(text) {
                    if (!text) return '';
                    let html = '';
                    if (window.marked) {
                        try {
                            html = marked.parse(text);
                        } catch (e) {
                            html = this.basicMarkdown(text);
                        }
                    } else {
                        html = this.basicMarkdown(text);
                    }
                    // Wrap large code blocks in collapsible accordions
                    html = this.wrapCodeBlocks(html);
                    return html;
                },

                // Wrap large code blocks (>5 lines) in collapsible <details> elements
                wrapCodeBlocks(html) {
                    return html.replace(/<pre><code([^>]*)>(.*?)<\/code><\/pre>/gs, (match, attrs, code) => {
                        const lineCount = (code.match(/\n/g) || []).length + 1;
                        if (lineCount > 5) {
                            // Detect language from class
                            const langMatch = attrs.match(/class="[^"]*language-(\w+)/);
                            const lang = langMatch ? langMatch[1].toUpperCase() : 'Code';
                            const label = lang === 'HTML' || lang === 'BLADE' ? 'View generated HTML' :
                                          lang === 'PHP' ? 'View generated PHP' :
                                          lang === 'CSS' ? 'View generated CSS' :
                                          `View generated code (${lang})`;
                            return `<details class="ai-code-accordion"><summary><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>${label} (${lineCount} lines)</summary><pre><code${attrs}>${code}</code></pre></details>`;
                        }
                        return match;
                    });
                },

                // Basic markdown fallback when marked.js isn't loaded
                basicMarkdown(text) {
                    let html = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\*(.+?)\*/g, '<em>$1</em>')
                        .replace(/`(.+?)`/g, '<code class="bg-gray-200 dark:bg-gray-600 px-1 rounded text-xs">$1</code>');
                    // Convert double newlines to paragraphs, single newlines to <br>
                    html = html.split(/\n\n+/).map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');
                    return html;
                },

                formatToolName(name) {
                    if (!name) return '';
                    return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                },

                // Check if an object contains large HTML or code content (>500 chars in any string value)
                hasLargeHtml(obj) {
                    if (!obj) return false;
                    const str = JSON.stringify(obj);
                    if (str.length > 1500) return true;
                    // Check for HTML-like content in any value
                    const checkValue = (val) => {
                        if (typeof val === 'string' && val.length > 500) {
                            return val.includes('<') || val.includes('{') || val.includes('\n');
                        }
                        if (typeof val === 'object' && val !== null) {
                            return Object.values(val).some(checkValue);
                        }
                        return false;
                    };
                    return checkValue(obj);
                },

                // ─── Plan Detection & Tracking ───

                // Detect a numbered plan in the AI's text response
                detectPlanInText(text) {
                    if (this.activePlan) return; // Already have a plan

                    // Look for a numbered list pattern (at least 3 items)
                    // Matches patterns like:
                    // 1. Set up theme
                    // 2. Create Home page
                    // 3. Create About page
                    const lines = text.split('\n');
                    const planLines = [];
                    let inPlan = false;

                    for (const line of lines) {
                        const trimmed = line.trim();
                        // Match numbered list items: "1. Something" or "1) Something"
                        const match = trimmed.match(/^(\d+)[.)\s]+\s*\*{0,2}(.+?)\*{0,2}$/);
                        if (match) {
                            planLines.push({
                                num: parseInt(match[1]),
                                text: match[2].replace(/\*{1,2}/g, '').trim(),
                                status: 'pending',
                                toolMatch: null,
                            });
                            inPlan = true;
                        } else if (inPlan && trimmed === '') {
                            // Allow blank lines within the plan
                            continue;
                        } else if (inPlan && planLines.length >= 3) {
                            // End of plan
                            break;
                        } else if (inPlan) {
                            // Non-numbered line after plan started — might be end
                            break;
                        }
                    }

                    // Only activate if we found at least 3 plan steps
                    if (planLines.length >= 3) {
                        this.activePlan = {
                            steps: planLines,
                            totalSteps: planLines.length,
                        };
                        this.currentPlanStep = 0;
                    }
                },

                // Advance the plan step based on the tool being called
                advancePlanStep(toolName, params) {
                    if (!this.activePlan) return;

                    // Map tool names to plan step keywords
                    const toolKeywords = {
                        'update_theme': ['theme', 'color', 'brand', 'style'],
                        'create_page': ['page', 'create'],
                        'update_page_template': ['template', 'layout', 'design', 'page'],
                        'update_page_fields': ['content', 'field', 'text', 'page'],
                        'update_menu': ['menu', 'navigation', 'nav'],
                        'update_settings': ['settings', 'seo', 'site name', 'meta'],
                        'update_css': ['css', 'style', 'custom'],
                        'generate_image': ['image', 'hero', 'banner', 'photo', 'generate'],
                        'upload_image': ['image', 'upload', 'logo'],
                        'create_post': ['blog', 'post', 'article'],
                        'scan_website': ['scan', 'website', 'analyze', 'competitor'],
                        'render_page': ['inspect', 'render', 'check', 'verify'],
                    };

                    const keywords = toolKeywords[toolName] || [toolName.replace(/_/g, ' ')];

                    // Also use params for matching (e.g., page title)
                    const paramHints = [];
                    if (params?.title) paramHints.push(params.title.toLowerCase());
                    if (params?.slug) paramHints.push(params.slug.toLowerCase());
                    if (params?.page_slug) paramHints.push(params.page_slug.toLowerCase());

                    // Find the next unmatched plan step that matches this tool
                    for (let i = 0; i < this.activePlan.steps.length; i++) {
                        const step = this.activePlan.steps[i];
                        if (step.status !== 'pending') continue;

                        const stepText = step.text.toLowerCase();

                        // Check if any keyword matches the step text
                        const keywordMatch = keywords.some(kw => stepText.includes(kw));
                        const paramMatch = paramHints.some(hint => stepText.includes(hint));

                        if (keywordMatch || paramMatch) {
                            // Mark previous pending steps as done (they were likely completed)
                            for (let j = 0; j < i; j++) {
                                if (this.activePlan.steps[j].status === 'pending') {
                                    this.activePlan.steps[j].status = 'done';
                                }
                            }
                            step.status = 'running';
                            this.currentPlanStep = i + 1;
                            return;
                        }
                    }

                    // If no specific match, just advance to the next pending step
                    for (let i = 0; i < this.activePlan.steps.length; i++) {
                        if (this.activePlan.steps[i].status === 'pending') {
                            this.activePlan.steps[i].status = 'running';
                            this.currentPlanStep = i + 1;
                            return;
                        }
                    }
                },

                // Mark the current running plan step as done
                completePlanStep() {
                    if (!this.activePlan) return;
                    for (const step of this.activePlan.steps) {
                        if (step.status === 'running') {
                            step.status = 'done';
                            break;
                        }
                    }
                },

                // Get a user-friendly status message for tool execution
                getToolStatusMessage(toolName, status) {
                    const messages = {
                        // Read tools
                        'get_site_overview': { running: 'Analyzing your website...', done: 'Site overview loaded' },
                        'get_page_info': { running: 'Reading page details...', done: 'Page info loaded' },
                        'list_pages': { running: 'Loading page list...', done: 'Pages loaded' },
                        'get_theme': { running: 'Checking current theme...', done: 'Theme loaded' },
                        'get_settings': { running: 'Reading site settings...', done: 'Settings loaded' },
                        'get_css': { running: 'Reading current styles...', done: 'CSS loaded' },
                        'list_media': { running: 'Browsing media gallery...', done: 'Media loaded' },
                        'scan_website': { running: 'Scanning website — extracting content, structure & design...', done: 'Website scanned!' },
                        // Write tools
                        'create_page': { running: 'Building new page...', done: 'Page created!' },
                        'update_page_template': { running: 'Updating page layout & design...', done: 'Template updated!' },
                        'update_page_fields': { running: 'Updating page content...', done: 'Content updated!' },
                        'delete_page': { running: 'Removing page...', done: 'Page deleted' },
                        'update_theme': { running: 'Applying theme changes...', done: 'Theme updated!' },
                        'update_settings': { running: 'Updating site settings...', done: 'Settings saved!' },
                        'update_css': { running: 'Applying style changes...', done: 'Styles updated!' },
                        'update_menu': { running: 'Updating navigation menu...', done: 'Menu updated!' },
                        'create_post': { running: 'Writing blog post...', done: 'Post created!' },
                        'create_portfolio': { running: 'Creating portfolio item...', done: 'Portfolio item created!' },
                        'create_product': { running: 'Adding product...', done: 'Product added!' },
                        'upload_image': { running: 'Downloading & saving image to gallery...', done: 'Image saved to gallery!' },
                        'generate_image': { running: 'Generating custom image with AI — this may take a moment...', done: 'Image generated & saved!' },
                        // Page Analysis
                        'render_page': { running: 'Inspecting rendered page — checking what the user sees...', done: 'Page inspected' },
                        // System / Debugging Tools
                        'run_query': { running: 'Running database query...', done: 'Query executed' },
                        'read_file': { running: 'Reading project file...', done: 'File loaded' },
                        'write_file': { running: 'Writing file...', done: 'File saved!' },
                        'list_files': { running: 'Browsing project structure...', done: 'Files listed' },
                        'run_artisan': { running: 'Running Artisan command...', done: 'Command executed' },
                        'read_error_log': { running: 'Checking error logs for issues...', done: 'Error log checked' },
                        // Preference tools
                        'save_preference': { running: 'Saving your preference...', done: 'Preference saved!' },
                        'get_preferences': { running: 'Loading your preferences...', done: 'Preferences loaded' },
                        // SEO tools
                        'suggest_seo': { running: 'Analyzing SEO metadata...', done: 'SEO analysis complete' },
                        // Plugin tools
                        'create_plugin': { running: 'Scaffolding custom plugin...', done: 'Plugin created!' },
                    };
                    const msg = messages[toolName];
                    if (msg && msg[status]) return msg[status];
                    return status === 'running' ? `Running ${this.formatToolName(toolName)}...` : 'Done';
                },
            };
        }
        </script>
    @endif
</x-filament-panels::page>
