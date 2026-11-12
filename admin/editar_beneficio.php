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

if (!isset($_GET["id"])) {
    header("Location: beneficios.php");
    exit();
}

$id = (int) ($_GET["id"] ?? 0);

$sql = "SELECT * FROM beneficios
        WHERE id_beneficio = ?";

$stmt = $conex->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows == 0) {
    header("Location: beneficios.php");
    exit();
}

$beneficio = $resultado->fetch_assoc();

$mensaje = "";

if (isset($_POST["actualizar"])) {

    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $fecha_inicio = $_POST["fecha_inicio"] ?? "";
    $fecha_fin = $_POST["fecha_fin"] ?? "";
    $estado = (int) ($_POST["estado"] ?? 0);

    if (!csrf_valido()) {

        $mensaje = "La sesión del formulario expiró. Intentá nuevamente.";
    } elseif ($nombre === "") {

        $mensaje = "El nombre es obligatorio.";
    } elseif ($fecha_inicio === "" || $fecha_fin === "") {

        $mensaje = "Las fechas son obligatorias.";
    } elseif ($fecha_fin < $fecha_inicio) {

        $mensaje = "La fecha de finalización debe ser posterior a la fecha de inicio.";
    } elseif ($estado !== 0 && $estado !== 1) {

        $mensaje = "El estado no es válido.";
    } else {

        // Otro beneficio no puede tener el mismo nombre
        $stmt = $conex->prepare(
            "SELECT id_beneficio FROM beneficios WHERE nombre = ? AND id_beneficio <> ?"
        );
        $stmt->bind_param("si", $nombre, $id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $mensaje = "Ya existe otro beneficio con ese nombre.";
        }
    }

    if ($mensaje === "") {

    $sql = "UPDATE beneficios
            SET
                nombre = ?,
                descripcion = ?,
                fecha_inicio = ?,
                fecha_fin = ?,
                estado = ?
            WHERE id_beneficio = ?";

    $stmt = $conex->prepare($sql);

    $stmt->bind_param(
        "ssssii",
        $nombre,
        $descripcion,
        $fecha_inicio,
        $fecha_fin,
        $estado,
        $id
    );

    $stmt->execute();

    header("Location: beneficios.php");
    exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container col-lg-8 mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-pencil-square"></i>
            <h2>Editar Beneficio</h2>
        </div>

        <?php if ($mensaje !== "") { ?>

            <div class="alert alert-danger">
                <?= h($mensaje) ?>
            </div>

        <?php } ?>

        <div class="card p-4 p-md-5">

        <form method="post">

            <?= csrf_campo() ?>

            <div class="mb-3">

                <label>Nombre</label>

                <input
                    type="text"
                    name="nombre"
                    class="form-control"
                    value="<?= h($beneficio["nombre"]) ?>"
                    required>

            </div>

            <div class="mb-3">

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    class="form-control"><?= h($beneficio["descripcion"]) ?></textarea>

            </div>

            <div class="row">

                <div class="col-md-4">

                    <label>Fecha Inicio</label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        class="form-control"
                        value="<?= h($beneficio["fecha_inicio"]) ?>">

                </div>

                <div class="col-md-4">

                    <label>Fecha Fin</label>

                    <input
                        type="date"
                        name="fecha_fin"
                        class="form-control"
                        value="<?= h($beneficio["fecha_fin"]) ?>">

                </div>

                <div class="col-md-4">

                    <label>Estado</label>

                    <select
                        name="estado"
                        class="form-select">

                        <option value="1"
                            <?= h($beneficio["estado"] == 1 ? "selected" : "") ?>>
                            Activo
                        </option>

                        <option value="0"
                            <?= h($beneficio["estado"] == 0 ? "selected" : "") ?>>
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>

            <div class="mt-4 text-center">

                <button
                    type="submit"
                    name="actualizar"
                    class="btn btn-success">

                    Actualizar Beneficio

                </button>

                <a
                    href="beneficios.php"
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