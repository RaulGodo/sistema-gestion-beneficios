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

$sql = "SELECT
            v.id_venta,
            v.fecha_venta,
            v.cliente,
            v.total,
            v.forma_pago,
            v.estado,
            v.entregado,
            v.fecha_entrega,
            CONCAT(u.nombre,' ',u.apellido) AS vendedor,
            b.nombre AS beneficio
        FROM ventas v
        INNER JOIN usuarios u
            ON v.usuarios_id_usuario = u.id_usuario
        INNER JOIN beneficios b
            ON v.beneficios_id_beneficio = b.id_beneficio
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
            <i class="bi bi-cart-check"></i>
            <h2>Historial de Ventas</h2>
        </div>

        <?php if (isset($_GET["entrega"])) {
            $msgs = [
                "ok" => ["success", "Entrega registrada correctamente."],
                "revertida" => ["info", "Se quitó el registro de entrega."],
                "fecha" => ["danger", "La fecha de entrega no es válida."]
            ];
            if (isset($msgs[$_GET["entrega"]])) { ?>
                <div class="alert alert-<?= h($msgs[$_GET["entrega"]][0]) ?>">
                    <?= h($msgs[$_GET["entrega"]][1]) ?>
                </div>
        <?php }
        } ?>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Beneficio</th>
                <th>Total</th>
                <th>Forma de Pago</th>
                <th>Estado</th>
                <th>Entrega</th>
                <th>Acciones</th>
            </tr>
        </thead>

        <tbody>

            <?php while ($venta = mysqli_fetch_assoc($resultado)) { ?>

                <tr>

                    <td><?= h($venta["id_venta"]) ?></td>

                    <td><?= h($venta["fecha_venta"]) ?></td>

                    <td><?= h($venta["cliente"]) ?></td>

                    <td><?= h($venta["vendedor"]) ?></td>

                    <td><?= h($venta["beneficio"]) ?></td>

                    <td>$<?= number_format($venta["total"], 2, ",", ".") ?></td>

                    <td><?= h($venta["forma_pago"]) ?></td>

                    <td>
                        <?php if ($venta["estado"] == 1) { ?>
                            <span class="badge-estado badge-activo">Finalizada</span>
                        <?php } else { ?>
                            <span class="badge-estado badge-pendiente">Pendiente</span>
                        <?php } ?>
                    </td>

                    <td>
                        <?php if ($venta["entregado"] == 1) { ?>

                            <span class="badge-estado badge-activo">
                                Entregada el <?= date("d/m/Y", strtotime($venta["fecha_entrega"])) ?>
                            </span>

                            <form method="post" action="registrar_entrega.php" class="mt-1"
                                onsubmit="return confirm('¿Quitar el registro de entrega?');">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id_venta" value="<?= (int) $venta["id_venta"] ?>">
                                <input type="hidden" name="accion" value="revertir">
                                <button type="submit" class="btn btn-link btn-sm p-0">Deshacer</button>
                            </form>

                        <?php } elseif ($venta["estado"] == 1) { ?>

                            <form method="post" action="registrar_entrega.php" class="d-flex gap-1">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id_venta" value="<?= (int) $venta["id_venta"] ?>">
                                <input type="hidden" name="accion" value="entregar">
                                <input type="date" name="fecha_entrega" class="form-control form-control-sm"
                                    value="<?= date("Y-m-d") ?>" required>
                                <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
                                    Entregar
                                </button>
                            </form>

                        <?php } else { ?>

                            <span class="text-secondary">—</span>

                        <?php } ?>
                    </td>

                    <td>
                        <a href="detalle_venta.php?id=<?= h($venta["id_venta"]) ?>" class="btn btn-primary btn-sm">
                            Ver detalle
                        </a>

                        <a href="pagos.php?id=<?= h($venta["id_venta"]) ?>" class="btn btn-success btn-sm">
                            Registrar pago
                        </a>
                    </td>

                </tr>

            <?php } ?>

        </tbody>

    </table>

        </div>

        <div class="text-center mt-4">
            <a href="inicio_admin.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i>
                Volver
            </a>
        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>