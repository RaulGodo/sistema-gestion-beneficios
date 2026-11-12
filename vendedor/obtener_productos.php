<?php
session_start();

if (
    !isset($_SESSION["dnivendedor"]) ||
    $_SESSION["rol"] != "vendedor"
) {
    exit();
}

include("../conexion.php");

if (!isset($_GET["id_beneficio"])) {
    exit;
}

$idBeneficio = (int) $_GET["id_beneficio"];
$idUsuario = (int) $_SESSION["id_usuario"];

$sql = "SELECT
            id_producto,
            nombre,
            precio,
            stock_actual
        FROM productos
        WHERE beneficios_id_beneficio = ?
        AND stock_actual > 0
        AND EXISTS (
            SELECT 1 FROM beneficios_has_usuarios bu
            WHERE bu.beneficios_id_beneficio = productos.beneficios_id_beneficio
            AND bu.usuarios_id_usuario = ?
        )
        ORDER BY nombre";

$stmt = $conex->prepare($sql);
$stmt->bind_param("ii", $idBeneficio, $idUsuario);
$stmt->execute();

$resultado = $stmt->get_result();

echo '<option value="">Seleccione un producto</option>';

while ($producto = $resultado->fetch_assoc()) {

    echo '<option
            value="' . $producto["id_producto"] . '"
            data-precio="' . $producto["precio"] . '"
            data-stock="' . $producto["stock_actual"] . '">

            ' . $producto["nombre"] . ' - $' . $producto["precio"] . '

          </option>';
}
