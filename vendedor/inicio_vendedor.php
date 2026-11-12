<?php
session_start();

if (
    !isset($_SESSION["dnivendedor"]) ||
    $_SESSION["rol"] != "vendedor"
) {
    header("Location: ../formlogin.php");
    exit();
}

require_once("../funciones.php");
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include('../header.php'); ?>

    <div class="container mt-5">

        <h2 class="text-center mb-4">
            Bienvenido <?= h($_SESSION["nombrecompleto"]) ?>
        </h2>

        <div class="row g-4">

            <div class="col-md-4">
                <a href="registrar_venta.php" class="panel-card c-1">
                    <i class="bi bi-cart-plus"></i>
                    <span>Registrar Venta</span>
                </a>
            </div>

            <div class="col-md-4">
                <a href="mis_ventas.php" class="panel-card c-2">
                    <i class="bi bi-receipt"></i>
                    <span>Mis Ventas</span>
                </a>
            </div>

            <div class="col-md-4">
                <a href="mis_pagos.php" class="panel-card c-3">
                    <i class="bi bi-credit-card"></i>
                    <span>Mis Pagos</span>
                </a>
            </div>

            <div class="col-md-4">
                <a href="mi_deuda.php" class="panel-card c-6">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Ver Deuda</span>
                </a>
            </div>

            <div class="col-md-4">
                <a href="rendicion.php" class="panel-card c-4">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Realizar Rendición</span>
                </a>
            </div>

            <div class="col-md-4">
                <a href="mis_rendiciones.php" class="panel-card c-5">
                    <i class="bi bi-clock-history"></i>
                    <span>Mis Rendiciones</span>
                </a>
            </div>
        </div>

    </div>

    <?php include('../footer.php'); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>