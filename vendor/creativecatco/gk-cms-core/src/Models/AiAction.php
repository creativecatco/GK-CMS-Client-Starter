<?php

namespace CreativeCatCo\GkCmsCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'tool_name',
        'tool_input',
        'tool_output',
        'rollback_data',
        'status',
        'created_at',
    ];

    protected $casts = [
        'tool_input' => 'array',
        'tool_output' => 'array',
        'rollback_data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the conversation this action belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    /**
     * Get the user who triggered this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Mark the action as successful.
     */
    public function markSuccess(array $output): void
    {
        $this->update([
            'status' => 'success',
            'tool_output' => $output,
        ]);
    }

    /**
     * Mark the action as failed.
     */
    public function markFailed(array $output): void
    {
        $this->update([
            'status' => 'failed',
            'tool_output' => $output,
        ]);
    }

    /**
     * Mark the action as rolled back.
     */
    public function markRolledBack(): void
    {
        $this->update(['status' => 'rolled_back']);
    }

    /**
     * Check if this action can be rolled back.
     */
    public function canRollback(): bool
    {
        return $this->status === 'success' && !empty($this->rollback_data);
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }
}
