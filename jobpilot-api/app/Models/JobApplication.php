<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobApplication extends Model
{
    use HasFactory;

    /**
     * Fields that can be mass-assigned.
     */
    protected $fillable = [
        'user_id',
        'job_description_id',
        'analysis_id',
        'cv_id',
        'tailored_cv_id',
        'cover_letter_id',
        'job_title',
        'company_name',
        'job_url',
        'location',
        'status',
        'applied_at',
        'next_follow_up_at',
        'notes',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'applied_at' => 'date',
            'next_follow_up_at' => 'date',
        ];
    }

    /**
     * The user who owns this application.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The saved job description linked to this application.
     */
    public function jobDescription(): BelongsTo
    {
        return $this->belongsTo(JobDescription::class);
    }

    /**
     * The CV match analysis linked to this application.
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /**
     * The original CV linked to this application.
     */
    public function cv(): BelongsTo
    {
        return $this->belongsTo(Cv::class);
    }

    /**
     * The tailored CV linked to this application.
     */
    public function tailoredCv(): BelongsTo
    {
        return $this->belongsTo(TailoredCv::class);
    }

    /**
     * The generated cover letter linked to this application.
     */
    public function coverLetter(): BelongsTo
    {
        return $this->belongsTo(CoverLetter::class);
    }
}