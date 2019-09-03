<?php

namespace Gmlo\CFDI\Nodes;

class Concept extends NodeCFDI
{
    public $node_name = 'cfdi:Concepto';

    protected $transferred_taxes = [];

    protected $dictionary = [
        'quantity' => 'Cantidad',
        'price' => 'ValorUnitario',
        'import' => 'Importe',
        'discount' => 'Descuento',
        'description' => 'Descripcion',
        'category_code' => 'ClaveProdServ',
        'unit' => 'ClaveUnidad',
        'unit_str' => 'Unidad',
    ];

    protected function getRules()
    {
        return [
            'quantity' => 'required|numeric|min:.01',
            'price' => 'required|numeric|min:0',
            'description' => 'required',
            'category_code' => 'required',
            'unit' => 'required|in:' . implode(',', array_keys(sat_catalogs()->unitsList())),
            'unit_str' => 'nullable',
            'discount' => 'nullable|numeric',
            'import' => 'nullable|numeric',
        ];
    }

    public function calcule()
    {
        //$this->validate();

        if (count($this->transferred_taxes) > 0) {
            $taxes = new Taxes();
            $transferred_taxes = new TransferredTaxes();

            foreach ($this->transferred_taxes as $tax) {
                $tax->calcule();
                $transferred_taxes->addChild($tax);
            }

            $taxes->addChild($transferred_taxes);
            $this->addChild($taxes);
        }

        $this->import = ($this->quantity * $this->price);
        /*if (!$this->discount) {
            $this->discount = 0;
        }*/
        //$this->import -= $this->discount;

        // calcule taxs
    }

    public function addTransferredTax(TransferredTax $tax)
    {
        $this->transferred_taxes[] = $tax;
    }

    public function getTransferredTaxes()
    {
        return $this->transferred_taxes;
    }
}
