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

if (
    $_SERVER["REQUEST_METHOD"] != "POST" ||
    !csrf_valido() ||
    !isset($_POST["id"]) ||
    !isset($_SESSION["id_venta"])
) {
    header("Location: registrar_venta.php");
    exit();
}

$idDetalle = (int) $_POST["id"];
$idVenta = (int) $_SESSION["id_venta"];
$idUsuario = (int) $_SESSION["id_usuario"];

$conex->begin_transaction();

try {

    // Solo se puede quitar un producto de la venta propia que sigue abierta
    $stmt = $conex->prepare(
        "SELECT d.productos_id_producto, d.cantidad
         FROM detalle_venta d
         INNER JOIN ventas v ON d.ventas_id_venta = v.id_venta
         WHERE d.id_detalle = ?
         AND v.id_venta = ?
         AND v.usuarios_id_usuario = ?
         AND v.estado = 0
         FOR UPDATE"
    );
    $stmt->bind_param("iii", $idDetalle, $idVenta, $idUsuario);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_assoc();

    if ($detalle) {

        $stmt = $conex->prepare(
            "UPDATE productos
             SET stock_actual = stock_actual + ?
             WHERE id_producto = ?"
        );
        $stmt->bind_param("ii", $detalle["cantidad"], $detalle["productos_id_producto"]);
        $stmt->execute();

        $stmt = $conex->prepare("DELETE FROM detalle_venta WHERE id_detalle = ?");
        $stmt->bind_param("i", $idDetalle);
        $stmt->execute();
    }

    $conex->commit();
} catch (Throwable $e) {

    $conex->rollback();
}

header("Location: registrar_venta.php");
exit();
