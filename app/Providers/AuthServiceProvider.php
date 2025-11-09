<?php

namespace App\Providers;

use App\Models\Team;
use App\Policies\TeamPolicy;
use App\Models\Task;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Team::class => TeamPolicy::class,
        \App\Models\Message::class => \App\Policies\MessagePolicy::class,
        Task::class => TaskPolicy::class, // ✅ Task policy kaydı
    ];

    public function boot(): void
    {
        //
    }
}
