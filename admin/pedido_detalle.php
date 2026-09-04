<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION["admin_id"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "mensaje" => "No autorizado."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "ID de pedido no válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | Verificar que el pedido exista
    |--------------------------------------------------------------------------
    */

    $stmtPedido = $conexion->prepare("
        SELECT
            id,
            numero_pedido,
            nombre_cliente,
            telefono,
            email,
            ciudad,
            direccion,
            observaciones,
            total,
            estado,
            created_at,
            updated_at
        FROM pedidos
        WHERE id = :id
        LIMIT 1
    ");

    $stmtPedido->execute([
        ":id" => $id
    ]);

    $pedido = $stmtPedido->fetch();

    if (!$pedido) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "mensaje" => "El pedido no existe."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener productos del pedido
    |--------------------------------------------------------------------------
    */

    $stmtProductos = $conexion->prepare("
        SELECT
            id,
            pedido_id,
            producto_id,
            nombre_producto,
            precio_unitario,
            cantidad,
            subtotal,
            created_at
        FROM detalle_pedidos
        WHERE pedido_id = :pedido_id
        ORDER BY id ASC
    ");

    $stmtProductos->execute([
        ":pedido_id" => $id
    ]);

    $productos = $stmtProductos->fetchAll();


    /*
    |--------------------------------------------------------------------------
    | Respuesta
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "mensaje" => "Pedido cargado correctamente.",
        "pedido" => $pedido,
        "productos" => $productos
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {

    error_log(
        "Error pedido_detalle.php - Pedido ID {$id}: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "mensaje" => "Error al consultar el pedido."
    ], JSON_UNESCAPED_UNICODE);
}