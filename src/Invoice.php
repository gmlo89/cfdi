<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Nodes\Receipt;
use Gmlo\CFDI\Traits\CalculeTotals;

class Invoice extends Receipt
{
    use CalculeTotals;

    public function __construct($data = [], $other_rules = [])
    {
        $data['type'] = 'I';

        parent::__construct($data);
    }
}
