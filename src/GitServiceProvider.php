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

        $gitProvider = config('git.providers.git', \Aybarsm\Laravel\Git\Git::class);

        $cacheConfig = array_merge([
            'enabled' => true,
            'store' => null,
            'key' => 'git',
            'tag' => null,
            'expires' => 0,
        ],
            config('git.cache', [])
        );

        $this->app->singleton('git', function ($app) use ($gitProvider, $cacheConfig) {
            return new $gitProvider(
                config('git.providers.gitRepo', \Aybarsm\Laravel\Git\GitRepo::class),
                config('git.repos', []),
                $cacheConfig,
            );
        });
    }

    public function boot(): void
    {
    }
}
