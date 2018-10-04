<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Receiver extends NodeCFDI
{
    public $node_name = 'nomina12:Receptor';
    protected $dictionary = [
        'curp' => 'Curp',
        'imss' => 'NumSeguridadSocial',
        'contract_type' => 'TipoContrato',
        'regime_type' => 'TipoRegimen',
        'employee_number' => 'NumEmpleado',
        'employee_position' => 'Puesto',
        'periodicity_of_payment' => 'PeriodicidadPago',
        'state' => 'ClaveEntFed',
    ];

    protected function getRules()
    {
        return [
            'curp' => 'required',
            'imss' => 'nullable',
            'contract_type' => 'required',
            'regime_type' => 'required',
            'employee_number' => 'required',
            'periodicity_of_payment' => 'required',
            'employee_position' => 'nullable',
            'state' => 'required',
        ];
    }
}
