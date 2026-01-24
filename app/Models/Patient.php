<?php

namespace App\Models;

use App\Models\Reference\MaritalStatus;
use App\Models\Reference\Nationality;
use App\Models\Reference\Occupation;
use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Patient extends Model
{
    use LogsModelActivity;

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
}
