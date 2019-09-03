<?php

namespace Gmlo\CFDI\Nodes\Payments;

use Gmlo\CFDI\Nodes\NodeCFDI;

class Payments extends NodeCFDI
{
    public $node_name = 'pago10:Pagos';

    public function __construct($data = [], $other_rules = [])
    {
        $data['version'] = '1.0';
        parent::__construct($data);
    }

    protected $dictionary = [
        'version' => 'Version',
    ];

    protected function getRules()
    {
        return [
            'version' => 'required',
        ];
    }
}
