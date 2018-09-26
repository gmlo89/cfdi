# CFDI 3.3

## EN DESARROLLO
**IMPORTANTE**  este componente se encuentra en desarrollo y probablemente tenga cambios severos. 


>Nota: Para poder generar los CFDIs es necesario contar con el certificado de sello digital o CSD (los archivos .cer y .key), ademas de conocer la contraseña de la llave privada.

### Crear archivos .cer.pem y .key.pem
Con ayuda de la clase `Gmlo\CFDI\OpenSSL`  podemos generar los archivos .pem.
El metodo `generatePemFiles(..)` ademas valida que los certificados esten vigentes y concuerden con el RFC y contraseña proporcionada.
```php
$openssl = new OpenSSL('ruta/al_archivo/.cer', 'ruta/al_archivo/.key', 'RFC del emisor', 'Contraseña');
$openssl->generatePemFiles('directorio/donde_se_guardaran_los_archivos/.cer.pem_y_.key.pem');
echo $openssl->cer_pem_path; //Ruta del archivo .cer.pem
echo $openssl->key_pem_path; //Ruta del archivo .key.pem
echo $openssl->getCertificateSerialNumber(); //Número del sertificado.
echo $openssl->start_date; //Fecha de inicio del periodo de validez del certificado.
echo $openssl->end_date; //Fecha de fin del periodo de validez del certificado.
```


### ToDo
* CFDI de Nomina
* CFDI de Egresos
* CFDI de Ingresos


### Development
By [@gmlo_89]

 [@gmlo_89]: <https://twitter.com/gmlo_89>