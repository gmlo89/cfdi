<?php

namespace Gmlo\CFDI\Nodes\Payments;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Payment extends NodeCFDI
{
    public $node_name = 'pago10:Pago';

    protected $dictionary = [
        'date' => 'FechaPago',
        'method' => 'FormaDePagoP',
        'currency' => 'MonedaP',
        'exchange_rate' => 'TipoCambioP',
        'amount' => 'Monto',
    ];

    protected function getRules()
    {
        return [
            'date' => 'required',
            'amount' => 'required|numeric',
            'method' => 'required',
            'currency' => 'required',
            'exchange_rate' => 'nullable'
        ];
    }
}
