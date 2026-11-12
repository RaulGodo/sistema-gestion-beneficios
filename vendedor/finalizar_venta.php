<?php
session_start();

if (
    !isset($_SESSION["dnivendedor"]) ||
    $_SESSION["rol"] != "vendedor"
) {
    header("Location: ../formlogin.php");
    exit();
}

include("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST" || !csrf_valido() || !isset($_SESSION["id_venta"])) {

    header("Location: registrar_venta.php");
    exit();
}

$idVenta = (int) $_SESSION["id_venta"];
$idUsuario = (int) $_SESSION["id_usuario"];

$stmt = $conex->prepare(
    "SELECT COALESCE(SUM(d.subtotal), 0) AS total
     FROM ventas v
     LEFT JOIN detalle_venta d ON d.ventas_id_venta = v.id_venta
     WHERE v.id_venta = ? AND v.usuarios_id_usuario = ? AND v.estado = 0"
);
$stmt->bind_param("ii", $idVenta, $idUsuario);
$stmt->execute();
$fila = $stmt->get_result()->fetch_assoc();

$total = (float) $fila["total"];

unset($_SESSION["id_venta"]);

// Venta sin productos: se descarta
if ($total <= 0) {

    $stmt = $conex->prepare(
        "DELETE FROM ventas
         WHERE id_venta = ? AND usuarios_id_usuario = ? AND estado = 0"
    );
    $stmt->bind_param("ii", $idVenta, $idUsuario);
    $stmt->execute();

    header("Location: registrar_venta.php?vacia=1");
    exit();
}

$stmt = $conex->prepare(
    "UPDATE ventas
     SET total = ?, estado = 1
     WHERE id_venta = ? AND usuarios_id_usuario = ? AND estado = 0"
);
$stmt->bind_param("dii", $total, $idVenta, $idUsuario);
$stmt->execute();

header("Location: registrar_venta.php?finalizada=1");
exit();
