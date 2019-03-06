<?php

namespace Gmlo\CFDI;

class CFDINode
{
    public $items;
    protected $data = [];

    public function __construct()
    {
        $this->items = collect([]);
    }

    public function __get($name)
    {
        if (method_exists($this, 'get' . ucfirst($name))) {
            return $this->{'get' . ucfirst($name)}();
        }
        if ($name == 'complement' or true) {
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    public function __set($name, $data)
    {
        $this->data[$name] = $data;
    }
}
