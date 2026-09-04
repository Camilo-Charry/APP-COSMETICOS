
<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

function redirigir($tipo, $mensaje)
{
    header(
        "Location: categorias.php?" .
        $tipo .
        "=" .
        rawurlencode($mensaje)
    );

    exit;
}

$id = isset($_GET["id"])
    ? (int)$_GET["id"]
    : 0;

if ($id <= 0) {

    redirigir(
        "error",
        "Categoría no válida."
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | BUSCAR CATEGORÍA
    |--------------------------------------------------------------------------
    */

    $stmt = $conexion->prepare("
        SELECT
            id,
            nombre,
            estado
        FROM categorias
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    $categoria = $stmt->fetch();

    if (!$categoria) {

        redirigir(
            "error",
            "La categoría no existe."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CAMBIAR ESTADO
    |--------------------------------------------------------------------------
    */

    $nuevoEstado =
        (int)$categoria["estado"] === 1
            ? 0
            : 1;

    $stmtActualizar =
        $conexion->prepare("
            UPDATE categorias
            SET estado = :estado
            WHERE id = :id
        ");

    $stmtActualizar->execute([
        ":estado" => $nuevoEstado,
        ":id" => $id
    ]);

    if ($nuevoEstado === 1) {

        redirigir(
            "mensaje",
            "Categoría activada correctamente."
        );

    } else {

        redirigir(
            "mensaje",
            "Categoría desactivada correctamente."
        );
    }

} catch (PDOException $e) {

    redirigir(
        "error",
        "No se pudo cambiar el estado de la categoría."
    );
}

