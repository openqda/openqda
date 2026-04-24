<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Code;
use App\Models\Note;
use App\Models\Project;
use App\Models\Source;
use App\Policies\CodesPolicy;
use App\Policies\NotePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\SourcePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Source::class => SourcePolicy::class,
        Project::class => ProjectPolicy::class,
        Code::class => CodesPolicy::class,
        Note::class => NotePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
