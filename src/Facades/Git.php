<?php

namespace Aybarsm\Laravel\Git\Facades;

use Aybarsm\Laravel\Git\Git as GitManager;
use Illuminate\Support\Facades\Facade;

class Git extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return GitManager::class;
    }
}
