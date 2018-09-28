<?php

namespace Gmlo\CFDI\Utils;

class XML
{
    protected $xml;
    protected $key_path;
    protected $cer_path;
    protected $data;

    public function __construct($key_path = null, $cer_path = null)
    {
        $this->key_path = $key_path;
        $this->cer_path = $cer_path;
    }

    // Load a xmlo object
    public function loadXML($xml)
    {
        $this->xml->loadXML($xml);
        if (config('app.env') == 'local') {
            $this->xml->save(storage_path('app/cfdi_tmp.xml'));
        }
    }

    // Return xml
    public function getXML()
    {
        return $this->xml;
    }

    /**
    * Generate a XML with data sent
    */
    public function generate($cfdi)
    {
        $this->xml = new \DOMDocument('1.0', 'UTF-8');

        $root = $this->createNode($this->xml, $cfdi);

        $this->preStamp($root, $cfdi);

        if (config('app.env') == 'local') {
            $this->xml->save(storage_path('app/cfdi_tmp.xml'));
        }
    }

    public function preStamp($root, $cfdi)
    {
        $original_string = $this->makeOriginalString();
        $certificado = $cfdi->cert_number;

        $pkeyid = openssl_get_privatekey(file_get_contents($this->key_path));
        openssl_sign($original_string, $crypttext, $pkeyid, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyid);

        $seal = base64_encode($crypttext);
        //$this->xml->setAttribute("sello", $sello);
        $this->addAttributes($root, ['Sello' => $seal]);

        $datos = file($this->cer_path);
        $cert = '';
        $carga = false;
        for ($i = 0; $i < sizeof($datos); $i++) {
            if (strstr($datos[$i], 'END CERTIFICATE')) {
                $carga = false;
            }
            if ($carga) {
                $cert .= trim($datos[$i]);
            }
            if (strstr($datos[$i], 'BEGIN CERTIFICATE')) {
                $carga = true;
            }
        }
        //$this->xml->setAttribute("certificado", $certificado);
        $this->addAttributes($root, ['Certificado' => $cert]);
    }

    protected function makeOriginalString()
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->loadXML($this->xml->saveXML());

        $xsl = new \DOMDocument('1.0', 'UTF-8');
        $xsl->load(__DIR__ . '/../resources/xslt/3.3/cadenaoriginal_3_3.xslt');
        $proc = new \XSLTProcessor;
        $proc->importStyleSheet($xsl);
        return $proc->transformToXML($xml);
    }

    protected function addAttributes(&$node, $attributes = [])
    {
        if (is_object($attributes) and method_exists($attributes, 'toXMLArray')) {
            $attributes = $attributes->toXMLArray();
        }

        foreach ($attributes as $key => $value) {
            //$value = htmlspecialchars($value);
            //$value = htmlspecialchars($value, ENT_QUOTES|ENT_XML1);
            $value = preg_replace('/\s\s+/', ' ', $value);
            $value = trim($value);
            if (strlen($value) > 0) {
                $value = str_replace('|', '/', $value);
                //$value = str_replace("'", "\&apos;", $value);
                $value = utf8_encode($value);
                $node->setAttribute($key, $value);
            }
        }
    }

    protected function createNode(&$parent, $node)
    {
        $element = $this->xml->createElement($node->node_name);
        $element = $parent->appendChild($element);
        $this->addAttributes($element, $node);
        foreach ($node->getChilds() as $child) {
            $this->createNode($element, $child);
        }
        return $element;
    }

    public function generate2($data)
    {
        $this->data = $data;
        $this->xml = new \DOMDocument('1.0', 'UTF-8');
        $root = $this->xml->createElement('cfdi:Comprobante');
        $root = $this->xml->appendChild($root);
        $this->addAttributes($root, $data->general);

        $this->createNode($root, $data->transmitter);

        $this->createNode($root, $data->receiver);

        if ($data->concepts) {
            $this->createNode($root, $data->concepts);

            //$concepts = $this->xml->createElement('cfdi:Conceptos');
            //$concepts = $root->appendChild($concepts);
            //$this->addAttributes($concepts);

            /*foreach ($data->concepts as $concept) {
                $concept_ = $this->xml->createElement('cfdi:Concepto');
                $concept_ = $concepts->appendChild($concept_);

                //$taxes = $concept['taxes'];
                //unset($concept['taxes']);

                $this->addAttributes($concept_, $concept);

                /*
                $taxes_ = $this->xml->createElement('cfdi:Impuestos');
                $taxes_ = $concept_->appendChild($taxes_);

                $taxes_t = $this->xml->createElement('cfdi:Traslados');
                $taxes_t = $taxes_->appendChild($taxes_t);*/
                /*
                foreach ($taxes['transfers'] as $tax) {
                    $tax_ = $this->xml->createElement('cfdi:Traslado');
                    $tax_ = $taxes_t->appendChild($tax_);
                    $this->addAttributes($tax_, $tax);
                }*/
            //}
        }

        if ($data->complements) {
            $complements = $this->createNode($root, $data->complements);
        }

        /*if (count($data->tax_transferred) > 0) {
            $taxes = $this->xml->createElement('cfdi:Impuestos');
            $taxes = $root->appendChild($taxes);
            //$this->addAttributes($taxes);

            $taxes_t = $this->xml->createElement('cfdi:Traslados');
            $taxes_t = $taxes->appendChild($taxes_t);
            //$this->addAttributes($taxes_t);

            $tax_transferreds = 0;
            foreach ($data->tax_transferred as $tax) {
                $aux = $this->xml->createElement('cfdi:Traslado');
                $aux = $taxes_t->appendChild($aux);
                $this->addAttributes($aux, $tax);
                $tax_transferreds += $tax['Importe'];
            }
            if ($tax_transferreds > 0) {
                $this->addAttributes($taxes, ['TotalImpuestosTrasladados' => $tax_transferreds]);
            }
        }*/

        $original_string = $this->makeOriginalString();

        $this->preStamp($original_string, $root);

        if (config('app.env') == 'local') {
            $this->xml->save(storage_path('app/cfdi_tmp.xml'));
        }
    }
}
