<?php
session_start();

if (
    !isset($_SESSION["dniadmin"]) ||
    $_SESSION["rol"] != "administrador"
) {
    header("Location: ../formlogin.php");
    exit();
}

include("../conexion.php");

if (isset($_POST["guardar"]) && !csrf_valido()) {

    $mensaje = "La sesión del formulario expiró. Intentá nuevamente.";
} elseif (isset($_POST["guardar"])) {

    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $dni = trim($_POST["dni"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"]);

    $password = password_hash(
        $_POST["password"],
        PASSWORD_DEFAULT
    );

    $estado = $_POST["estado"];

    $stmtDni = $conex->prepare("SELECT id_usuario FROM usuarios WHERE dni = ?");
    $stmtDni->bind_param("s", $dni);
    $stmtDni->execute();

    if ($stmtDni->get_result()->num_rows > 0) {

        $mensaje = "Ya existe un usuario con ese DNI.";
    } elseif (strlen($_POST["password"]) < 6) {

        $mensaje = "La clave debe tener al menos 6 caracteres.";
    } elseif (!in_array($estado, ["Activo", "Inactivo"], true)) {

        $mensaje = "El estado no es válido.";
    } else {

    $sql = "INSERT INTO usuarios
            (
                nombre,
                apellido,
                dni,
                telefono,
                email,
                password,
                rol,
                estado,
                fecha_alta
            )
            VALUES
            (?, ?, ?, ?, ?, ?, 'vendedor', ?, CURDATE())";

    $stmt = $conex->prepare($sql);

    $stmt->bind_param(
        "sssssss",
        $nombre,
        $apellido,
        $dni,
        $telefono,
        $email,
        $password,
        $estado
    );

    if ($stmt->execute()) {

        header("Location: vendedores.php?ok=1");
        exit();
    }
    }
}

$sqlVendedores = "SELECT *
                  FROM usuarios
                  WHERE rol = 'vendedor'
                  ORDER BY apellido, nombre";

$resultadoVendedores = $conex->query($sqlVendedores);
?>


<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-people"></i>
            <h2>Gestión de Vendedores</h2>
            <p>Alta y administración de las cuentas de vendedores</p>
        </div>

        <?php if (isset($mensaje)) { ?>

            <div class="alert alert-danger">
                <?= h($mensaje) ?>
            </div>

        <?php } ?>

        <?php if (isset($_GET["ok"])) { ?>

            <div class="alert alert-success">

                Vendedor registrado correctamente.

            </div>

        <?php } ?>

        <?php if (isset($_GET["desactivado"])) { ?>

            <div class="alert alert-warning">
                El vendedor tiene ventas o rendiciones registradas, por eso no se borró: quedó como Inactivo.
            </div>

        <?php } ?>

        <?php if (isset($_GET["editado"])) { ?>

            <div class="alert alert-success">

                Vendedor editado correctamente.

            </div>

        <?php } ?>

        <?php if (isset($_GET["eliminado"])) { ?>

            <div class="alert alert-success">

                Vendedor eliminado correctamente.

            </div>

        <?php } ?>

        <div class="card p-4 p-md-5 mb-4">

            <form method="post">

                <?= csrf_campo() ?>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label>Nombre</label>

                        <input
                            type="text"
                            name="nombre"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Apellido</label>

                        <input
                            type="text"
                            name="apellido"
                            class="form-control"
                            required>

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label>DNI</label>

                        <input
                            type="text"
                            name="dni"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>Teléfono</label>

                        <input
                            type="text"
                            name="telefono"
                            class="form-control">

                    </div>

                    <div class="col-md-4 mb-3">

                        <label>Email</label>

                        <input
                            type="email"
                            name="email"
                            class="form-control">

                    </div>

                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label>Contraseña</label>

                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>Estado</label>

                        <select
                            name="estado"
                            class="form-select">

                            <option value="Activo">

                                Activo

                            </option>

                            <option value="Inactivo">

                                Inactivo

                            </option>

                        </select>

                    </div>

                </div>

                <div class="text-center mt-4">

                    <button
                        type="submit"
                        name="guardar"
                        class="btn btn-success px-5">

                        Guardar Vendedor

                    </button>

                </div>

            </form>

        </div>

        <div class="card p-3 p-md-4">

            <h4>Vendedores Registrados</h4>

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Apellido y Nombre</th>
                        <th>DNI</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($vendedor = $resultadoVendedores->fetch_assoc()) { ?>

                        <tr>

                            <td><?= h($vendedor["id_usuario"]) ?></td>

                            <td>

                                <?= h($vendedor["apellido"]) ?>

                                <?= h($vendedor["nombre"]) ?>

                            </td>

                            <td><?= h($vendedor["dni"]) ?></td>

                            <td><?= h($vendedor["telefono"]) ?></td>

                            <td><?= h($vendedor["email"]) ?></td>

                            <td>
                                <span class="badge-estado <?= h($vendedor["estado"] == "Activo" ? "badge-activo" : "badge-inactivo") ?>">
                                    <?= h($vendedor["estado"]) ?>
                                </span>
                            </td>

                            <td>

                                <a
                                    href="editar_vendedor.php?id=<?= h($vendedor["id_usuario"]) ?>"
                                    class="btn btn-warning btn-sm">

                                    Editar

                                </a>

                                <form method="post" action="eliminar_vendedor.php" class="d-inline"
                                    onsubmit="return confirm('¿Desea eliminar este vendedor?');">

                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $vendedor["id_usuario"] ?>">

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>