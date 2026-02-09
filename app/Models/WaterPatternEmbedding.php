<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaterPatternEmbedding extends Model
{
    protected $fillable = [
        'device_id',
        'system_type',
        'pattern_text',
        'embedding',
        'embedding_model',
        'embedding_dim',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'embedding_dim' => 'integer',
    ];

    /**
     * Get the device that owns this pattern embedding
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
