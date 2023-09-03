<?php

namespace Aybarsm\Laravel\Git;

use Illuminate\Support\ServiceProvider;

class GitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/git.php',
            'git'
        );

        $this->publishes([
            __DIR__.'/../config/git.php' => config_path('git.php'),
        ], 'config');

        $this->app->singleton('git', function ($app) {
            return new Git(
                config('git.top_level', getcwd())
            );
        });
    }

    public function boot(): void
    {
    }
}
