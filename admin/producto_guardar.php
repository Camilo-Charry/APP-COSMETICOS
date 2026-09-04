
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
        "Location: productos.php?" .
        $tipo .
        "=" .
        rawurlencode($mensaje)
    );
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    redirigir("error", "Método de solicitud no permitido.");
}

$id = isset($_POST["id"]) ? (int) $_POST["id"] : 0;

$nombre = trim($_POST["nombre"] ?? "");
$categoriaId = isset($_POST["categoria_id"])
    ? (int) $_POST["categoria_id"]
    : 0;

$precio = isset($_POST["precio"])
    ? (float) $_POST["precio"]
    : 0;

$stock = isset($_POST["stock"])
    ? (int) $_POST["stock"]
    : 0;

$estado = isset($_POST["estado"])
    ? (int) $_POST["estado"]
    : 1;

$descripcion = trim($_POST["descripcion"] ?? "");

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if ($nombre === "") {
    redirigir("error", "El nombre del producto es obligatorio.");
}

if (mb_strlen($nombre) > 150) {
    redirigir("error", "El nombre del producto es demasiado largo.");
}

if ($categoriaId <= 0) {
    redirigir("error", "Debes seleccionar una categoría.");
}

if ($precio < 0) {
    redirigir("error", "El precio no puede ser negativo.");
}

if ($stock < 0) {
    redirigir("error", "El stock no puede ser negativo.");
}

if ($estado !== 0 && $estado !== 1) {
    redirigir("error", "El estado seleccionado no es válido.");
}

/*
|--------------------------------------------------------------------------
| VERIFICAR CATEGORÍA
|--------------------------------------------------------------------------
*/

