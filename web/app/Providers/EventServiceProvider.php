<?php

namespace App\Providers;

use App\Events\CodebookDeleting;
use App\Events\ProjectDeleting;
use App\Events\SourceDeleting;
use App\Events\UserDeletingItself;
use App\Listeners\DeleteRelatedCodes;
use App\Listeners\DeleteRelatedSources;
use App\Listeners\DeleteSourceFiles;
use App\Listeners\ListenUserDeletingItself;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ProjectDeleting::class => [
            DeleteRelatedSources::class,
        ],
        SourceDeleting::class => [
            DeleteSourceFiles::class,
        ],
        CodebookDeleting::class => [
            DeleteRelatedCodes::class,
        ],
        UserDeletingItself::class => [
            ListenUserDeletingItself::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
