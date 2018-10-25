<?php

namespace Gmlo\CFDI;

use Carbon\Carbon;

class CFDIReader
{
    protected $data;
    protected $type;
    protected $en_data;
    protected $es_data;

    /**
     * CFDI constructor.
     * @param null $xml_path
     */
    public function __construct($xml = null)
    {
        if ($xml) {
            $this->type = 'cfdi';
            $this->render($xml);
        }
    }

    /**
     * Convert CFDI to json
     * @return string
     */
    public function toJson($lang = '')
    {
        return json_encode($this->{$lang . 'data'});
    }

    /**
     * Render CFDIs
     *
     * @param String $xml
     * @return void
     */
    public function render($xml)
    {
        if (is_string($xml)) {
            $xml = simplexml_load_file($xml, 'SimpleXMLElement', 0, $this->type, true);
        } else {
            $xml = simplexml_load_string($xml->saveXML(), 'SimpleXMLElement', 0, $this->type, true);
        }

        $result = [];
        $this->iterator($xml, $result, "//{$this->type}:Comprobante");

        $this->data = collect($result)->first();
        $this->en_data = $this->translate($this->data);
        $this->es_data = $this->translate($this->data, 'es');
    }

    protected function translate($data, $lang = 'en')
    {
        if (!is_array($data)) {
            return $data;
        }
        $aux = [];
        $is_collection = false;
        foreach ($data as $key => $value) {
            if (is_integer($key)) {
                $is_collection = true;
                $aux[$key] = $this->translate($value, $lang);
            } elseif ($lang == 'en') {
                $aux[trans('CFDI::translation.' . $key)] = $this->translate($value, $lang);
            } elseif ($lang == 'es') {
                $aux[$key] = $this->translate($value, $lang);
            }
        }

        return ($is_collection) ? collect($aux) : (object)$aux;
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
        $name = $xml->getName();

        foreach ($xml->attributes() as $key => $value) {
            $result[$key] = (string)$xml->attributes()->{$key};
        }

        foreach ($xml as $key => $child) {
            $new_path = $path . "//{$this->type}:{$key}";
            $this->iterator($child, $result, $new_path);
        }

        if ($name == 'Complemento') {
            $path = $path . '//t:TimbreFiscalDigital';
            $ns = $xml->getNamespaces(true);

            $xml->registerXPathNamespace('c', $ns['cfdi']);
            $xml->registerXPathNamespace('t', $ns['tfd']);

            foreach ($xml->xpath($path) as $children) {
                $this->iterator($children, $result, $path);
            }
        }

        $path_parts = explode('//', $path);

        if (count($path_parts) >= 1 and $path_parts[count($path_parts) - 2] == last($path_parts) . 's') {
            $parent[] = $result;
        } else {
            $parent[$name] = $result;
        }
    }

    public function getSumary()
    {
        $sumary = '';
        foreach ($this->concepts as $concept) {
            $sumary .= str_limit($concept->description, 15, '..');
        }
        return $sumary;
    }

    public function __get($name)
    {
        if (isset($this->en_data->{$name})) {
            return $this->en_data->{$name};
        }

        if (isset($this->es_data->{$name})) {
            return $this->es_data->{$name};
        }

        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        }

        return null;
    }

    public function getGeneralData()
    {
        return [
            'serie' => $this->serie,
            'folio' => $this->folio,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_way' => $this->payment_way,
            'payment_account_number' => $this->payment_account_number,
            'uuid' => $this->complement->stamp->uuid,
            'stamping_at' => Carbon::parse($this->complement->stamp->stamping_at),
            'data' => $this->toJson(),
            'iva' => $this->taxes->transfers->where('tax', '002')->sum('amount'),
            'iva_rate' => $this->taxes->transfers->where('tax', '002')->first()->rate,
            'sumary' => str_limit($this->sumary, 180)
        ];
    }

    public function importFromJson($json)
    {
        $this->data = json_decode($json, true);
        $this->en_data = $this->translate($this->data);
        $this->es_data = $this->translate($this->data, 'es');
    }
}
