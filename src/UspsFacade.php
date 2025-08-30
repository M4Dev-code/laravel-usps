<?php

namespace UspsShipping\Laravel;

use Illuminate\Support\Facades\Facade;

class UspsFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'usps';
    }
}
