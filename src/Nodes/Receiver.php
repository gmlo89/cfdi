<?php

namespace Gmlo\CFDI\Nodes;

class Receiver extends NodeCFDI
{
    public $node_name = 'cfdi:Receptor';

    protected $dictionary = [
        'rfc' => 'Rfc',
        'name' => 'Nombre',
        'how_use' => 'UsoCFDI',
    ];

    protected function getRules()
    {
        return [
            'rfc' => 'required',
            'name' => 'required',
            'how_use' => 'required|in:' . implode(',', array_keys(config('cfdi.cfdi_uses'))),
        ];
    }
}
