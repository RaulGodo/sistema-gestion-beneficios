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
    header("Location: productos.php");
    exit();
}

$id = (int) $_POST["id"];

// Un producto que ya se vendió no puede borrarse (rompería el historial)
$stmt = $conex->prepare("SELECT COUNT(*) AS c FROM detalle_venta WHERE productos_id_producto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

if ($stmt->get_result()->fetch_assoc()["c"] > 0) {
    header("Location: productos.php?noeliminable=1");
    exit();
}

$stmt = $conex->prepare("DELETE FROM productos WHERE id_producto = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: productos.php?eliminado=1");
exit();
