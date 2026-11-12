<?php
session_start();

if (
    !isset($_SESSION["dniadmin"]) ||
    $_SESSION["rol"] != "administrador"
) {
    header("Location: ../formlogin.php");
    exit();
}

require_once("../conexion.php");

if (!isset($_GET["id"])) {
    header("Location: ventas.php");
    exit();
}

$idVenta = (int) $_GET["id"];

if (isset($_POST["registrar"])) {

    if (!csrf_valido()) {
        $error = "La sesión del formulario expiró. Intentá nuevamente.";
    } else {
        $error = registrar_pago($conex, $idVenta, $_POST["monto"] ?? "");

        if ($error === null) {
            header("Location: pagos.php?id=$idVenta&ok=1");
            exit();
        }
    }
}

$stmt = $conex->prepare("SELECT
                v.id_venta,
                v.fecha_venta,
                v.cliente,
                v.total,
                v.estado,
                CONCAT(u.nombre,' ',u.apellido) AS vendedor
            FROM ventas v
            INNER JOIN usuarios u
                ON v.usuarios_id_usuario = u.id_usuario
            WHERE v.id_venta = ?");
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();

if (!$venta) {
    header("Location: ventas.php");
    exit();
}

$stmt = $conex->prepare("SELECT * FROM pagos
             WHERE ventas_id_venta = ?
             ORDER BY fecha_pago DESC, id_pago DESC");
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$resultadoPagos = $stmt->get_result();

$stmt = $conex->prepare("SELECT COALESCE(SUM(monto), 0) AS total_pagado
                   FROM pagos
                   WHERE ventas_id_venta = ?");
$stmt->bind_param("i", $idVenta);
$stmt->execute();
$totalPagado = $stmt->get_result()->fetch_assoc()["total_pagado"];

$deuda = round($venta["total"] - $totalPagado, 2);

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-cash-coin"></i>
            <h2>Registrar Pago</h2>
        </div>

    <?php if (isset($_GET["ok"])) { ?>

        <div class="alert alert-success">Pago registrado correctamente.</div>

    <?php } ?>

    <?php if (isset($error)) { ?>

        <div class="alert alert-danger">

            <?= h($error) ?>

        </div>

    <?php } ?>

    <div class="card mb-4">

        <div class="card-header" style="background-color: var(--color-primary); color: #fff;">
            Datos de la Venta
        </div>

        <div class="card-body">

            <p><strong>Venta:</strong> <?= h($venta["id_venta"]) ?></p>

            <p><strong>Cliente:</strong> <?= h($venta["cliente"]) ?></p>

            <p><strong>Vendedor:</strong> <?= h($venta["vendedor"]) ?></p>

            <p>
                <strong>Total:</strong>
                $<?= number_format($venta["total"], 2, ",", ".") ?>
            </p>

        </div>

    </div>

    <?php if ($venta["estado"] != 1) { ?>

        <div class="alert alert-warning">Esta venta todavía no fue finalizada por el vendedor.</div>

    <?php } elseif ($deuda > 0) { ?>

        <div class="card mb-4">

            <div class="card-header" style="background-color: var(--color-accent); color: #fff;">
                Registrar Nuevo Pago
            </div>

            <div class="card-body">

                <form method="POST">

                    <?= csrf_campo() ?>

                    <div class="mb-3">

                        <label class="form-label">
                            Monto
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="monto"
                            class="form-control"
                            max="<?= h($deuda) ?>"
                            required>

                    </div>

                    <button type="submit"
                        name="registrar"
                        class="btn btn-primary">

                        Registrar Pago

                    </button>

                </form>

            </div>

        </div>

    <?php } else { ?>

        <div class="alert alert-success">

            <h5><i class="bi bi-check-circle"></i> Esta venta ya fue pagada completamente.</h5>

        </div>

    <?php } ?>

    <div class="card p-3 p-md-4">

    <h4>Pagos registrados</h4>

    <table class="table table-hover align-middle">

        <thead>

            <tr>

                <th>Fecha</th>
                <th>Monto</th>

            </tr>

        </thead>

        <tbody>

            <?php while ($pago = mysqli_fetch_assoc($resultadoPagos)) { ?>

                <tr>

                    <td><?= h($pago["fecha_pago"]) ?></td>

                    <td>
                        $<?= number_format($pago["monto"], 2, ",", ".") ?>
                    </td>

                </tr>

            <?php } ?>

        </tbody>

    </table>

    <div class="row mt-4 pt-3 border-top">

        <div class="col-md-6">

            <h5>

                Total Pagado:

                <span class="badge-estado badge-activo">

                    $<?= number_format($totalPagado, 2, ",", ".") ?>

                </span>

            </h5>

        </div>

        <div class="col-md-6 text-end">

            <h5>

                Deuda Pendiente:

                <?php if ($deuda > 0) { ?>

                    <span class="badge-estado" style="background-color:#f8d7da; color:#842029;">

                        $<?= number_format($deuda, 2, ",", ".") ?>

                    </span>

                <?php } else { ?>

                    <span class="badge-estado badge-activo">

                        Sin deuda

                    </span>

                <?php } ?>

            </h5>

        </div>

    </div>

    </div>

    <div class="text-center mt-4">
        <a href="ventas.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i>
            Volver
        </a>
    </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>