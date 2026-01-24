<?php

namespace App\Models\Reference;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsModelActivity;

abstract class BaseReference extends Model
{
    use SoftDeletes, LogsModelActivity;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status_id',
    ];

    protected $casts = [
        'status_id' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status_id', 1);
    }
}
