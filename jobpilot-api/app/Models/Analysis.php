<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Analysis extends Model
{
    /**
     * Attributes that can be mass-assigned.
     */
    protected $fillable = [
        'user_id',
        'cv_id',
        'job_description_id',
        'status',
        'match_score',
        'matching_skills',
        'missing_skills',
        'recommendations',
        'summary',
        'model_used',
        'error_message',
    ];

    /**
     * Cast JSONB columns to PHP arrays.
     */
    protected function casts(): array
    {
        return [
            'match_score' => 'integer',
            'matching_skills' => 'array',
            'missing_skills' => 'array',
            'recommendations' => 'array',
        ];
    }

    /**
     * Each analysis belongs to one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each analysis belongs to one CV.
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * Each analysis belongs to one job description.
     */
    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    /**
     * An analysis can have multiple cover letters.
     */
    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class);
    }

    /**
     * An analysis can have multiple tailored CVs.
     */
    public function tailoredCvs(): HasMany
    {
        return $this->hasMany(TailoredCv::class);
    }

    /**
     * An analysis can be linked to multiple job applications.
     */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}