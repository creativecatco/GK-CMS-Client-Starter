<?php

namespace CreativeCatCo\GkCmsCore\Http\Controllers\Api;

use CreativeCatCo\GkCmsCore\Models\AiConversation;
use CreativeCatCo\GkCmsCore\Services\Ai\AiOrchestrator;
use CreativeCatCo\GkCmsCore\Services\Ai\FileExtractor;
use CreativeCatCo\GkCmsCore\Services\Ai\LlmProviderFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    /**
     * Send a message and stream the AI response via SSE.
     *
     * POST /admin/api/ai-chat
     * Body: { conversation_id?: int, message: string }
     *
     * Returns: text/event-stream with events:
     *   - event: text, data: "chunk of text"
     *   - event: tool_start, data: {"name": "...", "params": {...}}
     *   - event: tool_result, data: {"name": "...", "result": {...}}
     *   - event: thinking, data: "status message"
     *   - event: done, data: {"conversation_id": int}
     *   - event: error, data: "error message"
     */
    public function chat(Request $request): StreamedResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|integer|exists:ai_conversations,id',
            'provider' => 'nullable|string|in:openai,anthropic,google,xai,manus',
            'model' => 'nullable|string|max:100',
        ]);

        $user = Auth::user();
        $message = $request->input('message');
        $conversationId = $request->input('conversation_id');
        $requestedProvider = $request->input('provider');
        $requestedModel = $request->input('model');

        // Check if AI is configured
        if (!LlmProviderFactory::isConfigured()) {
            return $this->sseError('AI is not configured. Please add your API key in Settings > AI Assistant.');
        }

        // Get or create conversation
        if ($conversationId) {
            $conversation = AiConversation::where('id', $conversationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$conversation) {
                return $this->sseError('Conversation not found.');
            }
        } else {
            $conversation = AiConversation::create([
                'user_id' => $user->id,
                'title' => 'New Conversation',
                'messages' => [],
                'context' => [],
            ]);
        }

        return new StreamedResponse(function () use ($conversation, $message, $requestedProvider, $requestedModel) {
            // Keep PHP running even if the browser disconnects (proxy timeout)
            // This ensures tool calls complete even if SSE connection drops
            ignore_user_abort(true);

            // Disable output buffering for real-time streaming
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Set time limit for long-running AI operations
            set_time_limit(300);

            try {
                $orchestrator = AiOrchestrator::create($conversation->user_id, $requestedProvider, $requestedModel);

                $orchestrator->handleMessage($conversation, $message, function ($type, $data) use ($conversation) {
                    $this->sendSseEvent($type, $data, $conversation);
                });

            } catch (\InvalidArgumentException $e) {
                // API key not configured, etc.
                $this->sendSseEvent('error', $e->getMessage(), $conversation);
            } catch (\Exception $e) {
                Log::error('AI chat error', [
                    'conversation_id' => $conversation->id,
                    'error' => $e->getMessage(),
                ]);
                $this->sendSseEvent('error', 'An unexpected error occurred. Please try again.', $conversation);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    /**
     * List conversations for the current user.
     *
     * GET /admin/api/ai-conversations
     */
    public function listConversations(Request $request): JsonResponse
    {
        $user = Auth::user();

        $conversations = AiConversation::where('user_id', $user->id)
            ->orderBy('updated_at', 'desc')
            ->select(['id', 'title', 'updated_at', 'created_at'])
            ->limit(50)
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'updated_at' => $conv->updated_at->diffForHumans(),
                    'created_at' => $conv->created_at->toIso8601String(),
                ];
            });

        return response()->json(['conversations' => $conversations]);
    }

    /**
     * Get a specific conversation with its messages.
     *
     * GET /admin/api/ai-conversations/{id}
     */
    public function getConversation(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Get actions for this conversation
        $actions = $conversation->actions()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($action) {
                return [
                    'id' => $action->id,
                    'tool_name' => $action->tool_name,
                    'tool_input' => $action->tool_input,
                    'tool_output' => $action->tool_output,
                    'status' => $action->status,
                    'can_rollback' => $action->canRollback(),
                    'created_at' => $action->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'messages' => $conversation->messages ?? [],
                'actions' => $actions,
                'created_at' => $conversation->created_at->toIso8601String(),
                'updated_at' => $conversation->updated_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Delete a conversation.
     *
     * DELETE /admin/api/ai-conversations/{id}
     */
    public function deleteConversation(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $conversation = AiConversation::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$conversation) {
            return response()->json(['error' => 'Conversation not found'], 404);
        }

        // Delete associated actions first
        $conversation->actions()->delete();
        $conversation->delete();

        return response()->json(['success' => true, 'message' => 'Conversation deleted']);
    }

    /**
     * Undo a specific action.
     *
     * POST /admin/api/ai-actions/{id}/undo
     */
    public function undoAction(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $action = \CreativeCatCo\GkCmsCore\Models\AiAction::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$action) {
            return response()->json(['error' => 'Action not found'], 404);
        }

        if (!$action->canRollback()) {
            $reason = 'This action cannot be undone.';
            if ($action->status === 'rolled_back') {
                $reason = 'This action has already been undone.';
            } elseif ($action->status === 'failed') {
                $reason = 'This action failed originally and cannot be undone.';
            } elseif (empty($action->rollback_data)) {
                $reason = 'No rollback data was captured for this action. This may happen if the action type does not support undo.';
            }
            return response()->json(['error' => $reason], 422);
        }

        try {
            $orchestrator = AiOrchestrator::create();
            $success = $orchestrator->getToolExecutor()->rollbackAction($action);

            if ($success) {
                return response()->json(['success' => true, 'message' => 'Action undone successfully']);
            } else {
                return response()->json(['error' => 'Failed to undo action'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Undo action failed', ['action_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to undo action: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Upload a file — handles both documents (text extraction) and images (save to gallery).
     *
     * POST /admin/api/ai-upload
     * Body: multipart/form-data with 'file' field
     *
     * For documents: Returns extracted text content for AI context
     * For images: Saves to media gallery and returns URL for use in pages
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check if this is an image
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        if (in_array($mimeType, $imageTypes) || in_array($extension, $imageExtensions)) {
            return $this->handleImageUpload($file);
        }

        // Otherwise treat as a document for text extraction
        $extractor = new FileExtractor();

        if (!$extractor->isSupported($file)) {
            $allSupported = array_merge(FileExtractor::getSupportedExtensions(), $imageExtensions);
            $supported = implode(', ', array_unique($allSupported));
            return response()->json([
                'success' => false,
                'error' => "Unsupported file type. Supported formats: {$supported}",
            ], 422);
        }

        $result = $extractor->extract($file);

        return response()->json($result);
    }

    /**
     * Handle image file upload — save to media gallery.
     */
    protected function handleImageUpload(\Illuminate\Http\UploadedFile $file): JsonResponse
    {
        try {
            $folder = 'uploads';
            $filename = \Illuminate\Support\Str::slug(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            );
            $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';

            // Map common extensions
            $extMap = ['jpeg' => 'jpg', 'svg' => 'svg'];
            $extension = $extMap[$extension] ?? $extension;

            $targetName = $filename . '.' . $extension;
            $basePath = config('cms.media_upload_path', 'cms/media') . '/' . $folder;
            $path = $basePath . '/' . $targetName;

            // Ensure unique filename
            $counter = 1;
            while (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                $path = $basePath . '/' . $filename . '-' . $counter . '.' . $extension;
                $counter++;
            }

            // Store the file
            \Illuminate\Support\Facades\Storage::disk('public')->put(
                $path,
                file_get_contents($file->getRealPath())
            );

            // Save to media library database
            $media = \CreativeCatCo\GkCmsCore\Models\Media::create([
                'filename' => basename($path),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'alt_text' => '',
                'folder' => $folder,
            ]);

            $url = asset('storage/' . $path);

            return response()->json([
                'success' => true,
                'type' => 'image',
                'filename' => $file->getClientOriginalName(),
                'url' => $url,
                'path' => $path,
                'media_id' => $media->id,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        } catch (\Exception $e) {
            Log::error('Image upload failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Failed to upload image: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import content from a Google Docs URL.
     *
     * POST /admin/api/ai-import-gdoc
     * Body: { url: string }
     *
     * Returns: JSON with extracted text content
     */
    public function importGoogleDoc(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');

        // Verify it's a Google Docs URL
        if (!preg_match('/docs\.google\.com\/document/', $url)) {
            return response()->json([
                'success' => false,
                'error' => 'Please provide a valid Google Docs URL (e.g., https://docs.google.com/document/d/...)',
            ], 422);
        }

        $result = FileExtractor::extractFromGoogleDoc($url);

        return response()->json($result);
    }

    /**
     * Send an SSE event to the client.
     */
    protected function sendSseEvent(string $type, mixed $data, ?AiConversation $conversation = null): void
    {
        $payload = match ($type) {
            'text' => $data,
            'thinking' => $data,
            'error' => $data,
            'tool_start' => json_encode($data),
            'tool_result' => json_encode($data),
            'done' => json_encode([
                'conversation_id' => $conversation?->id,
            ]),
            default => json_encode($data),
        };

        echo "event: {$type}\n";
        echo "data: {$payload}\n\n";

        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }

    /**
     * Return an SSE error response (for pre-stream errors).
     */
    protected function sseError(string $message): StreamedResponse
    {
        return new StreamedResponse(function () use ($message) {
            echo "event: error\ndata: {$message}\n\n";
            echo "event: done\ndata: {}\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
