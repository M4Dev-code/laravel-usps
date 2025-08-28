<?php // src/Facades/USPS.php

namespace m4dev\UspsShip\Facades;

use Illuminate\Support\Facades\Facade;

class USPS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'm4dev\\UspsShip\\Services\\LabelService';
    }
}
