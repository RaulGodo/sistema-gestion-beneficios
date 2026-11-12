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
            v.total,
            v.forma_pago,
            v.estado,
            v.entregado,
            v.fecha_entrega,
            b.nombre AS beneficio
        FROM ventas v
        INNER JOIN beneficios b
            ON v.beneficios_id_beneficio = b.id_beneficio
        WHERE v.usuarios_id_usuario = $idUsuario
        ORDER BY v.id_venta DESC";

$resultado = mysqli_query($conex, $sql);

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-receipt"></i>
            <h2>Mis Ventas</h2>
        </div>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Beneficio</th>
                    <th>Total</th>
                    <th>Forma de Pago</th>
                    <th>Estado</th>
                    <th>Entrega</th>
                    <th>Acción</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($resultado) > 0) { ?>

                    <?php while ($venta = mysqli_fetch_assoc($resultado)) { ?>

                        <tr>

                            <td><?= h($venta["id_venta"]) ?></td>

                            <td><?= date("d/m/Y", strtotime($venta["fecha_venta"])) ?></td>

                            <td><?= h($venta["cliente"]) ?></td>

                            <td><?= h($venta["beneficio"]) ?></td>

                            <td>

                                $<?= number_format($venta["total"], 2, ",", ".") ?>

                            </td>

                            <td><?= h($venta["forma_pago"]) ?></td>

                            <td>

                                <?php if ($venta["estado"] == 1) { ?>

                                    <span class="badge-estado badge-activo">

                                        Finalizada

                                    </span>

                                <?php } else { ?>

                                    <span class="badge-estado badge-pendiente">

                                        Pendiente

                                    </span>

                                <?php } ?>

                            </td>

                            <td>

                                <?php if ($venta["entregado"] == 1) { ?>

                                    <span class="badge-estado badge-activo">
                                        Entregada el <?= date("d/m/Y", strtotime($venta["fecha_entrega"])) ?>
                                    </span>

                                <?php } else { ?>

                                    <span class="badge-estado badge-pendiente">
                                        Pendiente de entrega
                                    </span>

                                <?php } ?>

                            </td>

                            <td>

                                <a href="detalle_mi_venta.php?id=<?= h($venta["id_venta"]) ?>"
                                    class="btn btn-primary btn-sm">

                                    Ver detalle

                                </a>

                                <?php if ($venta["estado"] == 1) { ?>

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

                        <td colspan="9" class="text-center">

                            No posee ventas registradas.

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

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