<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: productos.php?error=" . urlencode("Producto no válido."));
    exit;
}

try {

    // Verificamos que el producto exista
    $stmt = $conexion->prepare("
        SELECT id, nombre, estado
        FROM productos
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([$id]);

    $producto = $stmt->fetch();

    if (!$producto) {
        header("Location: productos.php?error=" . urlencode("El producto no existe."));
        exit;
    }

    // Si ya está inactivo
    if ((int)$producto["estado"] === 0) {
        header("Location: productos.php?mensaje=" . urlencode("El producto ya estaba inactivo."));
        exit;
    }

    // Desactivación lógica
    $stmt = $conexion->prepare("
        UPDATE productos
        SET estado = 0
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header(
        "Location: productos.php?mensaje=" .
        urlencode(
            'El producto "' .
            $producto["nombre"] .
            '" fue desactivado correctamente.'
        )
    );

    exit;

} catch (PDOException $e) {

    error_log(
        "Error al desactivar producto ID {$id}: " .
        $e->getMessage()
    );

    header(
        "Location: productos.php?error=" .
        urlencode("No fue posible desactivar el producto.")
    );

    exit;
}