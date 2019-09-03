<?php

namespace Gmlo\CFDI\Nodes;

use Gmlo\CFDI\Utils\XML;

class Receipt extends NodeCFDI
{
    public $node_name = 'cfdi:Comprobante';
    protected $key_path;
    protected $cer_path;
    protected $receiver;
    protected $transmitter;
    protected $concepts;
    protected $dictionary = [
        'pay_way' => 'FormaPago',
        'subtotal' => 'SubTotal',
        'discount' => 'Descuento',
        'total' => 'Total',
        'type' => 'TipoDeComprobante',
        'pay_method' => 'MetodoPago',
        'zip_code' => 'LugarExpedicion',
        'serie' => 'Serie',
        'folio' => 'Folio',
        'cert_number' => 'NoCertificado',
        'cert' => 'Certificado',
        'date' => 'Fecha',
        'version' => 'Version',
        'currency' => 'Moneda',
        'exchange_rate' => 'TipoCambio',
        'seal' => 'Sello',
        'xmlns_cfdi' => 'xmlns:cfdi',
        'xmlns_xsi' => 'xmlns:xsi',
        'xsi_schemaLocation' => 'xsi:schemaLocation',
        'xmlns_nomina12' => 'xmlns:nomina12',
        'xmlns_pago10' => 'xmlns:pago10'
    ];
    protected $data = [];

    protected $xml;

    public function setKeyPath($path)
    {
        $this->key_path = $path;
    }

    public function setCerPath($path)
    {
        $this->cer_path = $path;
    }

    public function __construct($data = [], $other_rules = [])
    {
        $this->data = [
            'xmlns_cfdi' => 'http://www.sat.gob.mx/cfd/3',
            'xmlns_xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi_schemaLocation' => 'http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv33.xsd ',
            'date' => date('Y-m-d') . 'T' . date('H:i:s'),
            'version' => '3.3',
            'currency' => 'MXN',
            // Fields to fill by PAC
            'seal' => '@',
            'cert' => '@',
        ];

        if (isset($data['key_path'])) {
            $this->key_path = $data['key_path'];
            unset($data['key_path']);
        } else {
            $this->key_path = env('CFDI_KEY_PEM_PATH');
        }
        if (isset($data['cer_path'])) {
            $this->key_path = $data['cer_path'];
            unset($data['cer_path']);
        } else {
            $this->cer_path = env('CFDI_CER_PEM_PATH');
        }

        parent::__construct($data, $other_rules);
    }

    public function generate()
    {
        $this->calcule();
        $this->xml = new XML($this->key_path, $this->cer_path);
        $this->xml->generate($this);
    }

    public function getXML()
    {
        return $this->xml->getXML();
    }

    protected function getRules()
    {
        return [
            'pay_way' => 'nullable|in:' . implode(',', array_keys(sat_catalogs()->payWaysList())),
            'subtotal' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'total' => 'required|numeric',
            'type' => 'required|in:I,E,N,P',
            'pay_method' => 'nullable|in:' . implode(',', array_keys(config('cfdi.pay_methods'))),
            'zip_code' => 'nullable|required',
            'serie' => 'required',
            'folio' => 'required',
            'cert_number' => 'required',
            'currency' => 'required',
            'exchange_rate' => 'nullable',
        ];
    }

    public function calcule()
    {
        $this->addChild($this->transmitter);
        $this->addChild($this->receiver);

        $this->total = 0;
        $this->subtotal = 0;
        $discount = 0;
        if (count($this->concepts) > 0) {
            $concepts = new Concepts();
            //$this->total = 0;
            //$this->subtotal = 0;
            //$this->discount = 0;
            foreach ($this->concepts as $item) {
                $item->calcule();
                $this->subtotal += $item->import;
                $discount += $item->discount;
                $concepts->addChild($item);
            }
            $this->total = $this->subtotal - $discount;

            $this->addChild($concepts);
        }
        if ($discount > 0) {
            $this->discount = $discount;
        }
    }

    public function addConcept(Concept $concept)
    {
        $this->concepts[] = $concept;
    }

    public function setReceiver(Receiver $receiver)
    {
        $this->receiver = $receiver;
    }

    public function setTransmitter(Transmitter $transmitter)
    {
        $this->transmitter = $transmitter;
    }
}
