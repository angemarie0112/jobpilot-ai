<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cv extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'extracted_text',
        'status',
        'error_message',
    ];

    /**
     * Each CV belongs to one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A CV can be used in multiple analyses.
     */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }
}