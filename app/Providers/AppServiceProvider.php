<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Requirement;
use App\Models\Sprint;
use App\Policies\ClientePolicy;
use App\Policies\ProyectoPolicy;
use App\Policies\RequerimientoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Policies
        Gate::policy(Client::class,       ClientePolicy::class);
        Gate::policy(Project::class,      ProyectoPolicy::class);
        Gate::policy(Requirement::class,  RequerimientoPolicy::class);

        // Route model bindings en español
        Route::model('cliente',       Client::class);
        Route::model('proyecto',      Project::class);
        Route::model('requerimiento', Requirement::class);
        Route::model('sprint',        Sprint::class);
        Route::model('cotizacion',    Quote::class);
    }
}
