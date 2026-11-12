<?php
session_start();

if (
    !isset($_SESSION["dniadmin"]) ||
    $_SESSION["rol"] != "administrador"
) {
    header("Location: ../formlogin.php");
    exit();
}

include("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST" || !csrf_valido() || !isset($_POST["id"])) {
    header("Location: beneficios.php");
    exit();
}

$id = (int) $_POST["id"];

// Cerrar el beneficio (no se borra: conserva ventas e historial)
$stmt = $conex->prepare("UPDATE beneficios SET estado = 0 WHERE id_beneficio = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: beneficios.php?cerrado=1");
exit();
