<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Exceptions\CFDIException;
use Gmlo\CFDI\Utils\XML;
use Validator;
use Gmlo\CFDI\Nodes\Receiver;
use Gmlo\CFDI\Nodes\Transmitter;
use Gmlo\CFDI\Nodes\Concept;
use Gmlo\CFDI\Nodes\Concepts;

class CFDIMaker
{
    protected $type = null;
    protected $data = [];
    protected $complements = null;
    protected $concepts = null;
    protected $receiver;
    protected $transmitter;
    protected $tax_transferred = [];

    public $xml;
    public $config;
    public $general;

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    public function __construct($data, $key_path, $cer_path)
    {
        if ($this->type) {
            $data['type'] = $this->type;
        }
        $this->data = $data;
        $this->xml = new XML($key_path, $cer_path);
    }

    public function generate()
    {
        $this->setGenerals($this->data);
        $this->xml->generate($this);
    }

    /*public function setComplementIne($data)
    {
        $data = (object)$data;
        // Validaciones complemento INE
        $errors = [];
        if( $data->process_type == 'Ordinario' )
        {
            if( !$data->committee_type )
                $errors[] = 'Cuando el tipo de proceso es Ordinario debes especificar el tipo de comite';
            else if( $data->committee_type == 'Ejecutivo Nacional' )
            {
                $data->state = null;
            }
            else if( $data->committee_type == 'Ejecutivo Estatal' )
            {
                $data->id_accounting = null;
                if( !$data->state  || !$data->scope )
                    $errors[] = 'Cuando el tipo de comite sea Ejecutivo Estatal debes seleccionar la entidad y el ambito.';
            }
            else if( $data->committee_type == 'Directivo Estatal' )
            {
                if( !$data->state || !$data->scope )
                    $errors[] = 'Cuando el tipo de comite sea Directivo Estatal debes seleccionar la entidad y el ambito.';
            }
        }
        else {
            if( !$data->state || !$data->scope )
                $errors[] = 'Cuando el tipo de proceso sea Precampaña o Campaña se debe registrar la entidad y el ambito.';

            $data->committee_type   = null;
            $data->id_accounting    = null;
        }


        throw new BillerException("Ocurrio un error mientras se realizaba el timbrado de tu CFDI", 0, null, ['errors' => $response->Incidencias->Incidencia]);
    }*/

    public function setGenerals($data)
    {
        // Calcule totals
        $data['total'] = 0;
        $data['subtotal'] = 0;
        $data['discount'] = 0;
        foreach ($this->concepts->getChilds() as $concept) {
            $data['subtotal'] += $concept->import;
            $data['discount'] += $concept->discount_amount;
        }

        $data['total'] = $data['subtotal'];

        $validator = Validator::make($data, [
            'pay_way' => 'required|in:' . implode(',', array_keys(sat_catalogs()->payWaysList())),
            'subtotal' => 'required|numeric|min:.01',
            'discount' => 'nullable|numeric',
            'total' => 'required|numeric|min:.01',
            'type' => 'required|in:I,E,N',
            'pay_method' => 'required|in:' . implode(',', array_keys(config('cfdi.pay_methods'))),
            'zip_code' => 'nullable|required',
            'serie' => 'required',
            'folio' => 'required',
            'cert_number' => 'required',
        ], trans('CFDI::validation_messages'));
        if ($validator->fails()) {
            $message = 'Tienes un error con la información general.';
            throw new CFDIException($message, 0, null, ['errors' => $validator->errors()->all()]);
        }

        $data = (object)$data;
        $this->general = $this->other_namespaces + [
            'xmlns:cfdi' => 'http://www.sat.gob.mx/cfd/3',

            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv33.xsd',
            'Fecha' => date('Y-m-d') . 'T' . date('H:i:s'),
            'Version' => '3.3',
            'Moneda' => 'MXN',

            // From data
            'FormaPago' => $data->pay_way,
            'SubTotal' => number_format($data->subtotal, 2, '.', ''), // Before taxes and discounts
            'Descuento' => $data->discount ? number_format($data->discount, 2, '.', '') : null,
            'Total' => number_format($data->total, 2, '.', ''), // subtotal - discounts + Taxes transferred -Taxes withheld
            'TipoDeComprobante' => $data->type,
            'MetodoPago' => $data->pay_method,
            'LugarExpedicion' => $data->zip_code,
            'Serie' => $data->serie,
            'Folio' => $data->folio,
            'NoCertificado' => $data->cert_number,

            // Fields to fill by PAC
            'Sello' => '@',
            'Certificado' => '@',

            // Optionals
            'CondicionesDePago' => null,
            'TipoCambio' => null,
            'Confirmacion' => null, // When the amount is very high
        ];
    }

    /**
    * Add concept
    */
    public function addConcept(Concept $concept)
    {
        /*
        $validator = Validator::make($data, [
            'quantity' => 'required|numeric|min:.01',
            'price' => 'required|numeric|min:.01',
            'description' => 'required',
            'category_code' => 'required',
            'unit' => 'required|in:' . implode(',', array_keys(sat_catalogs()->unitsList())),
            'unit_str' => 'required|in:' . implode(',', array_values(sat_catalogs()->unitsList())),
        ], trans('CFDI::validation_messages'));
        if ($validator->fails()) {
            $message = 'Tienes un error con la información de un concepto.';
            if (isset($data['description'])) {
                $message = 'Tienes un error con la información del concepto: ' . $data['description'];
            }
            throw new CFDIException($message, 0, null, ['errors' => $validator->errors()->all()]);
        }

        $data = (object)$data;
        $discount = null;
        if (isset($data->discount)) {
            $discount = ($data->quantity * $data->price) * $data->discount;
        }
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
        */
        if (!$this->concepts) {
            $this->concepts = new Concepts();
        }
        $concept->calcule();
        $this->concepts->addChild($concept);
    }

    /**
    * Set receiver
    */
    public function setReceiver(Receiver $receiver)
    {
        $this->receiver = $receiver;
    }

    /**
    * Set transmitter
    */
    public function setTransmitter(Transmitter $transmitter)
    {
        $this->transmitter = $transmitter;
    }
}
