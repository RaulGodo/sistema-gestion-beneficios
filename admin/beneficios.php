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
    $descripcion = trim($_POST["descripcion"]);
    $fecha_inicio = $_POST["fecha_inicio"];
    $fecha_fin = $_POST["fecha_fin"];
    $estado = $_POST["estado"];

    if ($fecha_fin < $fecha_inicio) {

        $mensaje = "La fecha de finalización debe ser posterior a la fecha de inicio.";
    } else {

        // Verificar que no exista otro beneficio con el mismo nombre
        $sqlExiste = "SELECT id_beneficio
                      FROM beneficios
                      WHERE nombre = ?";

        $stmtExiste = $conex->prepare($sqlExiste);
        $stmtExiste->bind_param("s", $nombre);
        $stmtExiste->execute();
        $resultadoExiste = $stmtExiste->get_result();

        if ($resultadoExiste->num_rows > 0) {

            $mensaje = "Ya existe un beneficio con ese nombre.";
        } else {

            $sql = "INSERT INTO beneficios
                    (nombre, descripcion, fecha_inicio, fecha_fin, estado)
                    VALUES (?, ?, ?, ?, ?)";

            $stmt = $conex->prepare($sql);

            $stmt->bind_param(
                "ssssi",
                $nombre,
                $descripcion,
                $fecha_inicio,
                $fecha_fin,
                $estado
            );

            if ($stmt->execute()) {

                // Se pasa directo a elegir los vendedores del nuevo beneficio
                header("Location: asignar_vendedores.php?id=" . $conex->insert_id . "&nuevo=1");
                exit();
            } else {

                $mensaje = "Error al registrar el beneficio.";
            }
        }
    }
}

$sqlBeneficios = "SELECT b.*,
                    (SELECT COUNT(*)
                     FROM beneficios_has_usuarios bu
                     WHERE bu.beneficios_id_beneficio = b.id_beneficio) AS cant_vendedores
                  FROM beneficios b
                  ORDER BY b.fecha_inicio DESC";

$resultadoBeneficios = $conex->query($sqlBeneficios);
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>
    <?php include("../header.php"); ?>

    <div class="container col-lg-10 mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-gift"></i>
            <h2>Gestión de Beneficios</h2>
            <p>Creá y administrá los beneficios activos del sistema</p>
        </div>

        <?php if (isset($mensaje)) { ?>

            <div class="alert alert-danger">
                <?= h($mensaje) ?>
            </div>

        <?php } ?>

        <?php if (isset($_GET["cerrado"])) { ?>

            <div class="alert alert-info">
                Beneficio cerrado. Podés volver a abrirlo desde "Editar".
            </div>

        <?php } ?>

        <?php if (isset($_GET["asignado"])) { ?>

            <div class="alert alert-success">
                Vendedores del beneficio actualizados correctamente.
            </div>

        <?php } ?>

        <?php if (isset($_GET["ok"])) { ?>

            <div class="alert alert-success">
                Beneficio registrado correctamente.
            </div>

        <?php } ?>

        <div class="card p-4 p-md-5 mb-4">

            <form method="post">

                <?= csrf_campo() ?>

            <div class="mb-3">

                <label>Nombre</label>

                <input
                    type="text"
                    name="nombre"
                    class="form-control"
                    required>

            </div>

            <div class="mb-3">

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    class="form-control"></textarea>

            </div>

            <div class="row">

                <div class="col-md-4">

                    <label>Fecha Inicio</label>

                    <input
                        type="date"
                        name="fecha_inicio"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-4">

                    <label>Fecha Fin</label>

                    <input
                        type="date"
                        name="fecha_fin"
                        class="form-control"
                        required>

                </div>

                <div class="col-md-4">

                    <label>Estado</label>

                    <select
                        name="estado"
                        class="form-select">

                        <option value="1">
                            Activo
                        </option>

                        <option value="0">
                            Inactivo
                        </option>

                    </select>

                </div>

            </div>

            <div class="mt-4 text-center">

                <button
                    type="submit"
                    name="guardar"
                    class="btn btn-primary px-5">

                    Guardar Beneficio

                </button>

            </div>

        </form>

        </div>

        <div class="card p-3 p-md-4">

        <h4>Beneficios Registrados</h4>

        <table class="table table-striped table-hover align-middle">

            <thead>

                <tr>

                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Vendedores</th>
                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody>

                <?php while ($beneficio = $resultadoBeneficios->fetch_assoc()) { ?>

                    <tr>

                        <td><?= h($beneficio["id_beneficio"]) ?></td>

                        <td><?= h($beneficio["nombre"]) ?></td>

                        <td><?= h($beneficio["fecha_inicio"]) ?></td>

                        <td><?= h($beneficio["fecha_fin"]) ?></td>

                        <td>

                            <?= h($beneficio["estado"] == 1 ? "Activo" : "Inactivo") ?>

                        </td>

                        <td>

                            <?php if ((int) $beneficio["cant_vendedores"] == 0) { ?>
                                <span class="text-danger">Sin asignar</span>
                            <?php } else { ?>
                                <?= (int) $beneficio["cant_vendedores"] ?>
                            <?php } ?>

                        </td>

                        <td>

                            <a
                                href="asignar_vendedores.php?id=<?= (int) $beneficio["id_beneficio"] ?>"
                                class="btn btn-primary btn-sm">

                                Vendedores

                            </a>

                            <a
                                href="editar_beneficio.php?id=<?= h($beneficio["id_beneficio"]) ?>"
                                class="btn btn-warning btn-sm">

                                Editar

                            </a>

                            <form method="post" action="eliminar_beneficio.php" class="d-inline"
                                    onsubmit="return confirm('¿Desea cerrar este beneficio? Dejará de estar disponible para nuevas ventas.');">

                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $beneficio["id_beneficio"] ?>">

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Cerrar
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