<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Source::class => \App\Policies\SourcePolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return in_array($user->email, [
                'belli@uni-bremen.de',
                'fhohmann@uni-bremen.de',
                's_ufzdc2@uni-bremen.de',

            ]);
        });
    }
}
