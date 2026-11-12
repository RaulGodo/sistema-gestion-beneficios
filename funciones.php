<?php
// Funciones comunes del sistema

// Escapa un valor para mostrarlo de forma segura en HTML (previene XSS)
function h($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

// Token CSRF para formularios POST que modifican datos
function csrf_token()
{
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valido()
{
    return isset($_POST["csrf"], $_SESSION["csrf"])
        && hash_equals($_SESSION["csrf"], $_POST["csrf"]);
}

// Registra un pago sobre una venta finalizada.
// Si se informa $idVendedor, la venta debe pertenecer a ese vendedor.
// Devuelve null si se registró correctamente, o un texto con el error.
function registrar_pago($conex, $idVenta, $monto, $idVendedor = null)
{
    if (!is_numeric($monto)) {
        return "El monto ingresado no es válido.";
    }

    $monto = round((float) $monto, 2);

    if ($monto <= 0) {
        return "El monto debe ser mayor que cero.";
    }

    $conex->begin_transaction();

    try {
        // Se bloquea la venta para que dos pagos simultáneos no superen la deuda
        $sql = "SELECT total FROM ventas WHERE id_venta = ? AND estado = 1";

        if ($idVendedor !== null) {
            $sql .= " AND usuarios_id_usuario = ?";
        }

        $sql .= " FOR UPDATE";

        $stmt = $conex->prepare($sql);

        if ($idVendedor !== null) {
            $stmt->bind_param("ii", $idVenta, $idVendedor);
        } else {
            $stmt->bind_param("i", $idVenta);
        }

        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();

        if (!$venta) {
            $conex->rollback();
            return "La venta no existe o todavía no fue finalizada.";
        }

        $stmt = $conex->prepare(
            "SELECT COALESCE(SUM(monto), 0) AS pagado FROM pagos WHERE ventas_id_venta = ?"
        );
        $stmt->bind_param("i", $idVenta);
        $stmt->execute();
        $pagado = (float) $stmt->get_result()->fetch_assoc()["pagado"];

        $deuda = round((float) $venta["total"] - $pagado, 2);

        if ($monto > $deuda) {
            $conex->rollback();
            return "El monto ingresado supera la deuda pendiente ($" .
                number_format($deuda, 2, ",", ".") . ").";
        }

        $stmt = $conex->prepare(
            "INSERT INTO pagos (fecha_pago, monto, ventas_id_venta) VALUES (CURDATE(), ?, ?)"
        );
        $stmt->bind_param("di", $monto, $idVenta);
        $stmt->execute();

        $conex->commit();

        return null;
    } catch (Throwable $e) {
        $conex->rollback();
        return "No se pudo registrar el pago.";
    }
}
