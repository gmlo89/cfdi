<?php

namespace Gmlo\CFDI;


use App\Exceptions\BillerException;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class OpenSSL {


    protected $path;
    protected $password;
    protected $rfc;
    public $certificate_serial_number;
    public $start_date;
    public $end_date;

    public function __construct($path, $password = null, $rfc = null)
    {
        $this->path = $path;
        $this->password = $password;
        $this->rfc = $rfc;
    }


    public function generateInitialFiles()
    {
        $this->generatePEMCer();
        $this->generatePEMKey();
        $this->getCertificateSerialNumber();
        $this->validateKeyCerFiles();
        $this->validateCertificateValidity();
        $this->verifyRFC();
    }

    public function verifyRFC()
    {
        $result = $this->runCommand("openssl x509 -in {$this->path}.cer.pem -noout -subject -nameopt RFC2253")->getOutput();

        if( !str_contains($result, $this->rfc) )
            throw new BillerException('El certificado no pertence al RFC: ' . $this->rfc, 0, null, ['delete_path' => $this->path]);
        return true;
    }


    /**
     * Validate certificate validity
     * @return bool
     */
    public function validateCertificateValidity()
    {
        $result = $this->runCommand("openssl x509 -noout -in {$this->path}.cer.pem -dates")->getOutput();
        $result = array_filter(explode(PHP_EOL, $result));

        $this->start_date  = Carbon::parse( str_replace('notBefore=', '', $result[0]) );
        $this->end_date    = Carbon::parse( str_replace('notAfter=', '', $result[1]) );

        if( !Carbon::now()->between($this->start_date, $this->end_date) )
            throw new BillerException('Error con el periodo de validez del certificado', 0, null, ['delete_path' => $this->path]);
        return true;
    }

    /**
     * Validate a .cer.pem and .key.pem
     * @return bool
     */
    public function validateKeyCerFiles()
    {
        $result_cer = $this->runCommand("openssl x509 -noout -modulus -in {$this->path}.cer.pem")->getOutput();
        $result_key = $this->runCommand("openssl rsa -noout -modulus -in {$this->path}.key.pem")->getOutput();
        if( $result_cer == $result_key )
            return true;
        throw new BillerException('Los archivos .cer y .key no coinciden.', 0, null, ['delete_path' => $this->path]);
    }

    /**
     * Get the certificate serial number
     * @param $path
     * @return bool|mixed
     */
    public function getCertificateSerialNumber()
    {
        $result = $this->runCommand("openssl x509 -inform DER -in {$this->path}.cer -noout -serial")->getOutput();
        $result = explode('=', $result);
        if( isset($result[1]) ) {
            $this->certificate_serial_number = str_replace(' ', '', $result[1]);
            $this->certificate_serial_number = implode('', array_filter(str_split($this->certificate_serial_number), function($var, $key){
                return is_numeric($var) and ( $key != 0 and ($key%2!=0) );
            }, ARRAY_FILTER_USE_BOTH));
            if( strlen($this->certificate_serial_number) == 20 )
                return true;
        }
        throw new BillerException('Ocurrio un error al intentar obtener el número de serie del certificado.', 0, null, ['delete_path'=>$this->path]);
    }

    /**
     * Generate a file .key.pem
     * @param $path
     * @param $password
     * @return bool
     */
    public function generatePEMKey()
    {
        if( $this->runCommand("openssl pkcs8 -inform DER -in {$this->path}.key -passin pass:{$this->password} -out {$this->path}.key.pem")->isSuccessful() )
            return true;
        throw new BillerException('Ocurrio un error al generar la llave en formato PEM. Por favor verifica la contraseña.', 0, null, ['delete_path' => $this->path]);
    }

    /**
     * Generate a file .cer.pem
     * @param $path
     * @return bool
     */
    public function generatePEMCer()
    {
        $path = $this->path;
        if( $this->runCommand("openssl x509 -inform DER -outform PEM -in {$this->path}.cer -pubkey -out {$this->path}.cer.pem")->isSuccessful() )
            return true;
        throw new BillerException('Ocurrio un error al intentar generar el archivo .cer.pem.', 0, null, ['delete_path' => $this->path]);
    }

    /**
     * Run a command
     * @param $command
     * @return Process
     */
    protected function runCommand($command)
    {
        $process = new Process($command);
        $process->run();
        return $process;
    }
}
