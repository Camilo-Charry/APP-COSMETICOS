```php
<?php

session_start();

require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| PROTEGER EL PANEL
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATOS DEL ADMINISTRADOR
|--------------------------------------------------------------------------
*/

$adminNombre = $_SESSION["admin_nombre"] ?? "Administrador";

/*
|--------------------------------------------------------------------------
| ESTADÍSTICAS
|--------------------------------------------------------------------------
*/

try {

    // Total de productos
    $stmt = $conexion->query("
        SELECT COUNT(*) AS total
        FROM productos
    ");

    $totalProductos = (int)$stmt->fetch()["total"];


    // Productos activos
    $stmt = $conexion->query("
        SELECT COUNT(*) AS total
        FROM productos
        WHERE estado = 1
    ");

    $productosActivos = (int)$stmt->fetch()["total"];


    // Productos agotados
    $stmt = $conexion->query("
        SELECT COUNT(*) AS total
        FROM productos
        WHERE stock <= 0
        AND estado = 1
    ");

    $productosAgotados = (int)$stmt->fetch()["total"];


    // Total pedidos
    $stmt = $conexion->query("
        SELECT COUNT(*) AS total
        FROM pedidos
    ");

    $totalPedidos = (int)$stmt->fetch()["total"];


    // Pedidos pendientes
    $stmt = $conexion->query("
        SELECT COUNT(*) AS total
        FROM pedidos
        WHERE estado = 'PENDIENTE'
    ");

    $pedidosPendientes = (int)$stmt->fetch()["total"];


    // Ventas totales
    $stmt = $conexion->query("
        SELECT COALESCE(SUM(total), 0) AS total
        FROM pedidos
        WHERE estado != 'CANCELADO'
    ");

    $ventasTotales = (float)$stmt->fetch()["total"];


    // Pedidos recientes
    $stmt = $conexion->query("
        SELECT
            id,
            numero_pedido,
            nombre_cliente,
            total,
            estado,
            created_at
        FROM pedidos
        ORDER BY id DESC
        LIMIT 6
    ");

    $pedidosRecientes = $stmt->fetchAll();


} catch (PDOException $e) {

    die(
        "Error al cargar el panel administrativo: "
        . htmlspecialchars($e->getMessage())
    );

}


/*
|--------------------------------------------------------------------------
| FORMATO DE PRECIO
|--------------------------------------------------------------------------
*/

function formatoPrecio($valor)
{
    return "$" . number_format(
        (float)$valor,
        0,
        ",",
        "."
    ) . " COP";
}


/*
|--------------------------------------------------------------------------
| ESTADO DEL PEDIDO
|--------------------------------------------------------------------------
*/

function claseEstado($estado)
{
    return match ($estado) {

        "PENDIENTE" => "estado-pendiente",

        "CONFIRMADO" => "estado-confirmado",

        "PREPARANDO" => "estado-preparando",

        "ENVIADO" => "estado-enviado",

        "ENTREGADO" => "estado-entregado",

        "CANCELADO" => "estado-cancelado",

        default => "estado-pendiente"

    };
}

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
        Dashboard | Cosméticos
    </title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #fafafa;

            color: #18181b;

        }

        a {
            text-decoration: none;
        }


        /*
        |--------------------------------------------------------------------------
        | LAYOUT
        |--------------------------------------------------------------------------
        */

        .admin-layout {

            min-height: 100vh;

            display: flex;

        }


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR
        |--------------------------------------------------------------------------
        */

        .sidebar {

            width: 260px;

            min-height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #18181b 0%,
                    #27272a 100%
                );

            color: white;

            padding: 26px 18px;

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            z-index: 100;

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 0 10px;

            margin-bottom: 40px;

        }


        .brand-icon {

            width: 46px;
            height: 46px;

            border-radius: 14px;

            display: flex;

            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #ec4899,
                    #a855f7
                );

            font-size: 23px;

            box-shadow:
                0 8px 25px
                rgba(236, 72, 153, .25);

        }


        .brand-text strong {

            display: block;

            font-size: 17px;

        }


        .brand-text span {

            display: block;

            color: #a1a1aa;

            font-size: 11px;

            margin-top: 3px;

        }


        .menu-title {

            color: #71717a;

            font-size: 10px;

            font-weight: 700;

            letter-spacing: 1.5px;

            padding: 0 12px;

            margin-bottom: 10px;

        }


        .menu {

            display: flex;

            flex-direction: column;

            gap: 6px;

        }


        .menu a {

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 13px 14px;

            border-radius: 12px;

            color: #d4d4d8;

            font-size: 14px;

            transition: .2s;

        }


        .menu a:hover,
        .menu a.active {

            background:
                rgba(236, 72, 153, .14);

            color: white;

        }


        .menu-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;

        }


        .sidebar-bottom {

            position: absolute;

            left: 18px;
            right: 18px;
            bottom: 22px;

        }


        .admin-box {

            background:
                rgba(255,255,255,.06);

            border:
                1px solid
                rgba(255,255,255,.08);

            border-radius: 14px;

            padding: 13px;

            margin-bottom: 10px;

        }


        .admin-box small {

            display: block;

            color: #71717a;

            font-size: 10px;

            margin-bottom: 4px;

        }


        .admin-box strong {

            font-size: 13px;

        }


        .logout {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            padding: 12px;

            border-radius: 11px;

            background:
                rgba(239, 68, 68, .10);

            color: #fca5a5;

            font-size: 13px;

            transition: .2s;

        }


        .logout:hover {

            background:
                rgba(239, 68, 68, .20);

            color: white;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .main {

            margin-left: 260px;

            width: calc(100% - 260px);

            min-height: 100vh;

            padding: 30px 34px;

        }


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .topbar {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 30px;

        }


        .topbar h1 {

            font-size: 28px;

            margin-bottom: 6px;

        }


        .topbar p {

            color: #71717a;

            font-size: 14px;

        }


        .view-store {

            display: inline-flex;

            align-items: center;

            gap: 8px;

            padding: 11px 16px;

            border-radius: 11px;

            background: white;

            border: 1px solid #e4e4e7;

            color: #3f3f46;

            font-size: 13px;

            font-weight: 700;

            transition: .2s;

        }


        .view-store:hover {

            border-color: #ec4899;

            color: #db2777;

        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .stats-grid {

            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 24px;

        }


        .stat-card {

            background: white;

            border: 1px solid #e4e4e7;

            border-radius: 18px;

            padding: 22px;

            transition: .2s;

        }


        .stat-card:hover {

            transform: translateY(-2px);

            box-shadow:
                0 12px 30px
                rgba(0,0,0,.06);

        }


        .stat-top {

            display: flex;

            align-items: center;

            justify-content: space-between;

            margin-bottom: 18px;

        }


        .stat-icon {

            width: 42px;
            height: 42px;

            border-radius: 12px;

            display: flex;

            align-items: center;
            justify-content: center;

            font-size: 20px;

            background: #fdf2f8;

        }


        .stat-label {

            color: #71717a;

            font-size: 12px;

            font-weight: 600;

        }


        .stat-value {

            font-size: 25px;

            font-weight: 800;

            color: #18181b;

        }


        .stat-description {

            color: #a1a1aa;

            font-size: 11px;

            margin-top: 5px;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENT
        |--------------------------------------------------------------------------
        */

        .content-grid {

            display: grid;

            grid-template-columns:
                minmax(0, 1.7fr)
                minmax(280px, 1fr);

            gap: 22px;

        }


        .panel {

            background: white;

            border: 1px solid #e4e4e7;

            border-radius: 18px;

            overflow: hidden;

        }


        .panel-header {

            padding: 21px 22px;

            border-bottom:
                1px solid #f0f0f1;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .panel-header h2 {

            font-size: 16px;

        }


        .panel-header span {

            color: #a1a1aa;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .table-wrapper {

            overflow-x: auto;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        th {

            text-align: left;

            padding: 13px 20px;

            background: #fafafa;

            color: #71717a;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: .5px;

        }


        td {

            padding: 15px 20px;

            border-top:
                1px solid #f4f4f5;

            font-size: 13px;

        }


        .order-number {

            color: #18181b;

            font-weight: 700;

        }


        .customer {

            color: #52525b;

        }


        .price {

            font-weight: 700;

        }


        .estado {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;

        }


        .estado-pendiente {

            background: #fef3c7;
            color: #92400e;

        }


        .estado-confirmado {

            background: #dbeafe;
            color: #1d4ed8;

        }


        .estado-preparando {

            background: #ede9fe;
            color: #6d28d9;

        }


        .estado-enviado {

            background: #cffafe;
            color: #0e7490;

        }


        .estado-entregado {

            background: #dcfce7;
            color: #166534;

        }


        .estado-cancelado {

            background: #fee2e2;
            color: #b91c1c;

        }


        .empty {

            text-align: center;

            padding: 45px 20px;

            color: #a1a1aa;

            font-size: 13px;

        }


        /*
        |--------------------------------------------------------------------------
        | QUICK ACTIONS
        |--------------------------------------------------------------------------
        */

        .quick-actions {

            padding: 20px;

            display: grid;

            gap: 10px;

        }


        .quick-action {

            display: flex;

            align-items: center;

            gap: 13px;

            padding: 14px;

            border:
                1px solid #f0f0f1;

            border-radius: 13px;

            color: #27272a;

            transition: .2s;

        }


        .quick-action:hover {

            border-color: #f9a8d4;

            background: #fdf2f8;

            transform: translateX(2px);

        }


        .quick-action-icon {

            width: 38px;
            height: 38px;

            border-radius: 10px;

            display: flex;

            align-items: center;
            justify-content: center;

            background: #fdf2f8;

            font-size: 17px;

        }


        .quick-action strong {

            display: block;

            font-size: 12px;

            margin-bottom: 3px;

        }


        .quick-action span {

            color: #a1a1aa;

            font-size: 10px;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERTA STOCK
        |--------------------------------------------------------------------------
        */

        .stock-alert {

            margin:
                0 20px 20px;

            padding: 15px;

            border-radius: 13px;

            background: #fff7ed;

            border: 1px solid #fed7aa;

        }


        .stock-alert strong {

            display: block;

            color: #9a3412;

            font-size: 12px;

            margin-bottom: 4px;

        }


        .stock-alert span {

            color: #c2410c;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1100px) {

            .stats-grid {

                grid-template-columns:
                    repeat(2, 1fr);

            }

            .content-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 760px) {

            .sidebar {

                width: 76px;

                padding: 20px 10px;

            }

            .brand {

                justify-content: center;

                padding: 0;

            }

            .brand-text,
            .menu-title,
            .menu a span:not(.menu-icon),
            .admin-box {

                display: none;

            }

            .menu a {

                justify-content: center;

                padding: 13px;

            }

            .sidebar-bottom {

                left: 10px;
                right: 10px;

            }

            .logout {

                font-size: 0;

            }

            .logout:first-letter {

                font-size: 16px;

            }

            .main {

                margin-left: 76px;

                width:
                    calc(100% - 76px);

                padding: 22px 16px;

            }

            .topbar {

                align-items: flex-start;

                gap: 15px;

            }

            .topbar h1 {

                font-size: 22px;

            }

            .view-store {

                font-size: 0;

                padding: 11px;

            }

            .view-store:first-letter {

                font-size: 16px;

            }

        }


        @media (max-width: 520px) {

            .stats-grid {

                grid-template-columns: 1fr;

            }

            .topbar {

                flex-direction: column;

            }

            .view-store {

                width: 100%;

                justify-content: center;

                font-size: 12px;

            }

            .view-store:first-letter {

                font-size: inherit;

            }

            .main {

                padding: 18px 12px;

            }

            .panel-header {

                padding: 17px;

            }

            th,
            td {

                padding:
                    12px 14px;

            }

        }

    </style>

</head>


<body>


<div class="admin-layout">


    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">


        <div class="brand">

            <div class="brand-icon">
                💄
            </div>

            <div class="brand-text">

                <strong>
                    Cosméticos
                </strong>

                <span>
                    PANEL ADMIN
                </span>

            </div>

        </div>


        <div class="menu-title">
            ADMINISTRACIÓN
        </div>


        <nav class="menu">

            <a
                href="index.php"
                class="active"
            >

                <span class="menu-icon">
                    📊
                </span>

                <span>
                    Dashboard
                </span>

            </a>


            <a href="productos.php">

                <span class="menu-icon">
                    💄
                </span>

                <span>
                    Productos
                </span>

            </a>


            <a href="pedidos.php">

                <span class="menu-icon">
                    📦
                </span>

                <span>
                    Pedidos
                </span>

            </a>


            <a href="#">

                <span class="menu-icon">
                    📈
                </span>

                <span>
                    Estadísticas
                </span>

            </a>

        </nav>


        <div class="sidebar-bottom">


            <div class="admin-box">

                <small>
                    SESIÓN ACTUAL
                </small>

                <strong>
                    <?= htmlspecialchars($adminNombre) ?>
                </strong>

            </div>


            <a
                href="logout.php"
                class="logout"
            >

                🚪
                <span>
                    Cerrar sesión
                </span>

            </a>


        </div>


    </aside>


    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================== -->

    <main class="main">


        <!-- HEADER -->

        <header class="topbar">

            <div>

                <h1>
                    Dashboard 👋
                </h1>

                <p>
                    Aquí tienes un resumen de tu tienda.
                </p>

            </div>


            <a
                href="../index.php"
                target="_blank"
                class="view-store"
            >

                🛍️
                Ver tienda

            </a>

        </header>


        <!-- =====================================================
             ESTADÍSTICAS
        ====================================================== -->

        <section class="stats-grid">


            <!-- PRODUCTOS -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-label">
                        PRODUCTOS
                    </div>

                    <div class="stat-icon">
                        💄
                    </div>

                </div>

                <div class="stat-value">
                    <?= $totalProductos ?>
                </div>

                <div class="stat-description">
                    <?= $productosActivos ?>
                    productos activos
                </div>

            </div>


            <!-- AGOTADOS -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-label">
                        AGOTADOS
                    </div>

                    <div class="stat-icon">
                        ⚠️
                    </div>

                </div>

                <div class="stat-value">
                    <?= $productosAgotados ?>
                </div>

                <div class="stat-description">
                    Productos sin stock
                </div>

            </div>


            <!-- PEDIDOS -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-label">
                        PEDIDOS
                    </div>

                    <div class="stat-icon">
                        📦
                    </div>

                </div>

                <div class="stat-value">
                    <?= $totalPedidos ?>
                </div>

                <div class="stat-description">
                    <?= $pedidosPendientes ?>
                    pendientes
                </div>

            </div>


            <!-- VENTAS -->

            <div class="stat-card">

                <div class="stat-top">

                    <div class="stat-label">
                        VENTAS
                    </div>

                    <div class="stat-icon">
                        💰
                    </div>

                </div>

                <div class="stat-value">

                    <?= formatoPrecio($ventasTotales) ?>

                </div>

                <div class="stat-description">
                    Ventas no canceladas
                </div>

            </div>


        </section>


        <!-- =====================================================
             CONTENIDO
        ====================================================== -->

        <section class="content-grid">


            <!-- =================================================
                 PEDIDOS RECIENTES
            ================================================== -->

            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Pedidos recientes
                    </h2>

                    <span>
                        Últimos 6 pedidos
                    </span>

                </div>


                <?php if (count($pedidosRecientes) > 0): ?>


                    <div class="table-wrapper">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Pedido
                                    </th>

                                    <th>
                                        Cliente
                                    </th>

                                    <th>
                                        Total
                                    </th>

                                    <th>
                                        Estado
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php foreach ($pedidosRecientes as $pedido): ?>


                                <tr>

                                    <td>

                                        <div class="order-number">

                                            <?= htmlspecialchars(
                                                $pedido["numero_pedido"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <div class="customer">

                                            <?= htmlspecialchars(
                                                $pedido["nombre_cliente"]
                                            ) ?>

                                        </div>

                                    </td>


                                    <td>

                                        <span class="price">

                                            <?= formatoPrecio(
                                                $pedido["total"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span
                                            class="estado <?= claseEstado(
                                                $pedido["estado"]
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $pedido["estado"]
                                            ) ?>

                                        </span>

                                    </td>

                                </tr>


                            <?php endforeach; ?>


                            </tbody>

                        </table>

                    </div>


                <?php else: ?>


                    <div class="empty">

                        📦

                        <br><br>

                        Todavía no hay pedidos registrados.

                    </div>


                <?php endif; ?>


            </div>


            <!-- =================================================
                 ACCIONES RÁPIDAS
            ================================================== -->

            <div class="panel">


                <div class="panel-header">

                    <h2>
                        Acciones rápidas
                    </h2>

                </div>


                <div class="quick-actions">


                    <a
                        href="productos.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            ➕
                        </div>

                        <div>

                            <strong>
                                Agregar producto
                            </strong>

                            <span>
                                Añade un nuevo producto
                            </span>

                        </div>

                    </a>


                    <a
                        href="productos.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            🛍️
                        </div>

                        <div>

                            <strong>
                                Administrar productos
                            </strong>

                            <span>
                                Edita precios, stock e imágenes
                            </span>

                        </div>

                    </a>


                    <a
                        href="pedidos.php"
                        class="quick-action"
                    >

                        <div class="quick-action-icon">
                            📦
                        </div>

                        <div>

                            <strong>
                                Ver pedidos
                            </strong>

                            <span>
                                Gestiona las compras realizadas
                            </span>

                        </div>

                    </a>


                </div>


                <?php if ($productosAgotados > 0): ?>


                    <div class="stock-alert">

                        <strong>
                            ⚠️ Atención con el inventario
                        </strong>

                        <span>

                            Tienes
                            <?= $productosAgotados ?>
                            producto(s) agotado(s).

                        </span>

                    </div>


                <?php endif; ?>


            </div>


        </section>


    </main>


</div>


</body>

</html>
```
