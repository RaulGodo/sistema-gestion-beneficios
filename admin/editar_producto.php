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
        FROM productos
        WHERE id_producto = ?";

$stmt = $conex->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$resultado = $stmt->get_result();

$producto = $resultado->fetch_assoc();

if (!$producto) {
    header("Location: productos.php");
    exit();
}

$sqlBeneficios = "SELECT id_beneficio, nombre
                  FROM beneficios
                  WHERE estado = 1
                  ORDER BY nombre";

$resultadoBeneficios = $conex->query($sqlBeneficios);

if (isset($_POST["guardar"]) && !csrf_valido()) {

    $mensaje = "La sesión del formulario expiró. Intentá nuevamente.";
} elseif (isset($_POST["guardar"])) {

    $nombre = trim($_POST["nombre"]);
    $descripcion = trim($_POST["descripcion"]);
    $precio = $_POST["precio"];
    $ajuste = (int) ($_POST["ajuste"] ?? 0);
    $beneficio = (int) $_POST["beneficio"];

    if ($precio <= 0) {

        $mensaje = "El precio debe ser mayor que cero.";
    } elseif ($producto["stock_actual"] + $ajuste < 0) {

        $mensaje = "El ajuste dejaría el stock en negativo (stock actual: " .
            $producto["stock_actual"] . ").";
    } else {

        $conex->begin_transaction();

        try {

            // El stock inicial NO se modifica: es el registro de lo cargado al comienzo
            $sql = "UPDATE productos
                    SET nombre = ?,
                        descripcion = ?,
                        precio = ?,
                        beneficios_id_beneficio = ?
                    WHERE id_producto = ?";

            $stmt = $conex->prepare($sql);
            $stmt->bind_param("ssdii", $nombre, $descripcion, $precio, $beneficio, $id);
            $stmt->execute();

            // El ajuste se suma al stock actual y nunca puede dejarlo negativo
            if ($ajuste != 0) {

                $stmt = $conex->prepare(
                    "UPDATE productos
                     SET stock_actual = stock_actual + ?
                     WHERE id_producto = ? AND stock_actual + ? >= 0"
                );
                $stmt->bind_param("iii", $ajuste, $id, $ajuste);
                $stmt->execute();

                if ($stmt->affected_rows == 0) {
                    throw new Exception("El ajuste dejaría el stock en negativo.");
                }
            }

            $conex->commit();

            header("Location: productos.php?editado=1");
            exit();
        } catch (Exception $e) {

            $conex->rollback();
            $mensaje = $e->getMessage();
        }
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
            <h2>Editar Producto</h2>
        </div>

        <?php if (isset($mensaje)) { ?>

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
                    value="<?= h($producto["nombre"]) ?>"
                    required>

            </div>

            <div class="mb-3">

                <label>Descripción</label>

                <textarea
                    name="descripcion"
                    class="form-control"><?= h($producto["descripcion"]) ?></textarea>

            </div>

            <div class="row">

                <div class="col-md-4">

                    <label>Precio</label>

                    <input
                        type="number"
                        step="0.01"
                        name="precio"
                        class="form-control"
                        value="<?= h($producto["precio"]) ?>"
                        required>

                </div>

                <div class="col-md-4">

                    <label>Ajuste de stock (+/-)</label>

                    <input
                        type="number"
                        name="ajuste"
                        class="form-control"
                        value="0">

                    <div class="form-text">
                        Stock inicial: <?= (int) $producto["stock_inicial"] ?> ·
                        Stock actual: <?= (int) $producto["stock_actual"] ?>.
                        Ej.: 20 repone, -5 descuenta.
                    </div>

                </div>

                <div class="col-md-4">

                    <label>Beneficio</label>

                    <select
                        name="beneficio"
                        class="form-select"
                        required>

                        <?php while ($fila = $resultadoBeneficios->fetch_assoc()) { ?>

                            <option
                                value="<?= h($fila["id_beneficio"]) ?>"

                                <?= h(($fila["id_beneficio"] == $producto["beneficios_id_beneficio"]) ? "selected" : "") ?>>

                                <?= h($fila["nombre"]) ?>

                            </option>

                        <?php } ?>

                    </select>

                </div>

            </div>

            <div class="mt-4 text-center">

                <button
                    type="submit"
                    name="guardar"
                    class="btn btn-success">

                    Guardar Cambios

                </button>

                <a
                    href="productos.php"
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