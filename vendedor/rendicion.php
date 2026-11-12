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

/* TOTAL COBRADO */

$sqlCobrado = "SELECT COALESCE(SUM(p.monto),0) AS total_cobrado
FROM pagos p
INNER JOIN ventas v
ON p.ventas_id_venta = v.id_venta
WHERE v.usuarios_id_usuario = $idUsuario";

$resCobrado = mysqli_query($conex, $sqlCobrado);

$cobrado = mysqli_fetch_assoc($resCobrado);

$totalCobrado = $cobrado["total_cobrado"];


/* TOTAL RENDIDO */

$sqlRendido = "SELECT COALESCE(SUM(monto_rendido),0) AS total_rendido
FROM rendiciones
WHERE usuarios_id_usuario = $idUsuario";

$resRendido = mysqli_query($conex, $sqlRendido);

$rendido = mysqli_fetch_assoc($resRendido);

$totalRendido = $rendido["total_rendido"];


/* PENDIENTE */

$pendiente = $totalCobrado - $totalRendido;
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container col-lg-8 mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-clipboard-check"></i>
            <h2>Realizar Rendición</h2>
        </div>

        <?php if (isset($_GET["ok"])) { ?>
            <div class="alert alert-success">Rendición registrada correctamente.</div>
        <?php } ?>

        <?php if (isset($_GET["error"])) { ?>
            <div class="alert alert-danger"><?= h($_GET["error"]) ?></div>
        <?php } ?>

        <div class="card mb-4">

            <div class="card-header" style="background-color: var(--color-primary); color: #fff;">

                Resumen

            </div>

            <div class="card-body">

                <h5>Total Cobrado:
                    <span class="badge-estado badge-activo">
                        $<?= number_format($totalCobrado, 2, ",", ".") ?>
                    </span>
                </h5>

                <h5>Total Rendido:
                    <span class="badge-estado" style="background-color:#e7f0ff; color:#1f4f8f;">
                        $<?= number_format($totalRendido, 2, ",", ".") ?>
                    </span>
                </h5>

                <h5>Pendiente:
                    <span class="badge-estado" style="background-color:#f8d7da; color:#842029;">
                        $<?= number_format($pendiente, 2, ",", ".") ?>
                    </span>
                </h5>

            </div>

        </div>

        <div class="card p-4 p-md-5">

        <form action="guardar_rendicion.php" method="POST">

            <?= csrf_campo() ?>

            <div class="mb-3">

                <label class="form-label">

                    Monto a rendir

                </label>

                <input
                    type="number"
                    name="monto"
                    class="form-control"
                    min="0.01"
                    step="0.01"
                    max="<?= h($pendiente) ?>"
                    required>

            </div>

            <button class="btn btn-primary">

                <i class="bi bi-check-circle"></i>
                Registrar Rendición

            </button>

            <a href="inicio_vendedor.php" class="btn btn-secondary">

                Volver

            </a>

        </form>

        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>