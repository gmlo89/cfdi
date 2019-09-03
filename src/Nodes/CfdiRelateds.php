<?php

namespace Gmlo\CFDI\Nodes;

class CfdiRelateds extends NodeCFDI
{
    public $node_name = 'cfdi:CfdiRelacionados';

    protected $dictionary = [
        'relation_type' => 'TipoRelacion',
    ];

    protected function getRules()
    {
        return [
            'relation_type' => 'required',
        ];
    }
}
