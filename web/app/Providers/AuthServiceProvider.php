<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Code;
use App\Policies\CodesPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

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
        Code::class => CodesPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
