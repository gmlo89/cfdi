<?php

namespace App\Exceptions;

namespace Gmlo\CFDI\Exceptions;

class BillerException extends \Exception
{
    protected $_data;

    public function __construct($message = '', $code = 0, Exception $previous = null, $_data = null)
    {
        $this->_data = $_data;
        parent::__construct($message, $code, $previous);
    }

    public function getPathFiles()
    {
        if (!isset($this->_data['delete_path'])) {
            return false;
        }
        return $this->_data['delete_path'];
    }

    public function getErrors()
    {
        if (!isset($this->_data['errors'])) {
            return false;
        }

        $errors = $this->_data['errors'];

        return $errors->CodigoError . ' - ' . $errors->MensajeIncidencia;
    }
}