try {

    $stmtCategoria = $conexion->prepare("
        SELECT id
        FROM categorias
        WHERE id = :id
        AND estado = 1
        LIMIT 1
    ");

    $stmtCategoria->execute([
        ":id" => $categoriaId
    ]);

    if (!$stmtCategoria->fetch()) {
        redirigir(
            "error",
            "La categoría seleccionada no existe o está inactiva."
        );
    }

} catch (PDOException $e) {

    redirigir(
        "error",
        "No se pudo verificar la categoría."
    );
}

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN DE IMÁGENES
|--------------------------------------------------------------------------
*/

$directorioImagenes = dirname(__DIR__) . DIRECTORY_SEPARATOR
    . "assets"
    . DIRECTORY_SEPARATOR
    . "img"
    . DIRECTORY_SEPARATOR
    . "productos"
    . DIRECTORY_SEPARATOR;

$imagenNueva = null;

$hayImagen = isset($_FILES["imagen"])
    && $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE;

if ($hayImagen) {

    if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
        redirigir(
            "error",
            "Ocurrió un error al subir la imagen."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Tamaño máximo: 5 MB
    |--------------------------------------------------------------------------
    */

    $maximo = 5 * 1024 * 1024;

    if ($_FILES["imagen"]["size"] > $maximo) {
        redirigir(
            "error",
            "La imagen no puede superar los 5 MB."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Validar MIME real
    |--------------------------------------------------------------------------
    */

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file($_FILES["imagen"]["tmp_name"]);

    $tiposPermitidos = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    if (!isset($tiposPermitidos[$mime])) {
        redirigir(
            "error",
            "Formato de imagen no permitido. Usa JPG, PNG o WEBP."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Crear carpeta si no existe
    |--------------------------------------------------------------------------
    */

    if (!is_dir($directorioImagenes)) {

        if (!mkdir($directorioImagenes, 0755, true)) {
            redirigir(
                "error",
                "No se pudo crear la carpeta de imágenes."
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Generar nombre único
    |--------------------------------------------------------------------------
    */

    try {

        $nombreUnico =
            bin2hex(random_bytes(16))
            . "."
            . $tiposPermitidos[$mime];

    } catch (Exception $e) {

        $nombreUnico =
            uniqid("producto_", true)
            . "."
            . $tiposPermitidos[$mime];
    }

    $rutaDestino = $directorioImagenes . $nombreUnico;

    /*
    |--------------------------------------------------------------------------
    | Mover imagen
    |--------------------------------------------------------------------------
    */

    if (!move_uploaded_file(
        $_FILES["imagen"]["tmp_name"],
        $rutaDestino
    )) {

        redirigir(
            "error",
            "No se pudo guardar la imagen del producto."
        );
    }

    $imagenNueva = $nombreUnico;
}

/*
|--------------------------------------------------------------------------
| CREAR PRODUCTO
|--------------------------------------------------------------------------
*/

if ($id <= 0) {

    try {

        $sql = "
            INSERT INTO productos (
                categoria_id,
                nombre,
                descripcion,
                precio,
                stock,
                imagen,
                estado
            )
            VALUES (
                :categoria_id,
                :nombre,
                :descripcion,
                :precio,
                :stock,
                :imagen,
                :estado
            )
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":categoria_id" => $categoriaId,
            ":nombre" => $nombre,
            ":descripcion" => $descripcion !== ""
                ? $descripcion
                : null,
            ":precio" => $precio,
            ":stock" => $stock,
            ":imagen" => $imagenNueva,
            ":estado" => $estado
        ]);

        redirigir(
            "mensaje",
            "Producto creado correctamente."
        );

    } catch (PDOException $e) {

        /*
        |--------------------------------------------------------------------------
        | Si la BD falla, eliminar la imagen recién subida
        |--------------------------------------------------------------------------
        */

        if (
            $imagenNueva !== null
            && file_exists($directorioImagenes . $imagenNueva)
        ) {
            unlink($directorioImagenes . $imagenNueva);
        }

        redirigir(
            "error",
            "No se pudo crear el producto."
        );
    }
}

/*
|--------------------------------------------------------------------------
| EDITAR PRODUCTO
|--------------------------------------------------------------------------
*/

try {

    $stmtProducto = $conexion->prepare("
        SELECT *
        FROM productos
        WHERE id = :id
        LIMIT 1
    ");

    $stmtProducto->execute([
        ":id" => $id
    ]);

    $productoActual = $stmtProducto->fetch();

    if (!$productoActual) {

        if (
            $imagenNueva !== null
            && file_exists($directorioImagenes . $imagenNueva)
        ) {
            unlink($directorioImagenes . $imagenNueva);
        }

        redirigir(
            "error",
            "El producto que intentas editar no existe."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Si no se subió imagen nueva, conservar la anterior
    |--------------------------------------------------------------------------
    */

    $imagenFinal = $productoActual["imagen"];

    if ($imagenNueva !== null) {
        $imagenFinal = $imagenNueva;
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar producto
    |--------------------------------------------------------------------------
    */

    $sql = "
        UPDATE productos
        SET
            categoria_id = :categoria_id,
            nombre = :nombre,
            descripcion = :descripcion,
            precio = :precio,
            stock = :stock,
            imagen = :imagen,
            estado = :estado
        WHERE id = :id
    ";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        ":categoria_id" => $categoriaId,
        ":nombre" => $nombre,
        ":descripcion" => $descripcion !== ""
            ? $descripcion
            : null,
        ":precio" => $precio,
        ":stock" => $stock,
        ":imagen" => $imagenFinal,
        ":estado" => $estado,
        ":id" => $id
    ]);

    /*
    |--------------------------------------------------------------------------
    | Eliminar imagen anterior si se reemplazó
    |--------------------------------------------------------------------------
    |
    | Solo eliminamos archivos que estén dentro de la carpeta
    | de productos y que tengan nombre diferente a la nueva imagen.
    |
    */

    if (
        $imagenNueva !== null
        && !empty($productoActual["imagen"])
        && $productoActual["imagen"] !== $imagenNueva
    ) {

        $imagenAnterior = basename(
            $productoActual["imagen"]
        );

        $rutaImagenAnterior =
            $directorioImagenes . $imagenAnterior;

        if (
            file_exists($rutaImagenAnterior)
            && is_file($rutaImagenAnterior)
        ) {
            unlink($rutaImagenAnterior);
        }
    }

    redirigir(
        "mensaje",
        "Producto actualizado correctamente."
    );

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | Si ocurrió un error al editar y había imagen nueva,
    | eliminar la imagen nueva para no dejar basura.
    |--------------------------------------------------------------------------
    */

    if (
        $imagenNueva !== null
        && file_exists($directorioImagenes . $imagenNueva)
    ) {
        unlink($directorioImagenes . $imagenNueva);
    }

    redirigir(
        "error",
        "No se pudo actualizar el producto."
    );
}

