<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Nodes\CfdiRelated;
use Gmlo\CFDI\Nodes\CfdiRelateds;
use Gmlo\CFDI\Nodes\Receipt;
use Gmlo\CFDI\Traits\CalculeTotals;

class Expense extends Receipt
{
    use CalculeTotals;

    public function __construct($data = [], $other_rules = [])
    {
        $data['type'] = 'E';

        parent::__construct($data);
    }

    public function addCfdiRelated($uuid, $relation_type)
    {
        $cfdi_related = new CfdiRelated([
            'uuid' => $uuid
        ]);
        $cfdi_relateds = new CfdiRelateds([
            'relation_type' => $relation_type
        ]);
        $cfdi_relateds->addChild($cfdi_related);
        $this->addChild($cfdi_relateds);
    }
}
