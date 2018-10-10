# CFDI 3.3 / Laravel

## EN DESARROLLO
**IMPORTANTE**  este componente se encuentra en desarrollo y probablemente tenga cambios severos. 

Si utilizas los servicios de Finkok para timbrado, talvez te puede servir [https://github.com/gmlo89/Finkok]

>Nota: Para poder generar los CFDIs es necesario contar con el certificado de sello digital o CSD (los archivos .cer y .key), ademas de conocer la contraseña de la llave privada.

### Crear archivos .cer.pem y .key.pem
Con ayuda de la clase `Gmlo\CFDI\OpenSSL`  podemos generar los archivos .pem.
El metodo `generatePemFiles(..)` ademas valida que los certificados esten vigentes y concuerden con el RFC y contraseña proporcionada.
```php
$openssl = new OpenSSL('ruta/al_archivo/.cer', 'ruta/al_archivo/.key', 'RFC del emisor', 'Contraseña');
$openssl->generatePemFiles('directorio/donde_se_guardaran_los_archivos/.cer.pem_y_.key.pem');
//Ruta del archivo .cer.pem
echo $openssl->cer_pem_path; 
//Ruta del archivo .key.pem
echo $openssl->key_pem_path; 
//Número del certificado.
echo $openssl->getCertificateSerialNumber(); 
//Fecha de inicio del periodo de validez del certificado.
echo $openssl->start_date; 
//Fecha de fin del periodo de validez del certificado.
echo $openssl->end_date; 
```
### Generar recibo de nomina
A continuación un ejemplo de como generar un recibo de nomina.
```php
$nomina = new CFDINomina([
    'pay_way' => '99',
    'pay_method' => 'PUE',
    'zip_code' => '23000',
    'serie' => 'A',
    'folio' => '01',
    'cert_number' => '20001000000300022762',
    'pay_start_date' => '2018-09-01',
    'pay_end_date' => '2018-09-15',
    'pay_date' => '2018-09-15',
    'type' => 'O',
    'pay_days' => 15,
]);
/*
    Asignar rutas de los archivos en el archivo .env:
    CFDI_KEY_PEM_PATH=/var/www/.../storage/certs/sasasasa.key.pem
    CFDI_CER_PEM_PATH=/var/www/.../storage/certs/sasasasa.cer.pem
    o asignarlos con los metodos setKeyPath y setCerPath:
*/
$nomina->setKeyPath(storage_path('certs/20181003171044.key.pem'));
$nomina->setCerPath(storage_path('certs/20181003171044.cer.pem'));

$nomina->setEmployer([
    'rfc' => 'ERFC201902919201',
    'name' => 'EL EMPLEADOR',
    'tax_regime' => '603',
]);

$nomina->setEmployee([
    'rfc' => 'RFCC921929192',
    'name' => 'EL EMPLEADO',
    'curp' => 'ELE28192819821',
    'contract_type' => '09',
    'regime_type' => '09',
    'employee_number' => '1',
    'periodicity_of_payment' => '05',
    'employee_position' => 'JEFE DE ALGUN DEPARTAMENTO',
    'state' => 'BCS',
]);

$nomina->addPerception('001', '001', 'Sueldos y Salarios', 7824.00, 0);
$nomina->addDeduction('002', '002', 'ISR', 1033.03);

$nomina->generate();
// Ya puedes obtener el XML generado
echo $nomina->getXML();
```

### Leer XML del CFDI
Podemos recuperar la información del XML con la ayuda de nuestra clase `\Gmlo\CFDI\CFDIReader`, con el que podras acceder a los nodos y atributos. ya sea con los nombres originales o con su traducción a ingles. En caso de los conjuntos de nodos del mismo tipo como por ejemplo los `CFDI:Conceptos` seran agrupados en una `Collection`.
Ejemplo:
```php
$cfdi = new CFDIReader('/path/to/CFDI.xml');
// Obtener información de un atributo (Con los nombres originales)
echo $cfdi->Emisor->Rfc;
// Ahora en ingles, minúsculas y en snake_case:
echo $cfdi->transmitter->rfc;
// Otras funciones:
echo $cfdi->toJson();
```

### ToDo
* Cancelaciones
* Complementos de pago
* Otros complementos
* CFDI de Egresos
* CFDI de Ingresos


### Development By [@gmlo_89]

 [@gmlo_89]: <https://twitter.com/gmlo_89>
 [https://github.com/gmlo89/Finkok]: <https://github.com/gmlo89/Finkok>