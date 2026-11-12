<?php
session_start();

if (
    !isset($_SESSION["dnivendedor"]) ||
    $_SESSION["rol"] != "vendedor"
) {
    header("Location: ../formlogin.php");
    exit();
}

require_once("../conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST" || !csrf_valido()) {
    header("Location: rendicion.php");
    exit();
}

$idUsuario = (int) $_SESSION["id_usuario"];

function volver($mensaje)
{
    header("Location: rendicion.php?error=" . urlencode($mensaje));
    exit();
}

if (!isset($_POST["monto"]) || !is_numeric($_POST["monto"])) {
    volver("El monto ingresado no es válido.");
}

$monto = round((float) $_POST["monto"], 2);

if ($monto <= 0) {
    volver("El monto debe ser mayor a cero.");
}

$conex->begin_transaction();

try {

    // Se bloquea al vendedor para que dos rendiciones simultáneas no superen lo cobrado
    $stmt = $conex->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ? FOR UPDATE");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $stmt->get_result();

    /* Total cobrado */
    $stmt = $conex->prepare(
        "SELECT COALESCE(SUM(p.monto), 0) AS total
         FROM pagos p
         INNER JOIN ventas v ON p.ventas_id_venta = v.id_venta
         WHERE v.usuarios_id_usuario = ?"
    );
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $totalCobrado = (float) $stmt->get_result()->fetch_assoc()["total"];

    /* Total rendido */
    $stmt = $conex->prepare(
        "SELECT COALESCE(SUM(monto_rendido), 0) AS total
         FROM rendiciones
         WHERE usuarios_id_usuario = ?"
    );
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $totalRendido = (float) $stmt->get_result()->fetch_assoc()["total"];

    $pendiente = round($totalCobrado - $totalRendido, 2);

    if ($monto > $pendiente) {
        $conex->rollback();
        volver("El monto supera el dinero pendiente de rendición.");
    }

    $stmt = $conex->prepare(
        "INSERT INTO rendiciones (fecha_rendicion, monto_rendido, usuarios_id_usuario)
         VALUES (CURDATE(), ?, ?)"
    );
    $stmt->bind_param("di", $monto, $idUsuario);
    $stmt->execute();

    $conex->commit();
} catch (Throwable $e) {

    $conex->rollback();
    volver("No se pudo registrar la rendición.");
}

header("Location: rendicion.php?ok=1");
exit();
