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

$id = (int) ($_GET["id"] ?? 0);

$sql = "SELECT *
        FROM usuarios
        WHERE id_usuario = ? AND rol = 'vendedor'";

$stmt = $conex->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

$vendedor = $resultado->fetch_assoc();

if (!$vendedor) {
    header("Location: vendedores.php");
    exit();
}

if (isset($_POST["guardar"]) && !csrf_valido()) {

    $mensaje = "La sesión del formulario expiró. Intentá nuevamente.";
} elseif (isset($_POST["guardar"])) {

    $nombre = trim($_POST["nombre"]);
    $apellido = trim($_POST["apellido"]);
    $dni = trim($_POST["dni"]);
    $telefono = trim($_POST["telefono"]);
    $email = trim($_POST["email"]);
    $estado = $_POST["estado"];

    $stmtDni = $conex->prepare(
        "SELECT id_usuario FROM usuarios WHERE dni = ? AND id_usuario <> ?"
    );
    $stmtDni->bind_param("si", $dni, $id);
    $stmtDni->execute();

    if ($stmtDni->get_result()->num_rows > 0) {
        $mensaje = "Ya existe otro usuario con ese DNI.";
    } elseif (!in_array($estado, ["Activo", "Inactivo"], true)) {
        $mensaje = "El estado no es válido.";
    } else {

    $sql = "UPDATE usuarios
            SET
                nombre = ?,
                apellido = ?,
                dni = ?,
                telefono = ?,
                email = ?,
                estado = ?
            WHERE id_usuario = ? AND rol = 'vendedor'";

    $stmt = $conex->prepare($sql);

    $stmt->bind_param(
        "ssssssi",
        $nombre,
        $apellido,
        $dni,
        $telefono,
        $email,
        $estado,
        $id
    );

    if ($stmt->execute()) {

        header("Location: vendedores.php?editado=1");
        exit();
    }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container col-lg-9 mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-pencil-square"></i>
            <h2>Editar Vendedor</h2>
        </div>

        <?php if (isset($mensaje)) { ?>

            <div class="alert alert-danger">
                <?= h($mensaje) ?>
            </div>

        <?php } ?>

        <div class="card p-4 p-md-5">

        <form method="post">

            <?= csrf_campo() ?>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label>Nombre</label>

                    <input
                        type="text"
                        name="nombre"
                        class="form-control"
                        value="<?= h($vendedor["nombre"]) ?>"
                        required>

                </div>

                <div class="col-md-6 mb-3">

                    <label>Apellido</label>

                    <input
                        type="text"
                        name="apellido"
                        class="form-control"
                        value="<?= h($vendedor["apellido"]) ?>"
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
                        value="<?= h($vendedor["dni"]) ?>"
                        required>

                </div>

                <div class="col-md-4 mb-3">

                    <label>Teléfono</label>

                    <input
                        type="text"
                        name="telefono"
                        class="form-control"
                        value="<?= h($vendedor["telefono"]) ?>">

                </div>

                <div class="col-md-4 mb-3">

                    <label>Email</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= h($vendedor["email"]) ?>">

                </div>

            </div>

            <div class="row">

                <div class="col-md-6 mb-3">

                    <label>Estado</label>

                    <select
                        name="estado"
                        class="form-select">

                        <option
                            value="Activo"
                            <?= h(($vendedor["estado"] == "Activo") ? "selected" : "") ?>>

                            Activo

                        </option>

                        <option
                            value="Inactivo"
                            <?= h(($vendedor["estado"] == "Inactivo") ? "selected" : "") ?>>

                            Inactivo

                        </option>

                    </select>

                </div>

            </div>

            <div class="text-center mt-4">

                <button
                    type="submit"
                    name="guardar"
                    class="btn btn-success">

                    Guardar Cambios

                </button>

                <a
                    href="vendedores.php"
                    class="btn btn-secondary">

                    Cancelar

                </a>

            </div>

        </form>

        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>