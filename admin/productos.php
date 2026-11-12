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
    $precio = $_POST["precio"];
    $stock = $_POST["stock"];
    $beneficio = $_POST["beneficio"];

    if ($precio <= 0) {

        $mensaje = "El precio debe ser mayor que cero.";
    } elseif ($stock < 0) {

        $mensaje = "El stock no puede ser negativo.";
    } elseif ($beneficio <= 0) {

        $mensaje = "Debe seleccionar un beneficio válido.";
    } else {

        $sql = "INSERT INTO productos
        (
            nombre,
            descripcion,
            precio,
            stock_inicial,
            stock_actual,
            beneficios_id_beneficio
        )
        VALUES
        (?, ?, ?, ?, ?, ?)";

        $stmt = $conex->prepare($sql);

        $stmt->bind_param(
            "ssdiii",
            $nombre,
            $descripcion,
            $precio,
            $stock,
            $stock,
            $beneficio
        );

        if ($stmt->execute()) {

            header("Location: productos.php?ok=1");
            exit();
        } else {

            $mensaje = "Error al guardar el producto: " . $stmt->error;
        }
    }
}

$sqlBeneficios = "SELECT id_beneficio, nombre
                  FROM beneficios
                  WHERE estado = 1
                  ORDER BY nombre";

$resultadoBeneficios = $conex->query($sqlBeneficios);

$sqlProductos = "SELECT
                    p.id_producto,
                    p.nombre,
                    p.precio,
                    p.stock_actual,
                    b.nombre AS beneficio
                 FROM productos p
                 INNER JOIN beneficios b
                    ON p.beneficios_id_beneficio = b.id_beneficio
                 ORDER BY p.nombre";

$resultadoProductos = $conex->query($sqlProductos);
?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-box-seam"></i>
            <h2>Gestión de Productos</h2>
            <p>Cargá productos y asignalos a un beneficio activo</p>
        </div>

        <?php if (isset($_GET["noeliminable"])) { ?>

            <div class="alert alert-warning">
                No se puede eliminar un producto que ya tiene ventas registradas.
                Podés dejar su stock en 0 con un ajuste.
            </div>

        <?php } ?>

        <?php if (isset($mensaje)) { ?>

            <div class="alert alert-danger">
                <?= h($mensaje) ?>
            </div>

        <?php } ?>

        <?php if (isset($_GET["ok"])) { ?>


            <div class="alert alert-success">
                Producto registrado correctamente.
            </div>

        <?php } ?>
        <?php if (isset($_GET["editado"])) { ?>

            <div class="alert alert-success">

                Producto editado correctamente.

            </div>

        <?php } ?>

        <?php if (isset($_GET["eliminado"])) { ?>

            <div class="alert alert-success">

                Producto eliminado correctamente.

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

                        <label>Precio</label>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="precio"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label>Stock Inicial</label>

                        <input
                            type="number"
                            name="stock"
                            min="0"
                            class="form-control"
                            required>

                    </div>

                    <div class="col-md-4">

                        <label>Beneficio</label>

                        <select
                            name="beneficio"
                            class="form-select"
                            required>

                            <?php while ($fila = $resultadoBeneficios->fetch_assoc()) { ?>

                                <option value="<?= h($fila["id_beneficio"]) ?>">

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
                        class="btn btn-success px-5">

                        Guardar Producto

                    </button>

                </div>

            </form>

        </div>

        <div class="card p-3 p-md-4">

            <h4>Productos Registrados</h4>

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Producto</th>
                        <th>Beneficio</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($producto = $resultadoProductos->fetch_assoc()) { ?>

                        <tr>

                            <td><?= h($producto["id_producto"]) ?></td>

                            <td><?= h($producto["nombre"]) ?></td>

                            <td><?= h($producto["beneficio"]) ?></td>

                            <td>$ <?= number_format($producto["precio"], 2, ",", ".") ?></td>

                            <td><?= h($producto["stock_actual"]) ?></td>

                            <td>

                                <a
                                    href="editar_producto.php?id=<?= h($producto["id_producto"]) ?>"
                                    class="btn btn-warning btn-sm">

                                    Editar

                                </a>

                                <form method="post" action="eliminar_producto.php" class="d-inline"
                                    onsubmit="return confirm('¿Desea eliminar este producto?');">

                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $producto["id_producto"] ?>">

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