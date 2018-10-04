<?php

namespace Gmlo\CFDI\Nodes;

class Concept extends NodeCFDI
{
    public $node_name = 'cfdi:Concepto';
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
            'price' => 'required|numeric|min:.01',
            'description' => 'required',
            'category_code' => 'required',
            'unit' => 'required|in:' . implode(',', array_keys(sat_catalogs()->unitsList())),
            'unit_str' => 'nullable|in:' . implode(',', array_values(sat_catalogs()->unitsList())),
            'discount' => 'nullable|numeric',
            'import' => 'nullable|numeric',
        ];
    }

    public function calcule()
    {
        //$this->validate();

        $this->import = ($this->quantity * $this->price);
        if (!$this->discount) {
            $this->discount = 0;
        }
        //$this->import -= $this->discount;

        // calcule taxs
    }

    /*public function toXMLArray()
    {
        $this->validate();
        $dictionary = [
            'quantity' => 'Cantidad',
            'price' => 'ValorUnitario',
            'import' => 'Importe',
            'discount' => 'Descuento',
            'description' => 'Descripcion',
            'category_code' => 'ClaveProdServ',
            'unit' => 'ClaveUnidad',
            'unit_str' => 'Unidad',
        ];
        $data = [];
        foreach ($this->data as $key => $value) {
            if (isset($dictionary[$key])) {
                $data[$dictionary[$key]] = $value;
            }
        }
        return $data;
    }*/

    public function xx()
    {
        $concept = [
            'Cantidad' => $data->quantity,
            'ValorUnitario' => $data->price,
            'Importe' => $data->quantity * $data->price,
            'Descuento' => $discount,
            'Descripcion' => $data->description,
            'ClaveProdServ' => $data->category_code,
            'ClaveUnidad' => $data->unit,
            'Unidad' => $data->unit_str
        ];

        $concept['taxes'] = [
            'transfers' => []
        ];

        if (isset($data->iva) and $data->iva > 0) {
            $tax = (object) config('cfdi.taxes.iva');
            $import = ($data->price * $data->quantity) * $data->iva;
            $concept['taxes']['transfers'][] = [
                'TasaOCuota' => $data->iva,
                'Impuesto' => $tax->code,
                'TipoFactor' => 'Tasa',
                'Importe' => $import,
                'Base' => $data->price * $data->quantity
            ];
            if (!isset($this->tax_transferred['iva'])) {
                $this->tax_transferred['iva'] = [
                    'TasaOCuota' => config('cfdi.core.iva_rate'),
                    'Impuesto' => $tax->code,
                    'TipoFactor' => 'Tasa',
                    'Importe' => $import
                ];
            } else {
                $this->tax_transferred['iva']['Importe'] += $import;
            }
        }

        $this->concepts[] = $concept;
    }
}
