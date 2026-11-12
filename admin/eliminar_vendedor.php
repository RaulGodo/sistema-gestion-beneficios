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
    header("Location: vendedores.php");
    exit();
}

$id = (int) $_POST["id"];

// Con ventas o rendiciones registradas no se borra: se da de baja (Inactivo)
$stmt = $conex->prepare(
    "SELECT
        (SELECT COUNT(*) FROM ventas WHERE usuarios_id_usuario = ?) +
        (SELECT COUNT(*) FROM rendiciones WHERE usuarios_id_usuario = ?) AS c"
);
$stmt->bind_param("ii", $id, $id);
$stmt->execute();

if ($stmt->get_result()->fetch_assoc()["c"] > 0) {

    $stmt = $conex->prepare(
        "UPDATE usuarios SET estado = 'Inactivo'
         WHERE id_usuario = ? AND rol = 'vendedor'"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: vendedores.php?desactivado=1");
    exit();
}

// Solo se pueden borrar cuentas de vendedores (nunca la del administrador)
$stmt = $conex->prepare("DELETE FROM beneficios_has_usuarios WHERE usuarios_id_usuario = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt = $conex->prepare("DELETE FROM usuarios WHERE id_usuario = ? AND rol = 'vendedor'");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: vendedores.php?eliminado=1");
exit();
