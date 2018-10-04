<?php

namespace Gmlo\CFDI\Nodes;

class Transmitter extends NodeCFDI
{
    public $node_name = 'cfdi:Emisor';

    protected $dictionary = [
        'rfc' => 'Rfc',
        'name' => 'Nombre',
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
