<?php
session_start();

if (
  !isset($_SESSION["dniadmin"]) ||
  $_SESSION["rol"] != "administrador"
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


  <?php
  include('../header.php');

  ?>



  <!-- Index.php contiene la página Inicial de un Sitio Web-->


  <section>

    <h3 class="text-center mt-4">
      Panel de Administración
    </h3>

    <h5 class="text-center text-secondary mb-4">
      Bienvenido <?= h($_SESSION["nombrecompleto"]) ?>
    </h5>

    <div class="row mt-5 g-4">

      <div class="col-md-4">
        <a href="beneficios.php" class="panel-card c-1">
          <i class="bi bi-gift"></i>
          <span>Beneficios</span>
        </a>
      </div>

      <div class="col-md-4">
        <a href="productos.php" class="panel-card c-2">
          <i class="bi bi-box-seam"></i>
          <span>Productos</span>
        </a>
      </div>

      <div class="col-md-4">
        <a href="vendedores.php" class="panel-card c-3">
          <i class="bi bi-people"></i>
          <span>Vendedores</span>
        </a>
      </div>

      <div class="col-md-4">
        <a href="ventas.php" class="panel-card c-4">
          <i class="bi bi-cart-check"></i>
          <span>Ventas</span>
        </a>
      </div>

      <div class="col-md-4">
        <a href="pagos.php" class="panel-card c-5">
          <i class="bi bi-cash-coin"></i>
          <span>Pagos</span>
        </a>
      </div>

      <div class="col-md-4">
        <a href="rendiciones.php" class="panel-card c-6">
          <i class="bi bi-clipboard-check"></i>
          <span>Rendiciones</span>
        </a>
      </div>

    </div>


  </section>

  <?php
  include('../footer.php');
  ?>

  <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>