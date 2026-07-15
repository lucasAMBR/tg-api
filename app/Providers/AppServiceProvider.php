<?php

namespace App\Providers;

use App\Events\DevProfileCreated;
use App\Listeners\CreateRecommendationPreference;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
        Relation::morphMap([
            'developer' => \App\Models\DevProfile::class,
            'company' => \App\Models\CompanyProfile::class,
            'client' => \App\Models\ClientProfile::class,
            'admin' => \App\Models\AdminProfile::class
        ]);
    }
}
