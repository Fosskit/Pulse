<?php

namespace App\Traits;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

trait LogsModelActivity
{
    public static function bootLogsModelActivity()
    {
        static::created(function (Model $model) {
            self::logActivity('created', $model);
        });
        static::updated(function (Model $model) {
            self::logActivity('updated', $model);
        });
        static::deleted(function (Model $model) {
            self::logActivity('deleted', $model);
        });
    }

    protected static function logActivity(string $event, Model $model)
    {
        $modelName = class_basename($model);
        $config = config('activitylog.model_logging', []);
        if (isset($config[strtolower($modelName)]) && !$config[strtolower($modelName)]) {
            return;
        }
        App::make(ActivityLogService::class)->log(
            action: strtolower($modelName) . '.' . $event,
            model: $modelName,
            modelId: $model->getKey(),
            description: $modelName . ' ' . $event,
            request: function_exists('request') ? request() : null,
        );
    }
}
