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

$id = (int) ($_GET["id"] ?? $_POST["id"] ?? 0);

$stmt = $conex->prepare("SELECT * FROM beneficios WHERE id_beneficio = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$beneficio = $stmt->get_result()->fetch_assoc();

if (!$beneficio) {
    header("Location: beneficios.php");
    exit();
}

$mensaje = "";

if (isset($_POST["guardar"])) {

    if (!csrf_valido()) {

        $mensaje = "La sesión del formulario expiró. Intentá nuevamente.";
    } else {

        $seleccion = array_map("intval", $_POST["vendedores"] ?? []);

        $conex->begin_transaction();

        try {

            // Solo se aceptan ids que realmente sean vendedores
            $validos = [];

            $rs = $conex->query("SELECT id_usuario FROM usuarios WHERE rol = 'vendedor'");

            while ($f = $rs->fetch_assoc()) {
                $validos[(int) $f["id_usuario"]] = true;
            }

            $stmt = $conex->prepare(
                "DELETE FROM beneficios_has_usuarios WHERE beneficios_id_beneficio = ?"
            );
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $stmt = $conex->prepare(
                "INSERT INTO beneficios_has_usuarios
                 (beneficios_id_beneficio, usuarios_id_usuario) VALUES (?, ?)"
            );

            foreach (array_unique($seleccion) as $idVendedor) {

                if (!isset($validos[$idVendedor])) {
                    continue;
                }

                $stmt->bind_param("ii", $id, $idVendedor);
                $stmt->execute();
            }

            $conex->commit();

            header("Location: beneficios.php?asignado=1");
            exit();
        } catch (Throwable $e) {

            $conex->rollback();
            $mensaje = "No se pudieron guardar las asignaciones.";
        }
    }
}

// Vendedores activos + los ya asignados (aunque hoy estén inactivos, para poder verlos/quitarlos)
$stmt = $conex->prepare(
    "SELECT u.id_usuario, u.apellido, u.nombre, u.dni, u.estado,
            (bu.usuarios_id_usuario IS NOT NULL) AS asignado
     FROM usuarios u
     LEFT JOIN beneficios_has_usuarios bu
            ON bu.usuarios_id_usuario = u.id_usuario
           AND bu.beneficios_id_beneficio = ?
     WHERE u.rol = 'vendedor'
       AND (u.estado = 'Activo' OR bu.usuarios_id_usuario IS NOT NULL)
     ORDER BY u.apellido, u.nombre"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$vendedores = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>
    <?php include("../header.php"); ?>

    <div class="container col-lg-8 mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-people"></i>
            <h2>Vendedores del beneficio</h2>
            <p><?= h($beneficio["nombre"]) ?></p>
        </div>

        <?php if ($mensaje !== "") { ?>
            <div class="alert alert-danger"><?= h($mensaje) ?></div>
        <?php } ?>

        <?php if (isset($_GET["nuevo"])) { ?>
            <div class="alert alert-success">
                Beneficio registrado. Ahora elegí qué vendedores pueden venderlo.
            </div>
        <?php } ?>

        <div class="card p-4 p-md-5">

            <form method="post">

                <?= csrf_campo() ?>
                <input type="hidden" name="id" value="<?= (int) $id ?>">

                <p class="text-muted">
                    Solo los vendedores tildados verán este beneficio y podrán registrar ventas de sus productos.
                </p>

                <?php if ($vendedores->num_rows == 0) { ?>

                    <div class="alert alert-warning">
                        No hay vendedores activos. Creá uno desde
                        <a href="vendedores.php">Vendedores</a>.
                    </div>

                <?php } else { ?>

                    <div class="form-check mb-3 border-bottom pb-2">
                        <input class="form-check-input" type="checkbox" id="todos">
                        <label class="form-check-label fw-bold" for="todos">Seleccionar todos</label>
                    </div>

                    <?php while ($v = $vendedores->fetch_assoc()) { ?>

                        <div class="form-check mb-2">
                            <input
                                class="form-check-input vendedor"
                                type="checkbox"
                                name="vendedores[]"
                                id="v<?= (int) $v["id_usuario"] ?>"
                                value="<?= (int) $v["id_usuario"] ?>"
                                <?= $v["asignado"] ? "checked" : "" ?>>
                            <label class="form-check-label" for="v<?= (int) $v["id_usuario"] ?>">
                                <?= h($v["apellido"] . " " . $v["nombre"]) ?>
                                <small class="text-muted">DNI <?= h($v["dni"]) ?></small>
                                <?php if ($v["estado"] != "Activo") { ?>
                                    <span class="badge-estado badge-inactivo">Inactivo</span>
                                <?php } ?>
                            </label>
                        </div>

                    <?php } ?>

                <?php } ?>

                <div class="mt-4 text-center">
                    <button type="submit" name="guardar" class="btn btn-success px-5">
                        Guardar asignaciones
                    </button>
                    <a href="beneficios.php" class="btn btn-secondary">Cancelar</a>
                </div>

            </form>

        </div>

    </div>

    <?php include("../footer.php"); ?>
    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        const todos = document.getElementById("todos");
        if (todos) {
            todos.addEventListener("change", function() {
                document.querySelectorAll(".vendedor").forEach(c => c.checked = this.checked);
            });
        }
    </script>
</body>

</html>
