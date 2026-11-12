<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>

<header>

  <!-- Encabezado principal -->

  <div class="encabezado-sistema">

    <a href="/sistema_beneficios/index.php" class="logo-container">
      <img
        src="/sistema_beneficios/imagenes/logo.png"
        alt="Logo Sistema de Beneficios"
        class="logo-sistema">
    </a>

    <div class="titulo-sistema">
      <h1>Sistema de Gestión de Beneficios</h1>
      <p>Administración y control del sistema</p>
    </div>

  </div>

  <!-- Menú -->

  <nav class="navbar navbar-expand-md navbar-dark navbar-sistema">

    <div class="container-fluid">

      <a class="navbar-brand d-md-none" href="/sistema_beneficios/index.php">
        Sistema de Beneficios
      </a>

      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Abrir menú">

        <span class="navbar-toggler-icon"></span>

      </button>

      <div class="collapse navbar-collapse" id="navbarNav">

        <?php
        if (
          !isset($_SESSION["dniadmin"]) &&
          !isset($_SESSION["dnivendedor"])
        ) {
        ?>

          <!-- Usuario sin sesión -->
          <ul class="navbar-nav">

            <li class="nav-item">
              <a class="nav-link" href="/sistema_beneficios/index.php">
                <i class="bi bi-house"></i>
                Inicio
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/sistema_beneficios/formlogin.php">
                <i class="bi bi-box-arrow-in-right"></i>
                Ingresar
              </a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/sistema_beneficios/registro.php">
                <i class="bi bi-person-plus"></i>
                Registrarse como vendedor
              </a>
            </li>

          </ul>

        <?php
        } elseif (isset($_SESSION["dniadmin"])) {
        ?>

          <!-- Administrador -->
          <ul class="navbar-nav">

            <li class="nav-item">
              <a class="nav-link" href="/sistema_beneficios/admin/inicio_admin.php">
                <i class="bi bi-speedometer2"></i>
                Panel de Administración
              </a>
            </li>

          </ul>

          <ul class="navbar-nav ms-auto align-items-md-center">

            <li class="nav-item usuario-navbar">

              <i class="bi bi-person-circle"></i>

              <?= htmlspecialchars($_SESSION["nombrecompleto"]); ?>

            </li>

            <li class="nav-item">

              <a class="btn btn-salir" href="/sistema_beneficios/salir.php">

                <i class="bi bi-box-arrow-right"></i>

                Salir

              </a>

            </li>

          </ul>

        <?php
        } elseif (isset($_SESSION["dnivendedor"])) {
        ?>

          <!-- Vendedor -->
          <ul class="navbar-nav">

            <li class="nav-item">

              <a class="nav-link" href="/sistema_beneficios/vendedor/inicio_vendedor.php">

                <i class="bi bi-shop"></i>

                Panel de Vendedor

              </a>

            </li>

          </ul>

          <ul class="navbar-nav ms-auto align-items-md-center">

            <li class="nav-item usuario-navbar">

              <i class="bi bi-person-circle"></i>

              <?= htmlspecialchars($_SESSION["nombrecompleto"]); ?>

            </li>

            <li class="nav-item">

              <a class="btn btn-salir" href="/sistema_beneficios/salir.php">

                <i class="bi bi-box-arrow-right"></i>

                Salir

              </a>

            </li>

          </ul>

        <?php
        }
        ?>

      </div>

    </div>

  </nav>

</header>