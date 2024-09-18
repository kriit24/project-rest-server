<?php

namespace Project\RestServer;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ProjectServiceProvider extends ServiceProvider
{

    /**
     * Register the application services.
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {

            //$this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));
            $this->commands(Commands\InstallCommand::class);
        }
    }

    /**
     * Bootstrap the application services.
     *
     * @param \Illuminate\Routing\Router $router
     */
    public function boot(Router $router, Dispatcher $event)
    {
        if ($this->app->runningInConsole()) {

            //$this->loadMigrationsFrom(realpath(__DIR__.'/../migrations'));
        }
    }
}
