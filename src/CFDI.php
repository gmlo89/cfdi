<?php

namespace Gmlo\CFDI;

use Illuminate\Support\Facades\Facade;

class CFDI extends Facade
{
    /**
     * Get the binding in the IoC container
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cfdi';
    }
}
