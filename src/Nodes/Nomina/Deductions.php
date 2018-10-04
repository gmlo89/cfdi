<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Deductions extends NodeCFDI
{
    public $node_name = 'nomina12:Deducciones';
    protected $dictionary = [
        'total' => 'TotalImpuestosRetenidos',
    ];

    protected function getRules()
    {
        return [
            'total' => 'required|numeric',
        ];
    }

    public function addChild(NodeCFDI $child)
    {
        $this->childs[] = $child;
    }

    public function calcule()
    {
        foreach ($this->getChilds() as $deduction) {
            $this->total += $deduction->import;
        }
    }
}
