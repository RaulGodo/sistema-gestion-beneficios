<?php
session_start();

if (
    !isset($_SESSION["dnivendedor"]) ||
    $_SESSION["rol"] != "vendedor"
) {
    header("Location: ../formlogin.php");
    exit();
}

include("../conexion.php");

/* ===========================
   CONSULTAR BENEFICIOS ACTIVOS
=========================== */

$usuario = (int) $_SESSION["id_usuario"];

// Solo los beneficios activos que el administrador le asignó a este vendedor
$stmt = $conex->prepare(
    "SELECT b.id_beneficio, b.nombre
     FROM beneficios b
     INNER JOIN beneficios_has_usuarios bu
             ON bu.beneficios_id_beneficio = b.id_beneficio
     WHERE b.estado = 1 AND bu.usuarios_id_usuario = ?
     ORDER BY b.nombre"
);
$stmt->bind_param("i", $usuario);
$stmt->execute();
$resultadoBeneficios = $stmt->get_result();
$sinBeneficios = ($resultadoBeneficios->num_rows == 0);

/* ===========================
   RETOMAR VENTA ABIERTA
   (si el vendedor cerró sesión antes de guardar, no se pierde)
=========================== */

if (isset($_SESSION["id_venta"])) {

    // Verificar que la venta de la sesión siga abierta y sea del vendedor
    $stmt = $conex->prepare(
        "SELECT id_venta FROM ventas
         WHERE id_venta = ? AND usuarios_id_usuario = ? AND estado = 0"
    );
    $stmt->bind_param("ii", $_SESSION["id_venta"], $usuario);
    $stmt->execute();

    if ($stmt->get_result()->num_rows == 0) {
        unset($_SESSION["id_venta"]);
    }
}

if (!isset($_SESSION["id_venta"])) {

    $stmt = $conex->prepare(
        "SELECT id_venta FROM ventas
         WHERE usuarios_id_usuario = ? AND estado = 0
         ORDER BY id_venta DESC LIMIT 1"
    );
    $stmt->bind_param("i", $usuario);
    $stmt->execute();
    $abierta = $stmt->get_result()->fetch_assoc();

    if ($abierta) {
        $_SESSION["id_venta"] = $abierta["id_venta"];
    }
}

/* ===========================
   AGREGAR PRODUCTO A LA VENTA
=========================== */

$mensajeError = "";
$mensajeOk = "";

