<?php
namespace Gmlo\CFDI;

use Carbon\Carbon;
use Gmlo\CFDI\Utils\XML;

class CFDIReader {

    protected $data;

    protected $type;

    /**
     * CFDI constructor.
     * @param null $xml_path
     */
    public function __construct($xml = null)
    {
        if( $xml )
        {
            $this->type = 'cfdi';
            $this->render($xml);
        }
    }

    /**
     * Convert CFDI to json
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->data);
    }

    /**
     * Convert CFDI to array
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * Render CFDI
     * @return array
     */
    public function render($xml)
    {

        if( is_string($xml) )
            $xml    = simplexml_load_file( public_path($xml), 'SimpleXMLElement', 0, $this->type, true);
        else
            $xml    = simplexml_load_string( $xml->saveXML(), 'SimpleXMLElement', 0, $this->type, true );

        $result = [];
        $this->iterator($xml, $result, "//{$this->type}:Comprobante");
        $this->data = collect($result)->first();
    }

    /**
     * Interacts between nodes to convert to array
     * @param $xml
     * @param $parent
     * @param string $path
     */

    protected function iterator($xml, &$parent, $path = '')
    {
        $result = [];
        $name   = $xml->getName();

        foreach ( $xml->attributes() as $key => $value )
        {
            $result[$key] = (string)$xml->attributes()->{$key};
        }

        foreach ( $xml as $key => $child) {
            $new_path   = $path . "//{$this->type}:{$key}";
            $this->iterator($child, $result, $new_path);
        }

        if( $name == 'Complemento' )
        {
            $path   = $path . "//t:TimbreFiscalDigital";
            $ns     = $xml->getNamespaces(true);

            $xml->registerXPathNamespace('c', $ns['cfdi']);
            $xml->registerXPathNamespace('t', $ns['tfd']);

            foreach ($xml->xpath($path) as $children)
            {
                $this->iterator($children, $result, $path);
            }
        }

        if( !isset($parent[$name]) )
            $parent[$name] = $result;
        else if( !is_array($parent[$name]) or !(array_keys($parent[$name]) === range(0, count($parent[$name]) - 1)) )
        {
            $aux = $parent[$name];
            $parent[$name] = [];
            $parent[$name][] = $aux;
            $parent[$name][] = $result;
        }
        else
        {
            $parent[$name][] = $result;
        }
    }

    public function getTransmitter()
    {
        $transmitter = [];
        if( $this->data )
        {
            $data = $this->data['Emisor'];
            if( isset($this->data['Emisor']['DomicilioFiscal']) )
                $data = array_merge($data, $this->data['Emisor']['DomicilioFiscal']);
            foreach ($data as $attr_name => $attr_value) {
                if( !is_array($attr_value) )
                    $transmitter[ trans('CFDI::translation.' . $attr_name) ] = $attr_value;
            }
        }
        return (object)$transmitter;
    }

    public function getReceiver()
    {
        $transmitter = [];
        if( $this->data )
        {
            $data = $this->data['Receptor'];
            if( isset($this->data['Receptor']['DomicilioFiscal']) )
                $data = array_merge($data, $this->data['Receptor']['DomicilioFiscal']);
            foreach ($data as $attr_name => $attr_value) {
                if( !is_array($attr_value) )
                    $transmitter[ trans('CFDI::translation.' . $attr_name) ] = $attr_value;
            }
        }
        return (object)$transmitter;
    }

    public function getSeal()
    {
        $seal = [];
        if( $this->data and isset($this->data['Complemento']['TimbreFiscalDigital']) )
        {
            foreach ($this->data['Complemento']['TimbreFiscalDigital'] as $attr_name => $attr_value) {
                $seal[ trans('CFDI::translation.' . $attr_name) ] = $attr_value;
            }
        }
        return (object) $seal;
    }

    public function getIva()
    {
        if( $this->data )
        {
            foreach ( $this->taxes->where('tax', '002') as $item )
            {
                if( $item['amount'] > 0 )
                    return (object) $item;
            }
            return (object)$this->taxes->where('tax', '002')->first();
        }
        return null;
    }

    public function getTaxes()
    {
        $taxes = [];

        if( $this->data and isset($this->data['Impuestos']['Traslados']) )
        {
            $data = $this->data['Impuestos']['Traslados'];
            if( isset($data['Traslado']) and isset($data['Traslado'][0]) )
            {
                $data = $data['Traslado'];
            }
            foreach ( $data as $item )
            {
                $aux = [];

                foreach ( $item as $attr_key => $attr_value )
                {
                    $aux[ trans('CFDI::translation.' . $attr_key) ] = $attr_value;
                }

                $taxes[] = $aux;
            }
        }
        return collect($taxes);
    }

    public function getConcepts()
    {
        $concepts = [];
        if( $this->data and isset($this->data['Conceptos']) )
        {
            $data = $this->data['Conceptos'];

            if( isset($data['Concepto']) and isset($data['Concepto'][0]) )
                $data = $data['Concepto'];

            foreach ( $data as $concept )
            {
                $aux = [];
                foreach ( $concept as $attr_key => $attr_value )
                {
                    $aux[ trans('CFDI::translation.' . $attr_key) ] = $attr_value;
                }
                $concepts[] = (object)$aux;
            }
        }
        return $concepts;
    }

    public function getSumary()
    {
        $sumary = '';
        foreach ( $this->concepts as $concept )
        {
            $sumary .= str_limit($concept->description, 15, '..');
        }
        return $sumary;
    }

    public function __get($name)
    {
        if( method_exists($this, 'get' . ucfirst($name) ) )
            return $this->{'get' . ucfirst($name)}();

        $diccionary = collect(trans('CFDI::translation'));

        if( $diccionary->search($name) and  isset($this->data[$diccionary->search($name)]) )
            return $this->data[$diccionary->search($name)];


        return '';
    }

    public function getGeneralData()
    {
        return [
            'serie'             => $this->serie,
            'folio'             => $this->folio,
            'subtotal'          => $this->subtotal,
            'total'             => $this->total,
            'currency'          => $this->currency,
            'payment_method'    => $this->payment_method,
            'payment_way'       => $this->payment_way,
            'payment_account_number' => $this->payment_account_number,
            'uuid'              => $this->seal->uuid,
            'stamping_at'       => Carbon::parse($this->seal->stamping_at),
            'data'              => $this->toJson(),
            'iva'               => $this->iva->amount,
            'iva_rate'               => $this->iva->rate > 0 ? ($this->iva->rate / 100) : null,
            'sumary'            => $this->sumary
        ];
    }

    public function importFromJson($json)
    {
        $this->data = json_decode($json,true);
    }

    public function getOriginalStringSAT()
    {
        $fileds = ['version', 'uuid', 'stamping_at', 'rfc_certified_provider', 'legend', 'seal', 'sat_certificate_number'];
        $data = [];
        foreach ($fileds as $filed) {
            if( isset($this->seal->{$filed}) )
            {
                $data[] = $this->seal->{$filed};
            }
        }
        return '||' . implode('|', $data) . '||';
    }

    public function getQrCodeStr()
    {
        $data = '?&re=' . $this->transmitter->rfc;

        if( isset($this->receiver->fiscal_residence) )
            $data .= '&nr=' . $this->receiver->foreign_id;
        else
            $data .= '&rr=' . $this->receiver->rfc;
        $data .= '&tt=' . str_pad(number_format($this->total, 6, '.', ''), 17, '0', STR_PAD_LEFT);
        $data .= '&id=' . $this->seal->uuid;
        return $data;
    }
}
