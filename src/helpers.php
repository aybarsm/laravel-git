<?php

if (! function_exists('git')) {
    function git(): mixed
    {
        return app('git');
    }
}

if (! function_exists('gitRepo')) {
    function gitRepo(string $repoName = 'default'): mixed
    {
        return app('git')->repo($repoName);
    }
}