if (isset($_POST["agregar"])) {

    $cliente = trim($_POST["cliente"] ?? "");
    $beneficio = (int) ($_POST["beneficio"] ?? 0);
    $formaPago = $_POST["forma_pago"] ?? "";
    $producto = (int) ($_POST["producto"] ?? 0);
    $cantidad = (int) ($_POST["cantidad"] ?? 0);

    if (!csrf_valido()) {

        $mensajeError = "La sesión del formulario expiró. Intentá nuevamente.";
    } elseif (!in_array($formaPago, ["Efectivo", "Transferencia"], true)) {

        $mensajeError = "La forma de pago no es válida.";
    } elseif ($cantidad < 1) {

        $mensajeError = "La cantidad debe ser al menos 1.";
    } elseif ($producto < 1) {

        $mensajeError = "Seleccioná un producto.";
    } elseif (!isset($_SESSION["id_venta"]) && $cliente === "") {

        $mensajeError = "Ingresá el nombre del cliente.";
    } else {

        $conex->begin_transaction();

        try {

            $creada = false;

            if (isset($_SESSION["id_venta"])) {

                // Venta en curso: el producto debe ser de su mismo beneficio
                $idVenta = (int) $_SESSION["id_venta"];

                $stmt = $conex->prepare(
                    "SELECT beneficios_id_beneficio FROM ventas WHERE id_venta = ?"
                );
                $stmt->bind_param("i", $idVenta);
                $stmt->execute();
                $beneficioVenta = (int) $stmt->get_result()->fetch_assoc()["beneficios_id_beneficio"];

                // Si le quitaron el beneficio mientras tenía la venta abierta, no puede seguir agregando
                $stmt = $conex->prepare(
                    "SELECT 1 FROM beneficios_has_usuarios
                     WHERE beneficios_id_beneficio = ? AND usuarios_id_usuario = ?"
                );
                $stmt->bind_param("ii", $beneficioVenta, $usuario);
                $stmt->execute();

                if ($stmt->get_result()->num_rows == 0) {
                    throw new Exception(
                        "Ya no estás asignado a este beneficio. Guardá la venta para cerrarla."
                    );
                }
            } else {

                // Venta nueva: el beneficio debe estar activo y asignado al vendedor
                $stmt = $conex->prepare(
                    "SELECT b.id_beneficio
                     FROM beneficios b
                     INNER JOIN beneficios_has_usuarios bu
                             ON bu.beneficios_id_beneficio = b.id_beneficio
                     WHERE b.id_beneficio = ? AND b.estado = 1 AND bu.usuarios_id_usuario = ?"
                );
                $stmt->bind_param("ii", $beneficio, $usuario);
                $stmt->execute();

                if ($stmt->get_result()->num_rows == 0) {
                    throw new Exception(
                        "El beneficio seleccionado no está activo o no tenés permiso para venderlo."
                    );
                }

                $beneficioVenta = $beneficio;
                $idVenta = 0;
            }

            // Producto del beneficio, bloqueado para controlar el stock
            $stmt = $conex->prepare(
                "SELECT precio, stock_actual, nombre
                 FROM productos
                 WHERE id_producto = ? AND beneficios_id_beneficio = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("ii", $producto, $beneficioVenta);
            $stmt->execute();
            $filaProducto = $stmt->get_result()->fetch_assoc();

            if (!$filaProducto) {
                throw new Exception(
                    "El producto no pertenece al beneficio de la venta en curso. " .
                        "Guardá la venta actual antes de cambiar de beneficio."
                );
            }

            if ($filaProducto["stock_actual"] < $cantidad) {
                throw new Exception(
                    "Stock insuficiente de " . $filaProducto["nombre"] .
                        ": quedan " . $filaProducto["stock_actual"] . " unidades."
                );
            }

            // Crear la venta (queda abierta: estado 0 hasta guardarla)
            if ($idVenta == 0) {

                $stmt = $conex->prepare(
                    "INSERT INTO ventas
                     (fecha_venta, cliente, total, usuarios_id_usuario,
                      beneficios_id_beneficio, forma_pago, estado)
                     VALUES (CURDATE(), ?, 0, ?, ?, ?, 0)"
                );
                $stmt->bind_param("siis", $cliente, $usuario, $beneficioVenta, $formaPago);
                $stmt->execute();

                $idVenta = $conex->insert_id;
                $creada = true;
            }

            $precio = $filaProducto["precio"];
            $subtotal = $precio * $cantidad;

            $stmt = $conex->prepare(
                "INSERT INTO detalle_venta
                 (ventas_id_venta, productos_id_producto, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iiidd", $idVenta, $producto, $cantidad, $precio, $subtotal);
            $stmt->execute();

            $stmt = $conex->prepare(
                "UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?"
            );
            $stmt->bind_param("ii", $cantidad, $producto);
            $stmt->execute();

            $conex->commit();

            $_SESSION["id_venta"] = $idVenta;

            $mensajeOk = $creada
                ? "Venta creada correctamente. ID Venta: " . $idVenta
                : "Producto agregado a la venta.";
        } catch (Exception $e) {

            $conex->rollback();
            $mensajeError = $e->getMessage();
        }
    }
}
?>

<?php

$detalleVenta = [];

$totalVenta = 0;

if (isset($_SESSION["id_venta"])) {

    $idVenta = $_SESSION["id_venta"];

    $sql = "SELECT
d.id_detalle,
p.nombre,
d.cantidad,
d.precio_unitario,
d.subtotal
FROM detalle_venta d
INNER JOIN productos p
ON d.productos_id_producto = p.id_producto
WHERE d.ventas_id_venta = ?";

    $stmt = $conex->prepare($sql);

    $stmt->bind_param("i", $idVenta);

    $stmt->execute();

    $resultadoDetalle = $stmt->get_result();

    while ($fila = $resultadoDetalle->fetch_assoc()) {

        $detalleVenta[] = $fila;

        $totalVenta += $fila["subtotal"];
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<?php include('../head.php'); ?>

<body>

    <?php include("../header.php"); ?>

    <div class="container mt-4 mb-5">

        <div class="page-header">
            <i class="bi bi-cart-plus"></i>
            <h2>Registrar Venta</h2>
        </div>

        <?php if ($mensajeError !== "") { ?>
            <div class="alert alert-danger"><?= h($mensajeError) ?></div>
        <?php } ?>

        <?php if ($mensajeOk !== "") { ?>
            <div class="alert alert-success"><?= h($mensajeOk) ?></div>
        <?php } ?>

        <?php if ($sinBeneficios && !isset($_SESSION["id_venta"])) { ?>
            <div class="alert alert-warning">
                No tenés beneficios asignados. Contactá al administrador para poder registrar ventas.
            </div>
        <?php } ?>

        <?php if (isset($_GET["vacia"])) { ?>
            <div class="alert alert-warning">
                La venta no tenía productos, por lo que no se guardó.
            </div>
        <?php } ?>

        <?php if (isset($_GET["finalizada"])) { ?>

            <div class="alert alert-success">

                Venta finalizada correctamente.

            </div>

        <?php } ?>

        <div class="card mb-4">

            <div class="card-header" style="background-color: var(--color-primary); color: #fff;">

                Nueva Venta

            </div>

            <div class="card-body">

                <form method="post">

                    <?= csrf_campo() ?>

                    <div class="mb-3">

                        <label class="form-label">

                            Cliente

                        </label>

                        <input
                            type="text"
                            name="cliente"
                            class="form-control"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Beneficio
                        </label>

                        <select
                            class="form-select"
                            name="beneficio"
                            id="beneficio"
                            required>

                            <option value="">
                                Seleccione un beneficio
                            </option>

                            <?php
                            while ($beneficio = $resultadoBeneficios->fetch_assoc()) {
                            ?>

                                <option
                                    value="<?= h($beneficio['id_beneficio']) ?>">

                                    <?= h($beneficio['nombre']) ?>

                                </option>

                            <?php
                            }
                            ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Forma de Pago

                        </label>

                        <select
                            name="forma_pago"
                            class="form-select"
                            required>

                            <option value="Efectivo">

                                Efectivo

                            </option>

                            <option value="Transferencia">

                                Transferencia

                            </option>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Producto
                        </label>

                        <select
                            class="form-select"
                            name="producto"
                            id="producto"
                            disabled>

                            <option value="">
                                Primero seleccione un beneficio
                            </option>

                        </select>

                    </div>

                    <div class="row mt-3 mb-3">

                        <div class="col-md-6">

                            <label class="form-label">

                                Precio

                            </label>

                            <input
                                type="text"
                                id="precio"
                                class="form-control"
                                readonly>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">

                                Stock Disponible

                            </label>

                            <input
                                type="text"
                                id="stock"
                                class="form-control"
                                readonly>

                        </div>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Cantidad

                        </label>

                        <input
                            type="number"
                            class="form-control"
                            id="cantidad"
                            name="cantidad"
                            min="1"
                            value="1"
                            disabled>

                    </div>

                    <button
                        type="submit"
                        name="agregar"
                        class="btn btn-primary">

                        Agregar Producto

                    </button>

                </form>

            </div>

        </div>

        <h4 class="mt-4 mb-3">

            Detalle de la Venta

        </h4>

        <div class="card p-3 p-md-4">

        <table class="table table-hover align-middle">

            <thead>

                <tr>

                    <th>Producto</th>

                    <th>Cantidad</th>

                    <th>Precio</th>

                    <th>Subtotal</th>

                    <th>Acción</th>

                </tr>

            </thead>

            <tbody>

                <?php if (count($detalleVenta) > 0) { ?>

                    <?php foreach ($detalleVenta as $detalle) { ?>

                        <tr>

                            <td><?= h($detalle["nombre"]) ?></td>

                            <td><?= h($detalle["cantidad"]) ?></td>

                            <td>$ <?= number_format($detalle["precio_unitario"], 2, ",", ".") ?></td>

                            <td>$ <?= number_format($detalle["subtotal"], 2, ",", ".") ?></td>

                            <td>

                                <form method="post" action="eliminar_detalle.php"
                                    onsubmit="return confirm('¿Desea eliminar este producto de la venta?')">

                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $detalle["id_detalle"] ?>">

                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Eliminar
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } else { ?>

                    <tr>

                        <td colspan="5" class="text-center">

                            Todavía no hay productos agregados.

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

        <div class="text-end">

            <h3>

                Total: $ <?= number_format($totalVenta, 2, ",", ".") ?>

            </h3>

            <form method="post" action="finalizar_venta.php">

                <?= csrf_campo() ?>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i>
                    Guardar Venta
                </button>

            </form>

        </div>

        </div>

    </div>

    <?php include("../footer.php"); ?>

    <script src="../bootstrap/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById("beneficio").addEventListener("change", function() {

            let idBeneficio = this.value;

            let producto = document.getElementById("producto");

            if (idBeneficio == "") {

                producto.innerHTML =
                    '<option>Primero seleccione un beneficio</option>';

                producto.disabled = true;

                document.getElementById("cantidad").disabled = true;

                document.getElementById("precio").value = "";
                document.getElementById("stock").value = "";
                document.getElementById("cantidad").value = 1;

                return;
            }

            fetch("obtener_productos.php?id_beneficio=" + idBeneficio)

                .then(respuesta => respuesta.text())

                .then(datos => {

                    producto.innerHTML = datos;

                    producto.disabled = false;

                    document.getElementById("cantidad").disabled = false;

                });

        });

        document.getElementById("producto").addEventListener("change", function() {

            let opcion = this.options[this.selectedIndex];

            let precio = opcion.getAttribute("data-precio");

            let stock = opcion.getAttribute("data-stock");

            document.getElementById("precio").value = "$ " + precio;

            document.getElementById("stock").value = stock;

            document.getElementById("cantidad").max = stock;

        });
    </script>

</body>

</html>