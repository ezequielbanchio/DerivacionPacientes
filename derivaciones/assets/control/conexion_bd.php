<?php
// conexion_bd.php

// importa credenciales
include("variables.php");

// Arreglo con info de conexión
$connectionInfo = array(
    "Database" => $dbName,
    "UID" => $userName,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
);

// Conexión
$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn) {
    // echo "Conexión exitosa a la BD: $dbName";
} else {
    die(print_r(sqlsrv_errors(), true));
}
?>
