<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function() {
            Route::middleware('api')
            ->prefix('api/v1')
                ->group(base_path('routes/api.php'))
                ->group(base_path('routes/v1/auth.php'))
                ->group(base_path('routes/v1/equipements.php'))
                ->group(base_path('routes/v1/propertytype.php'))
                ->group(base_path('routes/v1/propertyimage.php'))
                ->group(base_path('routes/v1/adversions.php'));
        },


        
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([

        'admin' => AdminMiddleware::class

        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
