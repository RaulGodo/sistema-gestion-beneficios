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

$idUsuario = (int) $_SESSION["id_usuario"];

$sql = "SELECT
            v.id_venta,
            v.fecha_venta,
            v.cliente,
            b.nombre AS beneficio,
            v.total,
            COALESCE(SUM(p.monto),0) AS total_pagado
        FROM ventas v
        INNER JOIN beneficios b
            ON v.beneficios_id_beneficio = b.id_beneficio
        LEFT JOIN pagos p
            ON v.id_venta = p.ventas_id_venta
        WHERE v.usuarios_id_usuario = $idUsuario
        GROUP BY
            v.id_venta,
            v.fecha_venta,
            v.cliente,
            b.nombre,
            v.total
        ORDER BY v.fecha_venta DESC";

$resultado = mysqli_query($conex, $sql);

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-exclamation-circle"></i>
            <h2>Mi Deuda</h2>
        </div>

        <?php
        $totalAdeudado = 0;
        ?>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>Venta</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Beneficio</th>
                    <th>Total</th>
                    <th>Pagado</th>
                    <th>Deuda</th>
                    <th>Acción</th>

                </tr>

            </thead>


            <tbody>

                <?php if (mysqli_num_rows($resultado) > 0) { ?>

                    <?php while ($venta = mysqli_fetch_assoc($resultado)) {

                        $deuda = $venta["total"] - $venta["total_pagado"];

                        $totalAdeudado += $deuda;
                    ?>

                        <tr>

                            <td><?= h($venta["id_venta"]) ?></td>

                            <td><?= date("d/m/Y", strtotime($venta["fecha_venta"])) ?></td>

                            <td><?= h($venta["cliente"]) ?></td>

                            <td><?= h($venta["beneficio"]) ?></td>

                            <td>$<?= number_format($venta["total"], 2, ",", ".") ?></td>

                            <td>$<?= number_format($venta["total_pagado"], 2, ",", ".") ?></td>

                            <td>

                                <?php if ($deuda > 0) { ?>

                                    <span class="badge-estado" style="background-color:#f8d7da; color:#842029;">

                                        $<?= number_format($deuda, 2, ",", ".") ?>

                                    </span>

                                <?php } else { ?>

                                    <span class="badge-estado badge-activo">

                                        Sin deuda

                                    </span>

                                <?php } ?>

                            </td>

                            <td>

                                <?php if ($deuda > 0) { ?>

                                    <a href="registrar_pago.php?id=<?= (int) $venta["id_venta"] ?>"
                                        class="btn btn-success btn-sm">

                                        Registrar cobro

                                    </a>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="8" class="text-center">

                            No posee ventas registradas.

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

        <div class="card mt-4" style="box-shadow:none; border:1px solid var(--color-border);">

            <?php if ($totalAdeudado > 0) { ?>

                <div class="card-header" style="background-color:#dc3545; color:#fff;">

            <?php } else { ?>

                <div class="card-header" style="background-color: var(--color-primary); color:#fff;">

            <?php } ?>

                Resumen

            </div>

            <div class="card-body">

                <h4 class="mb-0">

                    Total Adeudado:

                    <?php if ($totalAdeudado > 0) { ?>

                        <span class="badge-estado" style="background-color:#f8d7da; color:#842029;">

                            $<?= number_format($totalAdeudado, 2, ",", ".") ?>

                        </span>

                    <?php } else { ?>

                        <span class="badge-estado badge-activo">

                            Sin deuda

                        </span>

                    <?php } ?>

                </h4>

            </div>

        </div>

        </div>

        <div class="text-center mt-4">
            <a href="inicio_vendedor.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i>
                Volver
            </a>
        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>