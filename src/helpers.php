<?php

use Aybarsm\Laravel\Git\Contracts\GitRepoInterface;

if (! function_exists('git')) {
    function git(): mixed
    {
        return app('git');
    }
}

if (! function_exists('gitRepo')) {
    function gitRepo(string $repoName = 'default'): GitRepoInterface
    {
        return app('git')->repo($repoName);
    }
}
