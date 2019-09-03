<?php

namespace Gmlo\CFDI\Nodes\Payments;

use Gmlo\CFDI\Nodes\NodeCFDI;

class RelatedDocument extends NodeCFDI
{
    public $node_name = 'pago10:DoctoRelacionado';

    protected $dictionary = [
        'uuid' => 'IdDocumento',
        'serie' => 'Serie',
        'folio' => 'Folio',
        'currency' => 'MonedaDR',
        'pay_method' => 'MetodoDePagoDR',
        'partiality_number' => 'NumParcialidad',
        'pre_balance' => 'ImpSaldoAnt',
        'amount' => 'ImpPagado',
        'pending_amount' => 'ImpSaldoInsoluto',
    ];

    protected function getRules()
    {
        return [
            'uuid' => 'required',
            'serie' => 'required',
            'folio' => 'required',
            'currency' => 'required',
            'pay_method' => 'required',
            'partiality_number' => 'required',
            'pre_balance' => 'required',
            'amount' => 'required',
            'pending_amount' => 'required',
        ];
    }
}
