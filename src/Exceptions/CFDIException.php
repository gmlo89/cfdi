<?php

namespace Gmlo\CFDI\Exceptions;
use Illuminate\Support\MessageBag;

class CFDIException extends \Exception {

    protected $_data;

    public function __construct($message="", $code=0 , Exception $previous=NULL, $_data = NULL)
    {
        $this->_data = $_data;
        parent::__construct($message, $code, $previous);
    }

    public function getPathFiles()
    {
        if( !isset($this->_data['delete_path']) )
            return false;
        return $this->_data['delete_path'];
    }

    public function getErrors()
    {
        //dd(get_class($this->_data['errors']));

        if( is_array( $this->_data['errors'] ) )
        {
            $message_str = '';
            foreach ($this->_data['errors'] as $message) {
                $message_str .= "{$message}<br>";
            }
            return $message_str;
        }

        if( !isset($this->_data['errors']) )
            return false;

        $errors = $this->_data['errors'];

        return $errors->CodigoError . ' - ' . $errors->MensajeIncidencia;
    }
}
