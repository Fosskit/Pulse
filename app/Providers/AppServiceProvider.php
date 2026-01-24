<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        Builder::defaultStringLength(191);
        Blueprint::macro('metaFields', function () {
            $this->timestamp('created_at')->useCurrent();
            $this->unsignedSmallInteger('created_by')->nullable();
            $this->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $this->unsignedSmallInteger('updated_by')->nullable();
            $this->softDeletes($column = 'deleted_at', $precision = 0);
            $this->unsignedSmallInteger('deleted_by')->nullable();
            $this->unsignedTinyInteger('status_id')->default(1);

            $this->index('created_by');
            $this->index('updated_by');
            $this->index('deleted_by');
            $this->index('status_id');
        });

        Blueprint::macro('commonFields', function () {
            $this->string('code')->index();
            $this->string('name')->index();
            $this->tinyText('description');
        });

        Blueprint::macro('dateFields', function () {
            $this->date('start_date')->useCurrent()->index();
            $this->date('end_date')->nullable()->default(null)->index();
        });

        Request::macro('validatedExcept', function ($except = []) {
            return Arr::except($this->validated(), $except);
        });

        Request::macro('validatedOnly', function ($except = []) {
            return Arr::only($this->validated(), $except);
        });
    }
}
