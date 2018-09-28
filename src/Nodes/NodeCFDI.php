<?php

namespace Gmlo\CFDI\Nodes;

use Illuminate\Support\Facades\Validator;
use Gmlo\CFDI\Exceptions\CFDIException;

class NodeCFDI
{
    protected $data = [];
    protected $rules = [];
    protected $dictionary = [];
    protected $childs = [];

    public function getNodeName()
    {
        return '';
    }

    public function __construct($data = [], $other_rules = [])
    {
        $this->rules = array_merge($this->getRules(), $other_rules);

        foreach ($data as $key => $value) {
            if (in_array($key, array_keys($this->rules))) {
                $this->data[$key] = $value;
            }
        }
    }

    public function addChild(NodeCFDI $child)
    {
        $this->childs[] = $child;
    }

    public function getChilds()
    {
        return $this->childs;
    }

    protected function getRules()
    {
        return [];
    }

    public function getData()
    {
        return $this->data;
    }

    public function toXMLArray()
    {
        $this->validate();
        $data = [];
        foreach ($this->data as $key => $value) {
            if (isset($this->dictionary[$key])) {
                $data[$this->dictionary[$key]] = $value;
            }
        }
        return $data;
    }

    protected function validate()
    {
        //$this->calcule();
        $validator = Validator::make($this->data, $this->getRules(), trans('CFDI::validation_messages'));

        if ($validator->fails()) {
            $message = 'Tienes un error en ' . $this->node_name . '. ';
            //dd($this->getData());
            if (config('app.env') == 'local' and $validator->errors()) {
                $message .= json_encode($validator->errors());
            }
            throw new CFDIException($message, 0, null, ['errors' => $validator->errors()->all()]);
        }
        return true;
    }

    public function __get($name)
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function __set($name, $data)
    {
        if (in_array($name, array_keys($this->getRules()))) {
            $this->data[$name] = $data;
        } else {
            throw new CFDIException("El atributo {$name} no existe");
        }
    }

    public function calcule()
    {
    }
}
