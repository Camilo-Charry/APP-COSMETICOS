<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
    die("Pedido no válido.");
}

try {

    // ==============================
    // DATOS DEL PEDIDO
    // ==============================

    $stmt = $conexion->prepare("
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
            created_at
        FROM pedidos
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $id
    ]);

    $pedido = $stmt->fetch();

    if (!$pedido) {
        die("El pedido no existe.");
    }

    // ==============================
    // PRODUCTOS
    // ==============================

    $stmtProductos = $conexion->prepare("
        SELECT
            nombre_producto,
            precio_unitario,
            cantidad,
            subtotal
        FROM detalle_pedidos
        WHERE pedido_id = :pedido_id
        ORDER BY id ASC
    ");

    $stmtProductos->execute([
        ":pedido_id" => $id
    ]);

    $productos = $stmtProductos->fetchAll();

} catch (PDOException $e) {

    die("No fue posible cargar el comprobante.");
}


// ==============================
// FUNCIONES
// ==============================

function escapar($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        "UTF-8"
    );
}

function precio($valor)
{
    return "$" . number_format(
        (float)$valor,
        0,
        ",",
        "."
    );
}

function estadoClase($estado)
{
    return match ($estado) {
        "PENDIENTE" => "pendiente",
        "CONFIRMADO" => "confirmado",
        "PREPARANDO" => "preparando",
        "ENVIADO" => "enviado",
        "ENTREGADO" => "entregado",
        "CANCELADO" => "cancelado",
        default => "pendiente"
    };
}

function estadoTexto($estado)
{
    return match ($estado) {
        "PENDIENTE" => "Pendiente",
        "CONFIRMADO" => "Confirmado",
        "PREPARANDO" => "Preparando",
        "ENVIADO" => "Enviado",
        "ENTREGADO" => "Entregado",
        "CANCELADO" => "Cancelado",
        default => $estado
    };
}

$fecha = date(
    "d/m/Y H:i",
    strtotime($pedido["created_at"])
);

