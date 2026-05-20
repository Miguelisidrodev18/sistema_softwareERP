<?php

namespace App\Providers;

use App\Models\Client;
use App\Policies\ClientePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Policies
        Gate::policy(Client::class, ClientePolicy::class);

        // Route model bindings en español
        Route::model('cliente', Client::class);
    }
}
