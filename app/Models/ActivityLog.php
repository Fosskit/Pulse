<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityLog extends Model
{
    protected $connection = 'logging';

    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'description',
        'ip_address',
        'user_agent',
    ];

    /**
     * Get the user that performed the activity.
     */
    public function user(): BelongsTo
    {
        $relation = $this->belongsTo(User::class, 'fosskit_log.activity_log.user_id', 'fosskit_pulse.users.id');
        return $relation;
    }
}
