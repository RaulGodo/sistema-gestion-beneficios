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

$idVenta = $_GET["id"];

$sqlVenta = "SELECT
                v.id_venta,
                v.fecha_venta,
                v.cliente,
                v.total,
                CONCAT(u.nombre,' ',u.apellido) AS vendedor,
                b.nombre AS beneficio
            FROM ventas v
            INNER JOIN usuarios u
                ON v.usuarios_id_usuario = u.id_usuario
            INNER JOIN beneficios b
                ON v.beneficios_id_beneficio = b.id_beneficio
            WHERE v.id_venta = $idVenta";

$resultadoVenta = mysqli_query($conex, $sqlVenta);

$venta = mysqli_fetch_assoc($resultadoVenta);

$sqlDetalle = "SELECT
                    p.nombre,
                    d.cantidad,
                    d.precio_unitario,
                    d.subtotal
                FROM detalle_venta d
                INNER JOIN productos p
                    ON d.productos_id_producto = p.id_producto
                WHERE d.ventas_id_venta = $idVenta";

$resultadoDetalle = mysqli_query($conex, $sqlDetalle);
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-receipt"></i>
            <h2>Detalle de la Venta</h2>
        </div>

    <div class="card mb-4">

        <div class="card-header" style="background-color: var(--color-primary); color: #fff;">
            Información de la Venta
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-6">
                    <strong>N° Venta:</strong> <?= h($venta["id_venta"]) ?>
                </div>

                <div class="col-md-6">
                    <strong>Fecha:</strong> <?= h($venta["fecha_venta"]) ?>
                </div>

            </div>

            <div class="row mt-3">

                <div class="col-md-6">
                    <strong>Cliente:</strong> <?= h($venta["cliente"]) ?>
                </div>

                <div class="col-md-6">
                    <strong>Vendedor:</strong> <?= h($venta["vendedor"]) ?>
                </div>

            </div>

            <div class="row mt-3">

                <div class="col-md-6">
                    <strong>Beneficio:</strong> <?= h($venta["beneficio"]) ?>
                </div>

                <div class="col-md-6">
                    <strong>Total:</strong>
                    $<?= number_format($venta["total"], 2, ",", ".") ?>
                </div>

            </div>

        </div>

    </div>

    <div class="card p-3 p-md-4">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h4 class="mb-0">Productos vendidos</h4>

        <h5 class="mb-0">
            Total:
            <span class="badge-estado badge-activo">
                $<?= number_format($venta["total"], 2, ",", ".") ?>
            </span>
        </h5>

    </div>

    <table class="table table-hover align-middle">

        <thead>

            <tr>

                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>

            </tr>

        </thead>

        <tbody>

            <?php while ($detalle = mysqli_fetch_assoc($resultadoDetalle)) { ?>

                <tr>

                    <td><?= h($detalle["nombre"]) ?></td>

                    <td><?= h($detalle["cantidad"]) ?></td>

                    <td>
                        $<?= number_format($detalle["precio_unitario"], 2, ",", ".") ?>
                    </td>

                    <td>
                        $<?= number_format($detalle["subtotal"], 2, ",", ".") ?>
                    </td>

                </tr>

            <?php } ?>

        </tbody>

    </table>

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