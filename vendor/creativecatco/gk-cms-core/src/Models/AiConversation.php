<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'messages',
        'context',
    ];

    protected $casts = [
        'messages' => 'array',
        'context' => 'array',
    ];

    /**
     * Get the user that owns this conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get all actions performed in this conversation.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(AiAction::class, 'conversation_id');
    }

    /**
     * Add a message to the conversation.
     */
    public function addMessage(string $role, string $content, ?array $toolCalls = null, ?array $toolResults = null): void
    {
        $messages = $this->messages ?? [];
        $message = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($toolCalls) {
            $message['tool_calls'] = $toolCalls;
        }

        if ($toolResults) {
            // For tool messages, merge in tool_call_id and name at the top level
            if ($role === 'tool' && isset($toolResults['tool_call_id'])) {
                $message['tool_call_id'] = $toolResults['tool_call_id'];
                $message['name'] = $toolResults['name'] ?? '';
            } else {
                $message['tool_results'] = $toolResults;
            }
        }

        $messages[] = $message;
        $this->messages = $messages;
        $this->save();
    }

    /**
     * Get messages formatted for the LLM API.
     * Returns array of {role, content} objects.
     */
    public function getMessagesForApi(): array
    {
        $messages = $this->messages ?? [];
        $apiMessages = [];

        foreach ($messages as $msg) {
            $apiMessage = [
                'role' => $msg['role'],
                'content' => $msg['content'] ?? '',
            ];

            // Include tool calls for assistant messages
            if ($msg['role'] === 'assistant' && !empty($msg['tool_calls'])) {
                $apiMessage['tool_calls'] = $msg['tool_calls'];
            }

            // Tool result messages
            if ($msg['role'] === 'tool') {
                $apiMessage['tool_call_id'] = $msg['tool_call_id'] ?? '';
                $apiMessage['name'] = $msg['name'] ?? '';
            }

            $apiMessages[] = $apiMessage;
        }

        return $apiMessages;
    }

    /**
     * Auto-generate a title from the first user message.
     */
    public function generateTitle(): void
    {
        if ($this->title !== 'New Conversation') {
            return;
        }

        $messages = $this->messages ?? [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'user' && !empty($msg['content'])) {
                $title = \Illuminate\Support\Str::limit($msg['content'], 60);
                $this->update(['title' => $title]);
                return;
            }
        }
    }

    /**
     * Get the count of successful actions in this conversation.
     */
    public function getSuccessfulActionsCount(): int
    {
        return $this->actions()->where('status', 'success')->count();
    }
}
