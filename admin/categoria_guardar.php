
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirigir(
        "error",
        "Método de solicitud no permitido."
    );
}

$id = isset($_POST["id"])
    ? (int)$_POST["id"]
    : 0;

$nombre = trim(
    $_POST["nombre"] ?? ""
);

$descripcion = trim(
    $_POST["descripcion"] ?? ""
);

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if ($nombre === "") {

    redirigir(
        "error",
        "El nombre de la categoría es obligatorio."
    );
}

if (mb_strlen($nombre) > 100) {

    redirigir(
        "error",
        "El nombre de la categoría es demasiado largo."
    );
}

if (mb_strlen($descripcion) > 500) {

    redirigir(
        "error",
        "La descripción es demasiado larga."
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | COMPROBAR NOMBRE DUPLICADO
    |--------------------------------------------------------------------------
    */

    $sqlDuplicado = "
        SELECT id
        FROM categorias
        WHERE LOWER(nombre) = LOWER(:nombre)
    ";

    if ($id > 0) {
        $sqlDuplicado .= "
            AND id != :id
        ";
    }

    $sqlDuplicado .= "
        LIMIT 1
    ";

    $stmtDuplicado =
        $conexion->prepare(
            $sqlDuplicado
        );

    $parametros = [
        ":nombre" => $nombre
    ];

    if ($id > 0) {
        $parametros[":id"] = $id;
    }

    $stmtDuplicado->execute(
        $parametros
    );

    if ($stmtDuplicado->fetch()) {

        redirigir(
            "error",
            "Ya existe una categoría con ese nombre."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */

    if ($id <= 0) {

        $sql = "
            INSERT INTO categorias (
                nombre,
                descripcion,
                estado
            )
            VALUES (
                :nombre,
                :descripcion,
                1
            )
        ";

        $stmt =
            $conexion->prepare($sql);

        $stmt->execute([
            ":nombre" => $nombre,
            ":descripcion" =>
                $descripcion !== ""
                    ? $descripcion
                    : null
        ]);

        redirigir(
            "mensaje",
            "Categoría creada correctamente."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICAR QUE EXISTA
    |--------------------------------------------------------------------------
    */

    $stmtExiste =
        $conexion->prepare("
            SELECT id
            FROM categorias
            WHERE id = :id
            LIMIT 1
        ");

    $stmtExiste->execute([
        ":id" => $id
    ]);

    if (!$stmtExiste->fetch()) {

        redirigir(
            "error",
            "La categoría que intentas editar no existe."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDITAR
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE categorias
        SET
            nombre = :nombre,
            descripcion = :descripcion
        WHERE id = :id
    ";

    $stmt =
        $conexion->prepare($sql);

    $stmt->execute([
        ":nombre" => $nombre,
        ":descripcion" =>
            $descripcion !== ""
                ? $descripcion
                : null,
        ":id" => $id
    ]);

    redirigir(
        "mensaje",
        "Categoría actualizada correctamente."
    );

} catch (PDOException $e) {

    redirigir(
        "error",
        "No se pudo guardar la categoría."
    );
}
