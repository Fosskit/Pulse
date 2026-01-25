<?php

namespace App\Models;

use App\Models\Reference\MaritalStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\Occupation;
use App\Models\Reference\PatientStatus;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, LogsModelActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'surname',
        'name',
        'telephone',
        'sex',
        'birthdate',
        'multiple_birth',
        'nationality_id',
        'marital_status_id',
        'occupation_id',
        'deceased',
        'deceased_at',
        'status_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'deceased_at' => 'date',
        'multiple_birth' => 'boolean',
        'deceased' => 'boolean',
    ];

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function maritalStatus(): BelongsTo
    {
        return $this->belongsTo(MaritalStatus::class);
    }

    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(PatientStatus::class, 'status_id');
    }

    /**
     * Scope a query to filter patients by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $statusId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }
}
