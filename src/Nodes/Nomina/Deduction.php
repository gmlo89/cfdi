<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Deduction extends NodeCFDI
{
    public $node_name = 'nomina12:Deduccion';

    protected $dictionary = [
        'type' => 'TipoDeduccion',
        'code' => 'Clave',
        'concept' => 'Concepto',
        'import' => 'Importe',
    ];

    protected function getRules()
    {
        return [
            'type' => 'required',
            'code' => 'required',
            'concept' => 'required',
            'import' => 'required|numeric',
        ];
    }
}
