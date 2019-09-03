<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Nodes\Receipt;
use Gmlo\CFDI\Nodes\Complement;
use Gmlo\CFDI\Nodes\Payments\Payments;
use Gmlo\CFDI\Nodes\Payments\Payment as Payment_complement;
use Gmlo\CFDI\Nodes\Payments\RelatedDocument;
use Gmlo\CFDI\Nodes\Concept;

class Payment extends Receipt
{
    public function __construct($data = [], $other_rules = [])
    {
        $data['type'] = 'P';

        parent::__construct($data);
        $this->data['currency'] = 'XXX';
        $this->complement = new Complement();
    }

    protected $complement = null;

    public function calcule()
    {
        $concept = new Concept([
            'quantity' => 1,
            'price' => 0,
            'description' => 'Pago',
            'category_code' => '84111506',
            'unit' => 'ACT',
        ]);
        $this->addConcept($concept);
        parent::calcule();
        $this->addChild($this->complement);
    }

    public function addPaymentComplement($method, $amount, $reference, $cfdi_original, $partiality_number, $amount_paid, $currency, $exchange_rate)
    {
        if ($currency == 'MXN') {
            $exchange_rate = null;
        }

        $this->data['xmlns_pago10'] = 'http://www.sat.gob.mx/Pagos';
        $this->data['xsi_schemaLocation'] .= ' http://www.sat.gob.mx/Pagos http://www.sat.gob.mx/sitio_internet/cfd/Pagos/Pagos10.xsd';

        $payments = new Payments();
        $payment = new Payment_complement([
            'date' => date('Y-m-d') . 'T' . date('H:i:s'),
            'method' => $method,
            'currency' => $currency,
            'exchange_rate' => $exchange_rate,
            'amount' => number_format($amount, 2, '.', ''),
        ]);

        $pre_balance = number_format($cfdi_original->total - ($amount_paid), 2, '.', '');
        $amount_ = number_format($amount, 2, '.', '');
        $pending_amount = number_format($cfdi_original->total - ($amount_paid + $amount), 2, '.', '');

        if ($pending_amount < 0 and $pending_amount < 1) {
            $amount_ = $pre_balance;
            $pending_amount = number_format(0, 2, '.', '');
        }

        $docto = new RelatedDocument([
            'uuid' => $cfdi_original->uuid,
            'serie' => $cfdi_original->serie,
            'folio' => $cfdi_original->folio,
            'currency' => $cfdi_original->currency,
            'pay_method' => $cfdi_original->payment_method,
            'partiality_number' => $partiality_number,
            'pre_balance' => $pre_balance,
            'amount' => $amount_,
            'pending_amount' => $pending_amount,
        ]);
        $payment->addChild($docto);
        $payments->addChild($payment);
        $this->complement->addChild($payments);
    }
}
