<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Git\Contracts\GitInterface;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class GitServiceProvider extends ServiceProvider implements DeferrableProvider
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

        $gitProvider = sconfig('git.concretes.Git', \Aybarsm\Laravel\Git\Git::class);

        $this->app->singleton(GitInterface::class, function ($app) use ($gitProvider) {
            return new $gitProvider(
                sconfig('git.concretes.GitRepo', \Aybarsm\Laravel\Git\GitRepo::class),
                sconfig('git.repos', [])
            );
        });

        $this->app->alias(GitInterface::class, 'git');
    }

    public function provides(): array
    {
        return [
            GitInterface::class, 'git',
        ];
    }
}
