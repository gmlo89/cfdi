<?php

namespace Gmlo\CFDI\Nodes;

class Transmitter extends NodeCFDI
{
    public $node_name = 'cfdi:emisor';

    protected $dictionary = [
        'rfc' => 'Rfc',
        'name' => 'Name',
        'tax_regime' => 'RegimenFiscal',
    ];

    protected function getRules()
    {
        return [
            'rfc' => 'required',
            'name' => 'required',
            'tax_regime' => 'required|in:' . implode(',', array_keys(sat_catalogs()->taxRegimeList())),
        ];
    }
}
