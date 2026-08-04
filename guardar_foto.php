<?php
/*
    Tomar una fotografía y guardarla en un archivo
    @date @date 2018-10-22
    @author parzibyte
    @web parzibyte.me/blog
*/

$imagenCodificada = file_get_contents("php://input"); //Obtener la imagen
if(strlen($imagenCodificada) <= 0) exit("No se recibió ninguna imagen");
//La imagen traerá al inicio data:image/png;base64, cosa que debemos remover
$imagenCodificadaLimpia = str_replace("data:image/png;base64,", "", urldecode($imagenCodificada));

//Venía en base64 pero sólo la codificamos así para que viajara por la red, ahora la decodificamos y
//todo el contenido lo guardamos en un archivo
$imagenDecodificada = base64_decode($imagenCodificadaLimpia);

$nombreImagenGuardada = "foto_" . uniqid() . ".png";

//$route_photo = "./admin/fotos/" . $nombreImagenGuardada;
$route_photo = "./assets/img/pae/" . $nombreImagenGuardada;

//Escribir el archivo
file_put_contents($route_photo, $imagenDecodificada);


//Terminar y regresar el nombre de la foto
exit($nombreImagenGuardada);