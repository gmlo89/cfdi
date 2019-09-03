<?php

namespace Gmlo\CFDI\Traits;

use Gmlo\CFDI\Nodes\Taxes;
use Gmlo\CFDI\Nodes\TransferredTaxes;
use Gmlo\CFDI\Nodes\TransferredTax;

trait CalculeTotals
{
    public function calcule()
    {
        parent::calcule();

        $transferreds = collect();

        foreach ($this->concepts as $concept) {
            foreach ($concept->getTransferredTaxes() as $tax) {
                $transferreds->push($tax->getData());
            }
            //$transferreds = $transferreds->merge($concept->getTransferredTaxes());
        }

        if ($transferreds->count() > 0) {
            $taxes = new Taxes();
            $transferred_taxes = new TransferredTaxes();
            $taxes->total_transferred_tax = $transferreds->sum('import');
            $transferreds = $transferreds->groupBy(function ($item) {
                return implode('|', [$item['tax'], $item['factor_type'], $item['rate']]);
            });

            foreach ($transferreds as $group) {
                $transferred_taxes->addChild(new TransferredTax([
                    'tax' => $group->first()['tax'],
                    'factor_type' => $group->first()['factor_type'],
                    'rate' => $group->first()['rate'],
                    'import' => $group->sum('import'),
                ]));
            }
            $taxes->addChild($transferred_taxes);
            $this->addChild($taxes);

            $this->total = $this->total + $taxes->total_transferred_tax;
        }
    }
}
