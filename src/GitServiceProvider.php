<?php

namespace Aybarsm\Laravel\Git;

use Aybarsm\Laravel\Git\Contracts\GitInterface;
use Aybarsm\Laravel\Git\Contracts\GitRepoInterface;
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

        $gitRepoConcrete = sconfig('git.concretes.GitRepo', \Aybarsm\Laravel\Git\GitRepo::class);
        $this->app->bind(GitRepoInterface::class, $gitRepoConcrete);

        $gitConcrete = sconfig('git.concretes.Git', \Aybarsm\Laravel\Git\Git::class);

        $this->app->singleton(GitInterface::class,
            fn ($app) => new $gitConcrete(sconfig('git.repos', []))
        );

        $this->app->alias(GitInterface::class, 'git');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/git.php' => config_path('git.php'),
            ], 'config');
        }
    }

    public function provides(): array
    {
        return [
            GitInterface::class, 'git',
        ];
    }
}
