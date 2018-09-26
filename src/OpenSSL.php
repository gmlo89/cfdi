<?php

namespace Gmlo\CFDI;

use Gmlo\CFDI\Exceptions\BillerException;
use Carbon\Carbon;
use Symfony\Component\Process\Process;

class OpenSSL
{
    public $certificate_serial_number;
    public $start_date;
    public $end_date;
    public $cer_pem_path = null;
    public $key_pem_path = null;

    protected $cer_path = null;
    protected $key_path = null;
    protected $pem_directory = null;
    protected $pem_path = null;
    protected $password;
    protected $rfc;

    public function __construct($cer_path = null, $key_path = null, $rfc = null, $password = null)
    {
        $this->cer_path = $cer_path;
        $this->key_path = $key_path;
        $this->password = $password;
        $this->rfc = $rfc;
    }

    public function generatePemFiles($pem_directory = null)
    {
        $this->pem_path = $pem_directory . '/' . strtoupper($this->rfc) . '_' . date('YmdHms');
        $this->generatePEMCer();
        $this->generatePEMKey();

        $this->validateKeyCerFiles();
        $this->validateCertificateValidity();
        $this->verifyRFC();
    }

    protected function verifyRFC()
    {
        $result = $this->runCommand("openssl x509 -in {$this->pem_path}.cer.pem -noout -subject -nameopt RFC2253")->getOutput();

        if (!str_contains($result, $this->rfc)) {
            throw new BillerException('El certificado no pertence al RFC: ' . $this->rfc);
        }
        return true;
    }

    /**
     * Validate certificate validity
     * @return bool
     */
    protected function validateCertificateValidity()
    {
        $result = $this->runCommand("openssl x509 -noout -in {$this->pem_path}.cer.pem -dates")->getOutput();
        $result = array_filter(explode(PHP_EOL, $result));

        $this->start_date = Carbon::parse(str_replace('notBefore=', '', $result[0]));
        $this->end_date = Carbon::parse(str_replace('notAfter=', '', $result[1]));

        if (!Carbon::now()->between($this->start_date, $this->end_date)) {
            throw new BillerException('Error con el periodo de validez del certificado');
        }
        return true;
    }

    /**
     * Validate a .cer.pem and .key.pem
     * @return bool
     */
    protected function validateKeyCerFiles()
    {
        $result_cer = $this->runCommand("openssl x509 -noout -modulus -in {$this->cer_pem_path}")->getOutput();
        $result_key = $this->runCommand("openssl rsa -noout -modulus -in {$this->key_pem_path}")->getOutput();
        if ($result_cer == $result_key) {
            return true;
        }
        throw new BillerException('Los archivos .cer y .key no coinciden.');
    }

    /**
     * Get the certificate serial number
     * @param $path
     * @return bool|mixed
     */
    public function getCertificateSerialNumber()
    {
        $result = $this->runCommand("openssl x509 -inform DER -in {$this->cer_path} -noout -serial")->getOutput();
        $result = explode('=', $result);
        if (isset($result[1])) {
            $this->certificate_serial_number = str_replace(' ', '', $result[1]);
            $this->certificate_serial_number = implode('', array_filter(str_split($this->certificate_serial_number), function ($var, $key) {
                return is_numeric($var) and ($key != 0 and ($key % 2 != 0));
            }, ARRAY_FILTER_USE_BOTH));
            if (strlen($this->certificate_serial_number) == 20) {
                return $this->certificate_serial_number;
            }
        }
        throw new BillerException('Ocurrio un error al intentar obtener el número de serie del certificado.');
    }

    /**
     * Generate a file .key.pem
     * @param $path
     * @param $password
     * @return bool
     */
    public function generatePEMKey()
    {
        if ($this->runCommand("openssl pkcs8 -inform DER -in {$this->key_path} -passin pass:{$this->password} -out {$this->pem_path}.key.pem")->isSuccessful()) {
            $this->key_pem_path = $this->pem_path . '.key.pem';
            return true;
        }
        throw new BillerException('Ocurrio un error al generar la llave en formato PEM. Por favor verifica la contraseña.');
    }

    /**
     * Generate a file .cer.pem
     * @param $path
     * @return bool
     */
    public function generatePEMCer()
    {
        if ($this->runCommand("openssl x509 -inform DER -outform PEM -in {$this->cer_path} -pubkey -out {$this->pem_path}.cer.pem")->isSuccessful()) {
            $this->cer_pem_path = $this->pem_path . '.cer.pem';
            return true;
        }
        throw new BillerException("Ocurrio un error al intentar generar el archivo {$this->pem_path}.cer.pem");
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
