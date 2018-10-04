<?php

namespace Gmlo\CFDI\Nodes\Nomina;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Perceptions extends NodeCFDI
{
    public $node_name = 'nomina12:Percepciones';
    protected $dictionary = [
        'total_salaries' => 'TotalSueldos',
        'total_taxed' => 'TotalGravado',
        'total_exempt' => 'TotalExento',
    ];

    protected function getRules()
    {
        return [
            'total_salaries' => 'required|numeric',
            'total_taxed' => 'required|numeric',
            'total_exempt' => 'required|numeric',
        ];
    }

    public function addChild(NodeCFDI $child)
    {
        $this->childs[] = $child;
    }

    public function calcule()
    {
        foreach ($this->getChilds() as $perception) {
            $this->total_salaries += $perception->import_taxed + $perception->import_exempt;
            $this->total_taxed += $perception->import_taxed ;
            $this->total_exempt += $perception->import_exempt;
        }
    }
}
