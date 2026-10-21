<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailoredCv extends Model
{
    /**
     * Attributes that can be mass assigned.
     */
    protected $fillable = [
        'user_id',
        'cv_id',
        'analysis_id',
        'title',
        'content',
        'model_used',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    /**
     * The user who owns this tailored CV.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The original uploaded CV.
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * The match analysis used for tailoring.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
}