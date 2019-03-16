<?php

namespace Gmlo\CFDI\Nodes;

class Taxes extends NodeCFDI
{
    public $node_name = 'cfdi:Impuestos';

    protected $dictionary = [
        'total_transferred_tax' => 'TotalImpuestosTrasladados',
    ];

    protected function getRules()
    {
        return [
            'total_transferred_tax' => 'numeric',
        ];
    }
}
