<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverLetter extends Model
{
    /**
     * Attributes that can be mass-assigned.
     */
    protected $fillable = [
        'user_id',
        'analysis_id',
        'title',
        'content',
        'model_used',
    ];
    /**
     * Each cover letter belongs to one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each cover letter belongs to one analysis.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}