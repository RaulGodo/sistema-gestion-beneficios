<?php
// Configuración de los parametros de la base de datos
$host = "localhost"; // Dirección del servidor de tu base de datos
$usuario = "root"; //  Usuario Administrador de MySQL
$pass = "1q2w3e"; // Contraseña de MySQL
$bd = "bdbeneficios"; // Nombre de la base de datos MySQL

// Crear una conexión
$conex = new mysqli($host, $usuario, $pass, $bd);

// Verificar si la conexión fue exitosa
if ($conex->connect_error) {
    die("Error de conexión: " . $conex->connect_error);
}

require_once __DIR__ . '/funciones.php';
