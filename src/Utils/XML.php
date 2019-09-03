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

        //$this->validate();

        if (config('app.env') == 'local') {
            $this->xml->save(storage_path('app/cfdi_tmp.xml'));
        }
    }

    /**
     * Probando..
     *
     */
    public function validate()
    {
        $sxe = new \SimpleXMLElement($this->xml->saveXML());
        $schemaLocations = (string)$sxe->attributes('xsi', true)->schemaLocation;

        $schemaLocations = explode(' ', $schemaLocations);
        $xsds = [];

        foreach ($schemaLocations as $schemaLocation) {
            if (ends_with($schemaLocation, '.xsd')) {
                $xsds[] = basename($schemaLocation);
            }
        }
        $xsds = [
            __DIR__ . '/../resources/xsd/3.3/cfdv33.xsd',
            __DIR__ . '/../resources/xsd/3.3/Pagos10.xsd'
        ];

        $xml = new \DOMDocument('1.0', 'utf-8');
        $xml->loadXML($this->xml->saveXML(), LIBXML_NOBLANKS);
        libxml_use_internal_errors(true);
        $errors = [];

        foreach ($xsds as $xsd) {
            //$xsd_path = __DIR__ . '/../resources/xsd/3.3/' . $xsd;

            if (!$xml->schemaValidate($xsd)) {
                foreach (libxml_get_errors() as $error) {
                    $errors[] = $error->message;
                }
                libxml_clear_errors();
            }
        }
        \Log::error($errors);
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
                //$value = utf8_encode($value);
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
}
