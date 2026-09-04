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


/*
|--------------------------------------------------------------------------
| SOLO POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "mensaje" => "Método de solicitud no permitido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| LEER JSON
|--------------------------------------------------------------------------
*/

$input = file_get_contents("php://input");

$data = json_decode($input, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "Los datos enviados no son válidos."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| DATOS
|--------------------------------------------------------------------------
*/

$id = filter_var(
    $data["id"] ?? 0,
    FILTER_VALIDATE_INT
);

$estado = strtoupper(
    trim(
        (string)($data["estado"] ?? "")
    )
);


/*
|--------------------------------------------------------------------------
| ESTADOS PERMITIDOS
|--------------------------------------------------------------------------
*/

$estadosPermitidos = [
    "PENDIENTE",
    "CONFIRMADO",
    "PREPARANDO",
    "ENVIADO",
    "ENTREGADO",
    "CANCELADO"
];


/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if (!$id || $id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "El pedido no es válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| VALIDAR ESTADO
|--------------------------------------------------------------------------
*/

if (!in_array($estado, $estadosPermitidos, true)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => "El estado seleccionado no es válido."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| TRANSACCIÓN
|--------------------------------------------------------------------------
*/

try {

    $conexion->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | OBTENER PEDIDO Y BLOQUEARLO
    |--------------------------------------------------------------------------
    */

    $stmtPedido = $conexion->prepare("
        SELECT
            id,
            numero_pedido,
            estado
        FROM pedidos
        WHERE id = :id
        LIMIT 1
        FOR UPDATE
    ");

    $stmtPedido->execute([
        ":id" => $id
    ]);

    $pedido = $stmtPedido->fetch();

    if (!$pedido) {

        $conexion->rollBack();

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "mensaje" => "El pedido no existe."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    $estadoAnterior = $pedido["estado"];


    /*
    |--------------------------------------------------------------------------
    | SI EL ESTADO NO CAMBIA
    |--------------------------------------------------------------------------
    */

    if ($estadoAnterior === $estado) {

        $conexion->commit();

        echo json_encode([
            "success" => true,
            "mensaje" => "El pedido ya se encuentra en ese estado.",
            "datos" => [
                "id" => (int)$pedido["id"],
                "numero_pedido" => $pedido["numero_pedido"],
                "estado_anterior" => $estadoAnterior,
                "estado_nuevo" => $estado
            ]
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR PEDIDO → DEVOLVER STOCK
    |--------------------------------------------------------------------------
    */

    if (
        $estadoAnterior !== "CANCELADO" &&
        $estado === "CANCELADO"
    ) {

        $stmtDetalles = $conexion->prepare("
            SELECT
                producto_id,
                cantidad
            FROM detalle_pedidos
            WHERE pedido_id = :pedido_id
            FOR UPDATE
        ");

        $stmtDetalles->execute([
            ":pedido_id" => $id
        ]);

        $detalles = $stmtDetalles->fetchAll();


        foreach ($detalles as $detalle) {

            $productoId = (int)$detalle["producto_id"];
            $cantidad = (int)$detalle["cantidad"];

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmtStock = $conexion->prepare("
                UPDATE productos
                SET stock = stock + :cantidad
                WHERE id = :producto_id
            ");

            $stmtStock->execute([
                ":cantidad" => $cantidad,
                ":producto_id" => $productoId
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REACTIVAR PEDIDO CANCELADO → DESCONTAR STOCK
    |--------------------------------------------------------------------------
    */

    if (
        $estadoAnterior === "CANCELADO" &&
        $estado !== "CANCELADO"
    ) {

        $stmtDetalles = $conexion->prepare("
            SELECT
                producto_id,
                cantidad,
                nombre_producto
            FROM detalle_pedidos
            WHERE pedido_id = :pedido_id
            FOR UPDATE
        ");

        $stmtDetalles->execute([
            ":pedido_id" => $id
        ]);

        $detalles = $stmtDetalles->fetchAll();


        /*
        |--------------------------------------------------------------------------
        | Primero verificamos TODO el stock
        |--------------------------------------------------------------------------
        */

        foreach ($detalles as $detalle) {

            $productoId = (int)$detalle["producto_id"];
            $cantidad = (int)$detalle["cantidad"];

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmtProducto = $conexion->prepare("
                SELECT
                    id,
                    nombre,
                    stock
                FROM productos
                WHERE id = :producto_id
                LIMIT 1
                FOR UPDATE
            ");

            $stmtProducto->execute([
                ":producto_id" => $productoId
            ]);

            $producto = $stmtProducto->fetch();

            if (!$producto) {

                throw new Exception(
                    "El producto '{$detalle["nombre_producto"]}' ya no existe."
                );
            }

            $stockActual = (int)$producto["stock"];

            if ($stockActual < $cantidad) {

                throw new Exception(
                    "No hay stock suficiente para '{$producto["nombre"]}'. " .
                    "Disponible: {$stockActual}. Necesario: {$cantidad}."
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Si todo está disponible, descontamos
        |--------------------------------------------------------------------------
        */

        foreach ($detalles as $detalle) {

            $productoId = (int)$detalle["producto_id"];
            $cantidad = (int)$detalle["cantidad"];

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmtRestarStock = $conexion->prepare("
                UPDATE productos
                SET stock = stock - :cantidad
                WHERE id = :producto_id
            ");

            $stmtRestarStock->execute([
                ":cantidad" => $cantidad,
                ":producto_id" => $productoId
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO
    |--------------------------------------------------------------------------
    */

    $stmtUpdate = $conexion->prepare("
        UPDATE pedidos
        SET
            estado = :estado,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id
        LIMIT 1
    ");

    $stmtUpdate->execute([
        ":estado" => $estado,
        ":id" => $id
    ]);


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR TRANSACCIÓN
    |--------------------------------------------------------------------------
    */

    $conexion->commit();


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA EXITOSA
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,
        "mensaje" => "El estado del pedido se actualizó correctamente.",
        "datos" => [
            "id" => (int)$pedido["id"],
            "numero_pedido" => $pedido["numero_pedido"],
            "estado_anterior" => $estadoAnterior,
            "estado_nuevo" => $estado
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log(
        "Error pedido_estado.php - Pedido ID {$id}: " .
        $e->getMessage()
    );

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "mensaje" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}