<?php // src/Facades/USPS.php

namespace M4dev\UspsShip\Facades;

use Illuminate\Support\Facades\Facade;

class USPS extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'M4dev\\UspsShip\\Services\\LabelService';
    }
}
