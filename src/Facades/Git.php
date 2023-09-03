<?php

namespace Aybarsm\Laravel\Git\Facades;

use Illuminate\Support\Facades\Facade;

class Git extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'git';
    }
}
