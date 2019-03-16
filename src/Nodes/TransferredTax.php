<?php

namespace Gmlo\CFDI\Nodes;

class TransferredTax extends NodeCFDI
{
    public $node_name = 'cfdi:Traslado';

    protected $dictionary = [
        'base' => 'Base',
        'tax' => 'Impuesto',
        'factor_type' => 'TipoFactor',
        'rate' => 'TasaOCuota',
        'import' => 'Importe',
    ];

    protected function getRules()
    {
        return [
            'base' => 'numeric',
            'tax' => 'required|in:' . implode(',', sat_catalogs()->taxesCodesList()),
            'factor_type' => 'required|in:' . implode(',', config('cfdi.others.factor_types')),
            'rate' => 'required|numeric',
            'import' => 'required|numeric',
        ];
    }

    public function calcule()
    {
        if (!$this->import) {
            $this->import = $this->base * $this->rate;
        }
        $this->rate = number_format($this->rate, 6, '.', '');
    }
}
