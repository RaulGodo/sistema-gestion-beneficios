<?php
session_start();
require_once("funciones.php");
?>

<!DOCTYPE html>
<html lang="es">

<?php include('head.php'); ?>

<body>
  <?php

  include('header.php');

  ?>



  <!-- Index.php contiene un Formulario de Registro de Datos -->



  <section>

    <div class="container mt-2 mb-5">
      <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">

          <div class="card p-4 p-md-5 mt-4">

            <div class="text-center mb-3">
              <i class="bi bi-shield-lock" style="font-size:2.2rem; color: var(--color-primary);"></i>
              <h2 class="mt-2 mb-1">Iniciar Sesión</h2>
              <p class="text-secondary mb-0"><small>* Dato obligatorio</small></p>
            </div>

            <?php

            if (isset($_GET["registrado"])) {
              echo "<div class='alert alert-success text-center' role='alert'><strong>Registro exitoso. Ya podés iniciar sesión.</strong></div>";
            }

            if (isset($_GET["mensaje"]) && $_GET["mensaje"] != "ok") {
              echo "<div class='alert alert-danger text-center' role='alert'><strong>" . h($_GET["mensaje"]) . "</strong></div>";
            }

            ?>

            <form class="row g-3" action="validarDatosAcceso.php" method="post">

              <div class="col-12">
                <label for="dni" class="form-label">* DNI</label>
                <input type="text" class="form-control" name="dni" id="dni" placeholder="Ingrese tu DNI">
              </div>

              <div class="col-12">
                <label for="clave" class="form-label">* Clave</label>
                <input type="password" class="form-control" name="clave" id="clave" placeholder="Ingrese tu clave">
              </div>

              <div class="col-12">
                <label for="perfil" class="form-label">* Perfil</label>
                <select class="form-select" name="perfil" id="perfil" aria-label="Seleccionar perfil">
                  <option value="vendedor">Vendedor</option>
                  <option value="administrador">Administrador</option>
                </select>
              </div>

              <div class="col-12 mt-4 text-center">
                <button type="submit" class="btn btn-primary w-100" name="btn_registrar" id="btn_registrar">
                  <i class="bi bi-box-arrow-in-right"></i>
                  Ingresar
                </button>
              </div>

            </form>

            <p class="text-center mt-3 mb-0">
              ¿Querés ser vendedor voluntario?
              <a href="registro.php">Registrate acá</a>
            </p>

          </div>

        </div>
      </div>
    </div>

  </section>

  <?php
  include('footer.php');
  ?>

  <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>