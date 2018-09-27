<?php

namespace Gmlo\CFDI\Nodes;

class Receiver extends NodeCFDI
{
    public $node_name = 'cfdi:receptor';

    protected $dictionary = [
        'rfc' => 'Rfc',
        'name' => 'Name',
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
