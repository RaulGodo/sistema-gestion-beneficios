<?php
session_start();

require_once("conexion.php");

// Si ya hay sesión iniciada no tiene sentido registrarse
if (isset($_SESSION["dniadmin"]) || isset($_SESSION["dnivendedor"])) {
    header("Location: index.php");
    exit();
}

$errores = [];
$datos = [
    "nombre" => "",
    "apellido" => "",
    "dni" => "",
    "telefono" => "",
    "email" => ""
];

if (isset($_POST["registrar"])) {

    foreach ($datos as $campo => $vacio) {
        $datos[$campo] = trim($_POST[$campo] ?? "");
    }

    $password = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";

    if (!csrf_valido()) {
        $errores[] = "La sesión del formulario expiró. Intentá nuevamente.";
    }

    if ($datos["nombre"] === "" || $datos["apellido"] === "") {
        $errores[] = "El nombre y el apellido son obligatorios.";
    }

    if (!preg_match('/^\d{7,9}$/', $datos["dni"])) {
        $errores[] = "El DNI debe tener entre 7 y 9 números, sin puntos.";
    }

    if ($datos["telefono"] === "") {
        $errores[] = "El teléfono es obligatorio.";
    }

    if (!filter_var($datos["email"], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Ingresá un email válido.";
    }

    if (strlen($password) < 6) {
        $errores[] = "La clave debe tener al menos 6 caracteres.";
    }

    if ($password !== $password2) {
        $errores[] = "Las claves no coinciden.";
    }

    if (empty($errores)) {

        $stmt = $conex->prepare("SELECT id_usuario FROM usuarios WHERE dni = ?");
        $stmt->bind_param("s", $datos["dni"]);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $errores[] = "Ya existe un usuario registrado con ese DNI.";
        }
    }

    if (empty($errores)) {

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios
                (nombre, apellido, dni, telefono, email, password, rol, estado, fecha_alta)
                VALUES (?, ?, ?, ?, ?, ?, 'vendedor', 'Activo', CURDATE())";

        $stmt = $conex->prepare($sql);

        $stmt->bind_param(
            "ssssss",
            $datos["nombre"],
            $datos["apellido"],
            $datos["dni"],
            $datos["telefono"],
            $datos["email"],
            $hash
        );

        if ($stmt->execute()) {
            header("Location: formlogin.php?registrado=1");
            exit();
        }

        $errores[] = "No se pudo completar el registro. Intentá nuevamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<?php include('head.php'); ?>

<body>

    <?php include('header.php'); ?>

    <section>

        <div class="container mt-2 mb-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">

                    <div class="card p-4 p-md-5 mt-4">

                        <div class="text-center mb-3">
                            <i class="bi bi-person-plus" style="font-size:2.2rem; color: var(--color-primary);"></i>
                            <h2 class="mt-2 mb-1">Registro de Vendedor</h2>
                            <p class="text-secondary mb-0"><small>Participá como vendedor voluntario</small></p>
                        </div>

                        <?php if (!empty($errores)) { ?>
                            <div class="alert alert-danger" role="alert">
                                <?php foreach ($errores as $error) { ?>
                                    <div><?= h($error) ?></div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <form class="row g-3" method="post" action="registro.php">

                            <?= csrf_campo() ?>

                            <div class="col-md-6">
                                <label for="nombre" class="form-label">* Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="nombre" maxlength="45"
                                    value="<?= h($datos["nombre"]) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="apellido" class="form-label">* Apellido</label>
                                <input type="text" class="form-control" name="apellido" id="apellido" maxlength="45"
                                    value="<?= h($datos["apellido"]) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="dni" class="form-label">* DNI</label>
                                <input type="text" class="form-control" name="dni" id="dni" maxlength="9"
                                    inputmode="numeric" value="<?= h($datos["dni"]) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="telefono" class="form-label">* Teléfono</label>
                                <input type="text" class="form-control" name="telefono" id="telefono" maxlength="45"
                                    value="<?= h($datos["telefono"]) ?>" required>
                            </div>

                            <div class="col-12">
                                <label for="email" class="form-label">* Email</label>
                                <input type="email" class="form-control" name="email" id="email" maxlength="45"
                                    value="<?= h($datos["email"]) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">* Clave</label>
                                <input type="password" class="form-control" name="password" id="password" minlength="6" required>
                            </div>

                            <div class="col-md-6">
                                <label for="password2" class="form-label">* Repetir clave</label>
                                <input type="password" class="form-control" name="password2" id="password2" minlength="6" required>
                            </div>

                            <div class="col-12 mt-4 text-center">
                                <button type="submit" class="btn btn-primary w-100" name="registrar">
                                    <i class="bi bi-person-check"></i>
                                    Registrarme
                                </button>
                            </div>

                            <div class="col-12 text-center">
                                <a href="formlogin.php">Ya tengo cuenta</a>
                            </div>

                        </form>

                    </div>

                </div>
            </div>
        </div>

    </section>

    <?php include('footer.php'); ?>

    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>
