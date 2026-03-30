<?php

namespace Modules\Log\Providers;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Log\Listeners\LogScheduledTask;

class EventServiceProvider extends ServiceProvider
{
  /**
  * The event handler mappings for the application.
  *
  * @var array<string, array<int, string>>
  */
  protected $listen = [
    ScheduledTaskStarting::class => [LogScheduledTask::class],
    ScheduledTaskFinished::class => [LogScheduledTask::class]
  ];

  /**
  * Indicates if events should be discovered.
  *
  * @var bool
  */
  protected static $shouldDiscoverEvents = true;

  /**
  * Configure the proper event listeners for email verification.
  */
  protected function configureEmailVerification(): void {}
}