?>
<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Comprobante <?= escapar($pedido["numero_pedido"]) ?>
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #f3f4f6;
            font-family:
                Arial,
                Helvetica,
                sans-serif;
            color: #222;
        }

        .acciones {
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        .btn {
            border: 0;
            padding: 12px 18px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
        }

        .btn-volver {
            background: #e5e7eb;
            color: #222;
        }

        .btn-imprimir {
            background: #111827;
            color: white;
        }

        .comprobante {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 45px;
            border-radius: 16px;
            box-shadow:
                0 10px 35px rgba(0, 0, 0, .08);
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 30px;
            padding-bottom: 25px;
            border-bottom: 2px solid #eee;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 65px;
            height: 65px;
            object-fit: contain;
            border-radius: 12px;
        }

        .marca h1 {
            margin: 0;
            font-size: 25px;
        }

        .marca p {
            margin: 5px 0 0;
            color: #777;
            font-size: 13px;
        }

        .pedido-info {
            text-align: right;
        }

        .pedido-info small {
            display: block;
            color: #777;
            margin-bottom: 5px;
        }

        .pedido-numero {
            font-size: 20px;
            font-weight: 800;
        }

        .estado {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado.pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .estado.confirmado {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .estado.preparando {
            background: #ede9fe;
            color: #6d28d9;
        }

        .estado.enviado {
            background: #cffafe;
            color: #0e7490;
        }

        .estado.entregado {
            background: #dcfce7;
            color: #15803d;
        }

        .estado.cancelado {
            background: #fee2e2;
            color: #b91c1c;
        }

        .seccion {
            margin-top: 30px;
        }

        .seccion-titulo {
            margin-bottom: 14px;
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .cliente-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 30px;
        }

        .dato {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .dato strong {
            display: block;
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .dato span {
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            background: #f8f8f8;
            padding: 12px;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
        }

        td {
            padding: 13px 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .texto-derecha {
            text-align: right;
        }

        .texto-centro {
            text-align: center;
        }

        .total-box {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }

        .total {
            width: 280px;
            border-top: 2px solid #222;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total span {
            font-size: 15px;
            font-weight: 700;
        }

        .total strong {
            font-size: 24px;
        }

        .observaciones {
            background: #fafafa;
            border-radius: 10px;
            padding: 15px;
            font-size: 13px;
            line-height: 1.5;
        }

        .pie {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #888;
            font-size: 12px;
            line-height: 1.6;
        }

        @media (max-width: 650px) {

            body {
                padding: 12px;
            }

            .comprobante {
                padding: 22px;
                border-radius: 10px;
            }

            .encabezado {
                flex-direction: column;
            }

            .pedido-info {
                text-align: left;
            }

            .cliente-grid {
                grid-template-columns: 1fr;
            }

            table {
                font-size: 12px;
            }

            th,
            td {
                padding: 9px 6px;
            }

            .acciones {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }

        @media print {

            body {
                padding: 0;
                background: white;
            }

            .acciones {
                display: none;
            }

            .comprobante {
                max-width: none;
                width: 100%;
                padding: 20px;
                box-shadow: none;
                border-radius: 0;
            }

        }

    </style>

</head>

<body>

    <div class="acciones">

        <button
            class="btn btn-volver"
            onclick="history.back()"
        >
            ← Volver
        </button>

        <button
            class="btn btn-imprimir"
            onclick="window.print()"
        >
            🖨️ Imprimir / Guardar PDF
        </button>

    </div>


    <div class="comprobante">

        <!-- ENCABEZADO -->

        <div class="encabezado">

            <div class="marca">

                <img
                    src="../assets/img/logo.png"
                    class="logo"
                    alt="Logo"
                    onerror="this.style.display='none'"
                >

                <div>

                    <h1>
                        Bellae
                    </h1>

                    <p>
                        Comprobante de pedido
                    </p>

                </div>

            </div>


            <div class="pedido-info">

                <small>
                    Pedido
                </small>

                <div class="pedido-numero">
                    <?= escapar($pedido["numero_pedido"]) ?>
                </div>

                <small>
                    <?= escapar($fecha) ?>
                </small>

                <span
                    class="estado <?= estadoClase($pedido["estado"]) ?>"
                >
                    <?= escapar(estadoTexto($pedido["estado"])) ?>
                </span>

            </div>

        </div>


        <!-- CLIENTE -->

        <div class="seccion">

            <div class="seccion-titulo">
                Datos del cliente
            </div>

            <div class="cliente-grid">

                <div class="dato">

                    <strong>
                        Nombre
                    </strong>

                    <span>
                        <?= escapar($pedido["nombre_cliente"]) ?>
                    </span>

                </div>


                <div class="dato">

                    <strong>
                        Teléfono
                    </strong>

                    <span>
                        <?= escapar($pedido["telefono"]) ?>
                    </span>

                </div>


                <?php if (!empty($pedido["email"])): ?>

                    <div class="dato">

                        <strong>
                            Correo
                        </strong>

                        <span>
                            <?= escapar($pedido["email"]) ?>
                        </span>

                    </div>

                <?php endif; ?>


                <div class="dato">

                    <strong>
                        Ciudad
                    </strong>

                    <span>
                        <?= escapar($pedido["ciudad"]) ?>
                    </span>

                </div>


                <div class="dato">

                    <strong>
                        Dirección
                    </strong>

                    <span>
                        <?= escapar($pedido["direccion"]) ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- PRODUCTOS -->

        <div class="seccion">

            <div class="seccion-titulo">
                Detalle del pedido
            </div>

            <table>

                <thead>

                    <tr>

                        <th>
                            Producto
                        </th>

                        <th class="texto-centro">
                            Cant.
                        </th>

                        <th class="texto-derecha">
                            Precio
                        </th>

                        <th class="texto-derecha">
                            Subtotal
                        </th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($productos as $producto): ?>

                        <tr>

                            <td>
                                <?= escapar($producto["nombre_producto"]) ?>
                            </td>

                            <td class="texto-centro">
                                <?= (int)$producto["cantidad"] ?>
                            </td>

                            <td class="texto-derecha">
                                <?= precio($producto["precio_unitario"]) ?>
                            </td>

                            <td class="texto-derecha">
                                <?= precio($producto["subtotal"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>


            <div class="total-box">

                <div class="total">

                    <span>
                        TOTAL
                    </span>

                    <strong>
                        <?= precio($pedido["total"]) ?>
                    </strong>

                </div>

            </div>

        </div>


        <!-- OBSERVACIONES -->

        <?php if (!empty($pedido["observaciones"])): ?>

            <div class="seccion">

                <div class="seccion-titulo">
                    Observaciones
                </div>

                <div class="observaciones">

                    <?= nl2br(
                        escapar($pedido["observaciones"])
                    ) ?>

                </div>

            </div>

        <?php endif; ?>


        <!-- PIE -->

        <div class="pie">

            Gracias por tu compra 💄✨

            <br>

            Este documento corresponde al pedido
            <?= escapar($pedido["numero_pedido"]) ?>.

        </div>

    </div>


</body>

</html>