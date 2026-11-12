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
            id_rendicion,
            fecha_rendicion,
            monto_rendido
        FROM rendiciones
        WHERE usuarios_id_usuario = $idUsuario
        ORDER BY fecha_rendicion DESC";

$resultado = mysqli_query($conex, $sql);

$totalRendido = 0;

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-clock-history"></i>
            <h2>Mis Rendiciones</h2>
        </div>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Monto Rendido</th>

                </tr>

            </thead>

            <tbody>

                <?php if (mysqli_num_rows($resultado) > 0) { ?>

                    <?php while ($rendicion = mysqli_fetch_assoc($resultado)) {

                        $totalRendido += $rendicion["monto_rendido"];

                    ?>

                        <tr>

                            <td><?= h($rendicion["id_rendicion"]) ?></td>

                            <td><?= date("d/m/Y", strtotime($rendicion["fecha_rendicion"])) ?></td>

                            <td>

                                $<?= number_format($rendicion["monto_rendido"], 2, ",", ".") ?>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="3" class="text-center">

                            No posee rendiciones registradas.

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

                    Total Rendido

                    <span class="badge-estado badge-activo">

                        $<?= number_format($totalRendido, 2, ",", ".") ?>

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