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

if ($_SERVER["REQUEST_METHOD"] != "POST" || !csrf_valido()) {
    header("Location: ventas.php");
    exit();
}

$idVenta = (int) ($_POST["id_venta"] ?? 0);
$accion = $_POST["accion"] ?? "";

if ($accion == "entregar") {

    $fecha = $_POST["fecha_entrega"] ?? "";
    $d = DateTime::createFromFormat("Y-m-d", $fecha);

    if (!$d || $d->format("Y-m-d") !== $fecha) {
        header("Location: ventas.php?entrega=fecha");
        exit();
    }

    // Solo se entregan ventas finalizadas
    $stmt = $conex->prepare(
        "UPDATE ventas
         SET entregado = 1, fecha_entrega = ?
         WHERE id_venta = ? AND estado = 1"
    );
    $stmt->bind_param("si", $fecha, $idVenta);
    $stmt->execute();

    header("Location: ventas.php?entrega=ok");
    exit();
}

if ($accion == "revertir") {

    $stmt = $conex->prepare(
        "UPDATE ventas
         SET entregado = 0, fecha_entrega = NULL
         WHERE id_venta = ?"
    );
    $stmt->bind_param("i", $idVenta);
    $stmt->execute();

    header("Location: ventas.php?entrega=revertida");
    exit();
}

header("Location: ventas.php");
exit();
