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

        $this->data['cadena_original'] = $this->makeOriginalString($xml);

        $this->en_data = $this->translate($this->data);
        $this->es_data = $this->translate($this->data, 'es');
        $this->en_data->url_qr = $this->makeUrl();
        $this->es_data->url_qr = $this->makeUrl();
    }

    protected function makeOriginalString($xml)
    {
        $xsl = new \DOMDocument('1.0', 'UTF-8');
        $xsl->load(__DIR__ . '/resources/xslt/3.3/cadenaoriginal_3_3.xslt');
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl);
        return $proc->transformToXML($xml);
    }

    protected function makeUrl()
    {
        if ($this->complement == null or $this->complement->stamp == null) {
            return '';
        }
        $totals = explode('.', number_format($this->total, 6, '.', ''));
        $total = str_pad($totals[0], 10, '0', STR_PAD_LEFT) . '.' . $totals[1];

        $params = [
            're' => $this->transmitter->rfc,
            'rr' => $this->receiver->rfc,
            'tt' => $total,
            'Id' => $this->complement->stamp->uuid,
        ];
        return '?' . http_build_query($params);
    }

    protected function translate($data, $lang = 'en')
    {
        if (!is_array($data)) {
            return $data;
        }
        /*$aux = [];
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

        return ($is_collection) ? collect($aux) : (object)$aux;*/
        $aux = new CFDINode();
        foreach ($data as $key => $value) {
            if (is_integer($key)) {
                $aux->items->push($this->translate($value, $lang));
            //$is_collection = true;
                //$aux[$key] = $this->translate($value, $lang);
            } elseif ($lang == 'en') {
                $aux->{trans('CFDI::translation.' . $key)} = $this->translate($value, $lang);
            } elseif ($lang == 'es') {
                $aux->{$key} = $this->translate($value, $lang);
            }
        }
        return $aux;
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

        $namespaces = $xml->getNamespaces(true);
        foreach ($namespaces as $pre => $ns) {
            foreach ($xml->children($ns) as $k => $v) {
                $new_path = $path . "//{$pre}:{$k}";
                $this->iterator($v, $result, $new_path);
            }
        }

        $path_parts = explode('//', $path);

        if (ends_with($path, 'Deduccion')) {
            //    dd($path_parts);
        }

        if (
            count($path_parts) >= 1 and
            in_array($path_parts[count($path_parts) - 2], [last($path_parts) . 's', last($path_parts) . 'es'])
            ) {
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
        if (!is_null($this->en_data->{$name})) {
            return $this->en_data->{$name};
        }

        if (!is_null($this->es_data->{$name})) {
            return $this->es_data->{$name};
        }

        if (method_exists($this, 'get' . ucfirst(camel_case($name)))) {
            return $this->{'get' . ucfirst(camel_case($name))}();
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
            'iva' => isset($this->taxes->transfers) ? $this->taxes->transfers->where('tax', '002')->sum('amount') : 0,
            'iva_rate' => isset($this->taxes->transfers) ? $this->taxes->transfers->where('tax', '002')->first()->rate : 0,
            'sumary' => str_limit($this->sumary, 180)
        ];
    }

    public function getPaymentMethodStr()
    {
        return $this->payment_method . ' - ' . config('cfdi.pay_methods.' . $this->payment_method);
    }

    public function getPaymentWayStr()
    {
        return $this->payment_way . ' - ' . config('cfdi.pay_way._' . $this->payment_way);
    }

    public function getStampingDate()
    {
        return Carbon::parse($this->complement->stamp->stamping_at);
    }

    public function getCfdiTypeStr()
    {
        return config('cfdi.others.cfdi_types.' . $this->cfdi_type);
    }

    public function getFolioStr()
    {
        return str_pad($this->folio, 4, '0', STR_PAD_LEFT);
    }

    public function importFromJson($json)
    {
        $this->data = json_decode($json, true);
        $this->en_data = $this->translate($this->data);
        $this->es_data = $this->translate($this->data, 'es');
    }

    public function getUuid()
    {
        return $this->complement->stamp->uuid;
    }
}
