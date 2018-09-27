<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Nomina extends NodeCFDI
{
    public $node_name = 'nomina12:nomina';

    protected $data = [
        'version' => '1.2'
    ];

    protected $dictionary = [
        'version' => 'Version',
        'type' => 'TipoNomina',
        'pay_date' => 'FechaPago',
        'pay_start_date' => 'FechaInicialPago',
        'pay_end_date' => 'FechaFinalPago',
        'pay_days' => 'NumDiasPagados',
        'total_perceptions' => 'TotalPercepciones',
        'total_deductions' => 'TotalDeducciones',
    ];

    protected function getRules()
    {
        return [
            'type' => 'required',
            'pay_date' => 'required',
            'pay_start_date' => 'required',
            'pay_end_date' => 'required',
            'pay_days' => 'required',
            'total_perceptions' => 'required',
            'total_deductions' => 'required',
        ];
    }

    public function calcule()
    {
        foreach ($this->getChilds() as $child) {
            if (get_class($child) == Perceptions::class) {
                $this->total_perceptions = $child->total_salaries;
            } elseif (get_class($child) == Deductions::class) {
                $this->total_deductions = $child->total;
            }
        }
    }
}
