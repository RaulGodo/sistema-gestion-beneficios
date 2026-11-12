<?php

session_start();

require_once("conexion.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: formlogin.php");
    exit();
}

$dni_usu = trim($_POST["dni"] ?? "");
$clave_usu = $_POST["clave"] ?? "";
$tipo_usu = $_POST["perfil"] ?? "";

$sql = "SELECT *
        FROM usuarios
        WHERE dni = ?
        AND rol = ?
        AND estado = 'Activo'";

$stmt = $conex->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conex->error);
}

$stmt->bind_param(
    "ss",
    $dni_usu,
    $tipo_usu
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $fila = $result->fetch_assoc();

    if (password_verify($clave_usu, $fila["password"])) {

        session_regenerate_id(true);

        $_SESSION["id_usuario"] = $fila["id_usuario"];

        $_SESSION["nombrecompleto"] =
            $fila["nombre"] . " " . $fila["apellido"];

        $_SESSION["rol"] = $fila["rol"];

        if ($fila["rol"] == "administrador") {

            $_SESSION["dniadmin"] = $fila["dni"];

            header("Location: admin/inicio_admin.php");
            exit();
        } elseif ($fila["rol"] == "vendedor") {

            $_SESSION["dnivendedor"] = $fila["dni"];

            header("Location: vendedor/inicio_vendedor.php");
            exit();
        }
    } else {

        $error = "Contraseña incorrecta.";

        header(
            "Location: formlogin.php?mensaje=" .
                urlencode($error)
        );

        exit();
    }
} else {

    $error = "Usuario inexistente, inactivo o perfil incorrecto.";

    header(
        "Location: formlogin.php?mensaje=" .
            urlencode($error)
    );

    exit();
}
