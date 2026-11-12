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
            p.id_pago,
            p.fecha_pago,
            p.monto,
            v.id_venta,
            v.cliente
        FROM pagos p
        INNER JOIN ventas v
            ON p.ventas_id_venta = v.id_venta
        WHERE v.usuarios_id_usuario = $idUsuario
        ORDER BY p.fecha_pago DESC";

$resultado = mysqli_query($conex, $sql);

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <?php
    $totalCobrado = 0;
    ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-credit-card"></i>
            <h2>Mis Pagos</h2>
        </div>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>Venta</th>
                    <th>Fecha del Pago</th>
                    <th>Cliente</th>
                    <th>Monto</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($resultado) > 0) { ?>

                    <?php while ($pago = mysqli_fetch_assoc($resultado)) {

                        $totalCobrado += $pago["monto"];

                    ?>

                        <tr>

                            <td><?= h($pago["id_venta"]) ?></td>

                            <td><?= date("d/m/Y", strtotime($pago["fecha_pago"])) ?></td>

                            <td><?= h($pago["cliente"]) ?></td>

                            <td>

                                $<?= number_format($pago["monto"], 2, ",", ".") ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="4" class="text-center">

                            No posee pagos registrados.

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

        <div class="card mt-4" style="box-shadow:none; border:1px solid var(--color-border);">

            <div class="card-header" style="background-color: var(--color-primary); color:#fff;">

                Resumen

            </div>

            <div class="card-body">

                <h4 class="mb-0">

                    Total Cobrado:

                    <span class="badge-estado badge-activo">

                        $<?= number_format($totalCobrado, 2, ",", ".") ?>

                    </span>

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