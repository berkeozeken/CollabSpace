<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // /broadcasting/auth rotasını web + auth ile kaydeder
        Broadcast::routes(['middleware' => ['web', 'auth']]);

        // Kanal tanımlarını yükler (routes/channels.php)
        require base_path('routes/channels.php');
    }
}
