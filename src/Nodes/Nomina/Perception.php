<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Perception extends NodeCFDI
{
    public $node_name = 'nomina12:Percepcion';
    protected $dictionary = [
        'type' => 'TipoPercepcion',
        'code' => 'Clave',
        'concept' => 'Concepto',
        'import_taxed' => 'ImporteGravado',
        'import_exempt' => 'ImporteExento',
    ];

    protected function getRules()
    {
        return [
            'type' => 'required',
            'code' => 'required',
            'concept' => 'required',
            'import_taxed' => 'required|numeric',
            'import_exempt' => 'required|numeric',
        ];
    }
}
