<?php

namespace Gmlo\CFDI\Nodes;

class CfdiRelated extends NodeCFDI
{
    public $node_name = 'cfdi:CfdiRelacionado';

    protected $dictionary = [
        'uuid' => 'UUID',
    ];

    protected function getRules()
    {
        return [
            'uuid' => 'required',
        ];
    }
}
