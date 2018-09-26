<?php

namespace Gmlo\CFDI;

class Nomina extends CFDIMaker
{
    protected $type = 'N';

    public function setEmployee($data)
    {
        $data['how_use'] = 'P01';
        parent::setReceiver($data);
    }

    public function setEmployer($data)
    {
        parent::setTransmitter($data);
    }
}
