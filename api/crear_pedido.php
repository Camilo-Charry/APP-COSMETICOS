<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";


/* =========================================================
   RESPUESTA JSON
========================================================= */

function responder($success, $mensaje, $datos = [])
{
    echo json_encode([
        "success" => $success,
        "mensaje" => $mensaje,
        "datos" => $datos
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/* =========================================================
   SOLO POST
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    responder(
        false,
        "Método de solicitud no permitido."
    );

}


/* =========================================================
   RECIBIR JSON
========================================================= */

$input = file_get_contents("php://input");

$data = json_decode($input, true);


if (!is_array($data)) {

    responder(
        false,
        "Los datos enviados no son válidos."
    );

}


/* =========================================================
   DATOS DEL CLIENTE
========================================================= */

$nombre = trim($data["nombre"] ?? "");
$telefono = trim($data["telefono"] ?? "");
$email = trim($data["email"] ?? "");
$ciudad = trim($data["ciudad"] ?? "");
$direccion = trim($data["direccion"] ?? "");
$observaciones = trim($data["observaciones"] ?? "");

$carrito = $data["carrito"] ?? [];


/* =========================================================
   VALIDACIONES
========================================================= */

if ($nombre === "") {

    responder(
        false,
        "El nombre es obligatorio."
    );

}


if ($telefono === "") {

    responder(
        false,
        "El teléfono es obligatorio."
    );

}


if ($ciudad === "") {

    responder(
        false,
        "La ciudad es obligatoria."
    );

}


if ($direccion === "") {

    responder(
        false,
        "La dirección es obligatoria."
    );

}


if (!is_array($carrito) || count($carrito) === 0) {

    responder(
        false,
        "El carrito está vacío."
    );

}


if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {

    responder(
        false,
        "El correo electrónico no es válido."
    );

}


/* =========================================================
   PROCESAR PEDIDO
========================================================= */

try {

    /*
     * Iniciamos una transacción.
     *
     * Si algo falla, NO se guarda nada.
     */

    $conexion->beginTransaction();


    $productosPedido = [];

    $total = 0;


    /* =====================================================
       VALIDAR PRODUCTOS CONTRA MYSQL
    ===================================================== */

    foreach ($carrito as $item) {

        $productoId = (int)($item["id"] ?? 0);
        $cantidad = (int)($item["cantidad"] ?? 0);


        if ($productoId <= 0 || $cantidad <= 0) {

            throw new Exception(
                "Uno de los productos del carrito no es válido."
            );

        }


        /*
         * IMPORTANTE:
         * No utilizamos el precio enviado por JavaScript.
         *
         * Consultamos el precio real en MySQL.
         */

        $sql = "
            SELECT
                id,
                nombre,
                precio,
                stock,
                estado
            FROM productos
            WHERE id = :id
            LIMIT 1
            FOR UPDATE
        ";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            ":id" => $productoId
        ]);

        $producto = $stmt->fetch();


        if (!$producto) {

            throw new Exception(
                "Uno de los productos ya no existe."
            );

        }


        if ((int)$producto["estado"] !== 1) {

            throw new Exception(
                "El producto " .
                $producto["nombre"] .
                " ya no está disponible."
            );

        }


        if ($cantidad > (int)$producto["stock"]) {

            throw new Exception(
                "No hay suficiente stock de " .
                $producto["nombre"] .
                ". Stock disponible: " .
                $producto["stock"]
            );

        }


        $precio = (float)$producto["precio"];

        $subtotal = $precio * $cantidad;

        $total += $subtotal;


        $productosPedido[] = [

            "id" => $producto["id"],

            "nombre" => $producto["nombre"],

            "precio" => $precio,

            "cantidad" => $cantidad,

            "subtotal" => $subtotal

        ];

    }


    /* =====================================================
       GENERAR NÚMERO DE PEDIDO
    ===================================================== */

    $fechaPedido = date("Ymd");


    $stmt = $conexion->query("
        SELECT COUNT(*) + 1 AS siguiente
        FROM pedidos
        WHERE DATE(created_at) = CURDATE()
    ");


    $resultado = $stmt->fetch();


    $numeroSecuencia =
        str_pad(
            (int)$resultado["siguiente"],
            4,
            "0",
            STR_PAD_LEFT
        );


    $numeroPedido =
        "PED-" .
        $fechaPedido .
        "-" .
        $numeroSecuencia;


    /* =====================================================
       GUARDAR PEDIDO
    ===================================================== */

    $sqlPedido = "
        INSERT INTO pedidos (
            numero_pedido,
            nombre_cliente,
            telefono,
            email,
            ciudad,
            direccion,
            observaciones,
            total,
            estado
        )
        VALUES (
            :numero_pedido,
            :nombre_cliente,
            :telefono,
            :email,
            :ciudad,
            :direccion,
            :observaciones,
            :total,
            'PENDIENTE'
        )
    ";


    $stmtPedido = $conexion->prepare($sqlPedido);


    $stmtPedido->execute([

        ":numero_pedido" => $numeroPedido,

        ":nombre_cliente" => $nombre,

        ":telefono" => $telefono,

        ":email" =>
            $email !== ""
                ? $email
                : null,

        ":ciudad" => $ciudad,

        ":direccion" => $direccion,

        ":observaciones" =>
            $observaciones !== ""
                ? $observaciones
                : null,

        ":total" => $total

    ]);


    $pedidoId =
        (int)$conexion->lastInsertId();


    /* =====================================================
       GUARDAR DETALLE DEL PEDIDO
    ===================================================== */

    $sqlDetalle = "
        INSERT INTO detalle_pedidos (
            pedido_id,
            producto_id,
            nombre_producto,
            precio_unitario,
            cantidad,
            subtotal
        )
        VALUES (
            :pedido_id,
            :producto_id,
            :nombre_producto,
            :precio_unitario,
            :cantidad,
            :subtotal
        )
    ";


    $stmtDetalle =
        $conexion->prepare($sqlDetalle);


    /* =====================================================
       ACTUALIZAR STOCK
    ===================================================== */

    $sqlStock = "
        UPDATE productos
        SET stock = stock - :cantidad
        WHERE id = :id
    ";


    $stmtStock =
        $conexion->prepare($sqlStock);


    foreach ($productosPedido as $producto) {


        /* Guardar detalle */

        $stmtDetalle->execute([

            ":pedido_id" => $pedidoId,

            ":producto_id" => $producto["id"],

            ":nombre_producto" =>
                $producto["nombre"],

            ":precio_unitario" =>
                $producto["precio"],

            ":cantidad" =>
                $producto["cantidad"],

            ":subtotal" =>
                $producto["subtotal"]

        ]);


        /* Descontar stock */

        $stmtStock->execute([

            ":cantidad" =>
                $producto["cantidad"],

            ":id" =>
                $producto["id"]

        ]);

    }


    /* =====================================================
       CONFIRMAR TRANSACCIÓN
    ===================================================== */

    $conexion->commit();


    /* =====================================================
       PREPARAR RESPUESTA
    ===================================================== */

    responder(
        true,
        "Pedido creado correctamente.",
        [

            "pedido_id" => $pedidoId,

            "numero_pedido" =>
                $numeroPedido,

            "total" =>
                $total,

            "productos" =>
                $productosPedido

        ]
    );


} catch (Exception $e) {


    /* =====================================================
       CANCELAR TODO SI HUBO ERROR
    ===================================================== */

    if ($conexion->inTransaction()) {

        $conexion->rollBack();

    }


    responder(
        false,
        $e->getMessage()
    );

}