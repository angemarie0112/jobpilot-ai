<?php

namespace App\Models;

use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobDescription extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'company_name',
        'location',
        'description',
        'source_url',
    ];

    /**
     * Each job description belongs to one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A job description can be used in multiple analyses.
     */
    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }
}