<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* =========================
   FUNCIONES
========================= */

function escapar($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}

function formatoPrecio($precio)
{
    return "$" . number_format(
        (float)$precio,
        0,
        ",",
        "."
    );
}

function formatoFecha($fecha)
{
    if (!$fecha) {
        return "—";
    }

    return date(
        "d/m/Y H:i",
        strtotime($fecha)
    );
}

function claseEstado($estado)
{
    switch ($estado) {
        case "CONFIRMADO":
            return "status-confirmado";

        case "PREPARANDO":
            return "status-preparando";

        case "ENVIADO":
            return "status-enviado";

        case "ENTREGADO":
            return "status-entregado";

        case "CANCELADO":
            return "status-cancelado";

        default:
            return "status-pendiente";
    }
}

/* =========================
   DATOS DEL DASHBOARD
========================= */

try {

    /* PRODUCTOS */

    $stmt = $conexion->query("
        SELECT
            COUNT(*) AS total_productos,
            SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS productos_activos,
            SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) AS agotados,
            COALESCE(SUM(stock), 0) AS unidades_stock
        FROM productos
    ");

    $productosStats = $stmt->fetch();

    /* CATEGORÍAS */

    $stmt = $conexion->query("
        SELECT
            COUNT(*) AS total_categorias,
            SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS categorias_activas
        FROM categorias
    ");

    $categoriasStats = $stmt->fetch();

    /* PEDIDOS */

    $stmt = $conexion->query("
        SELECT
            COUNT(*) AS total_pedidos,
            SUM(
                CASE
                    WHEN estado = 'PENDIENTE'
                    THEN 1
                    ELSE 0
                END
            ) AS pedidos_pendientes,
            SUM(
                CASE
                    WHEN estado = 'ENTREGADO'
                    THEN 1
                    ELSE 0
                END
            ) AS pedidos_entregados,
            COALESCE(
                SUM(
                    CASE
                        WHEN estado <> 'CANCELADO'
                        THEN total
                        ELSE 0
                    END
                ),
                0
            ) AS ventas_totales
        FROM pedidos
    ");

    $pedidosStats = $stmt->fetch();

    /* PEDIDOS RECIENTES */

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

    /* PRODUCTOS CON POCO STOCK */

    $stmt = $conexion->query("
        SELECT
            p.id,
            p.nombre,
            p.stock,
            p.precio,
            c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c
            ON c.id = p.categoria_id
        WHERE p.estado = 1
          AND p.stock <= 5
        ORDER BY p.stock ASC, p.nombre ASC
        LIMIT 6
    ");

    $stockBajo = $stmt->fetchAll();

    /* VENTAS ÚLTIMOS 7 DÍAS */

    $stmt = $conexion->query("
        SELECT
            DATE(created_at) AS fecha,
            COALESCE(SUM(
                CASE
                    WHEN estado <> 'CANCELADO'
                    THEN total
                    ELSE 0
                END
            ), 0) AS total
        FROM pedidos
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY fecha ASC
    ");

    $ventasSemana = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        "Error dashboard admin: " .
        $e->getMessage()
    );

    die("No fue posible cargar el dashboard.");
}


/* =========================
   PREPARAR DATOS GRÁFICA
========================= */

$ventasLabels = [];
$ventasValores = [];

$ventasPorFecha = [];

foreach ($ventasSemana as $venta) {
    $ventasPorFecha[$venta["fecha"]] =
        (float)$venta["total"];
}

for ($i = 6; $i >= 0; $i--) {

    $fecha = date(
        "Y-m-d",
        strtotime("-{$i} days")
    );

    $ventasLabels[] = date(
        "d/m",
        strtotime($fecha)
    );

    $ventasValores[] =
        $ventasPorFecha[$fecha] ?? 0;
}

$ventasLabelsJson = json_encode(
    $ventasLabels,
    JSON_UNESCAPED_UNICODE
);

$ventasValoresJson = json_encode(
    $ventasValores
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

    <title>Dashboard | Bellaé Admin</title>

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #09090b;
            --panel: #111113;
            --panel-2: #151518;
            --border: #242429;
            --text: #f5f5f5;
            --muted: #94949f;
            --accent: #e9b8c8;
            --accent-strong: #d98fa9;
            --success: #77d7a2;
            --warning: #f1c875;
            --danger: #ed8585;
            --blue: #82b7ef;
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        button,
        input,
        select {
            font: inherit;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        /* =========================
           LAYOUT
        ========================= */

        .admin-layout {
            min-height: 100vh;
            display: flex;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 255px;
            background: #0d0d0f;
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
        }

        .brand {
            height: 82px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(
                135deg,
                #f1d0da,
                #d98fa9
            );
            color: #191217;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            margin-right: 12px;
        }

        .brand-text strong {
            display: block;
            font-size: 15px;
            letter-spacing: .3px;
        }

        .brand-text span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            margin-top: 3px;
        }

        .nav {
            padding: 22px 14px;
            flex: 1;
        }

        .nav-title {
            color: #62626b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            padding: 0 12px;
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 10px;
            color: #9999a3;
            font-size: 13px;
            margin-bottom: 4px;
            transition: .2s;
        }

        .nav-link:hover {
            background: #17171a;
            color: #fff;
        }

        .nav-link.active {
            background: rgba(233, 184, 200, .10);
            color: var(--accent);
        }

        .nav-icon {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .sidebar-bottom {
            padding: 14px;
            border-top: 1px solid var(--border);
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 10px;
            margin-bottom: 8px;
        }

        .admin-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #222226;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-weight: 800;
        }

        .admin-user strong {
            display: block;
            font-size: 12px;
        }

        .admin-user span {
            color: var(--muted);
            font-size: 10px;
        }

        .logout {
            display: block;
            padding: 10px 12px;
            border-radius: 9px;
            color: #9999a3;
            font-size: 12px;
        }

        .logout:hover {
            background: #18181b;
            color: #fff;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: 255px;
            width: calc(100% - 255px);
            min-height: 100vh;
        }

        .topbar {
            height: 82px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 34px;
            background: rgba(9, 9, 11, .92);
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(14px);
        }

        .topbar-title h1 {
            font-size: 20px;
            font-weight: 700;
        }

        .topbar-title p {
            color: var(--muted);
            font-size: 11px;
            margin-top: 4px;
        }

        .topbar-actions {
            display: flex;
            gap: 9px;
        }

        .top-btn {
            border: 1px solid var(--border);
            background: #131316;
            color: #ddd;
            padding: 9px 13px;
            border-radius: 9px;
            font-size: 12px;
            cursor: pointer;
        }

        .top-btn:hover {
            border-color: #393940;
            background: #18181c;
        }

        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 30px 34px 45px;
        }

        /* =========================
           STATS
        ========================= */

        .stats-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 15px;
            margin-bottom: 22px;
        }

        .stat-card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 15px;
            padding: 19px;
            min-height: 130px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 100px;
            height: 100px;
            right: -35px;
            top: -35px;
            border-radius: 50%;
            background: rgba(255,255,255,.025);
        }

        .stat-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-label {
            color: var(--muted);
            font-size: 11px;
        }

        .stat-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #1a1a1e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 800;
            margin-top: 13px;
            letter-spacing: -.6px;
        }

        .stat-sub {
            color: #6e6e77;
            font-size: 10px;
            margin-top: 5px;
        }

        /* =========================
           GRID
        ========================= */

        .dashboard-grid {
            display: grid;
            grid-template-columns:
                minmax(0, 1.6fr)
                minmax(300px, 1fr);
            gap: 18px;
            margin-bottom: 18px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 15px;
            overflow: hidden;
        }

        .panel-header {
            min-height: 67px;
            padding: 15px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .panel-header h2 {
            font-size: 13px;
            font-weight: 700;
        }

        .panel-header p {
            color: var(--muted);
            font-size: 10px;
            margin-top: 3px;
        }

        .panel-link {
            color: var(--accent);
            font-size: 10px;
        }

        /* =========================
           CHART
        ========================= */

        .chart-wrapper {
            height: 280px;
            padding: 20px;
            position: relative;
        }

        canvas {
            width: 100% !important;
            height: 100% !important;
        }

        /* =========================
           RECENT ORDERS
        ========================= */

        .orders-list {
            padding: 6px 18px;
        }

        .order-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 13px 0;
            border-bottom: 1px solid #1d1d21;
        }

        .order-item:last-child {
            border-bottom: 0;
        }

        .order-info strong {
            display: block;
            font-size: 12px;
        }

        .order-info span {
            display: block;
            color: var(--muted);
            font-size: 10px;
            margin-top: 3px;
        }

        .order-right {
            text-align: right;
        }

        .order-total {
            display: block;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 7px;
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .3px;
        }

        .status-pendiente {
            color: var(--warning);
            background: rgba(241, 200, 117, .10);
        }

        .status-confirmado {
            color: var(--blue);
            background: rgba(130, 183, 239, .10);
        }

        .status-preparando {
            color: var(--accent);
            background: rgba(233, 184, 200, .10);
        }

        .status-enviado {
            color: #9c9cf0;
            background: rgba(156, 156, 240, .10);
        }

        .status-entregado {
            color: var(--success);
            background: rgba(119, 215, 162, .10);
        }

        .status-cancelado {
            color: var(--danger);
            background: rgba(237, 133, 133, .10);
        }

        /* =========================
           STOCK
        ========================= */

        .stock-list {
            padding: 7px 18px;
        }

        .stock-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #1d1d21;
        }

        .stock-item:last-child {
            border-bottom: 0;
        }

        .product-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #1a1a1e;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            font-size: 14px;
        }

        .stock-info {
            min-width: 0;
            flex: 1;
        }

        .stock-info strong {
            display: block;
            font-size: 11px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stock-info span {
            color: var(--muted);
            font-size: 9px;
            display: block;
            margin-top: 3px;
        }

        .stock-number {
            font-size: 11px;
            font-weight: 800;
            color: var(--danger);
            white-space: nowrap;
        }

        /* =========================
           QUICK ACTIONS
        ========================= */

        .quick-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 12px;
            padding: 18px;
        }

        .quick-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            background: #131316;
            transition: .2s;
        }

        .quick-card:hover {
            border-color: #3a3a41;
            transform: translateY(-2px);
        }

        .quick-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            background: #1c1c20;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 11px;
        }

        .quick-card strong {
            display: block;
            font-size: 11px;
        }

        .quick-card span {
            display: block;
            color: var(--muted);
            font-size: 9px;
            margin-top: 4px;
            line-height: 1.4;
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            padding: 35px 18px;
            text-align: center;
            color: var(--muted);
            font-size: 11px;
        }

        /* =========================
           MOBILE
        ========================= */

        .mobile-menu {
            display: none;
        }

        @media (max-width: 1100px) {

            .stats-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .quick-grid {
                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 800px) {

            .sidebar {
                transform: translateX(-100%);
                transition: .25s;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;
                width: 100%;
            }

            .mobile-menu {
                display: flex;
                width: 38px;
                height: 38px;
                border: 1px solid var(--border);
                background: #131316;
                color: #fff;
                border-radius: 9px;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                margin-right: 10px;
            }

            .topbar {
                padding: 0 16px;
            }

            .topbar-title {
                flex: 1;
            }

            .topbar-title h1 {
                font-size: 17px;
            }

            .top-btn span {
                display: none;
            }

            .content {
                padding: 20px 16px 35px;
            }
        }

        @media (max-width: 550px) {

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-grid {
                grid-template-columns: 1fr;
            }

            .stat-value {
                font-size: 25px;
            }

            .order-item {
                align-items: flex-start;
            }
        }

    </style>

</head>

<body>

<div class="admin-layout">

    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">

        <div class="brand">

            <div class="brand-mark">
                B
            </div>

            <div class="brand-text">

                <strong>Bellaé</strong>

                <span>Panel administrativo</span>

            </div>

        </div>


        <nav class="nav">

            <div class="nav-title">
                Principal
            </div>

            <a
                href="index.php"
                class="nav-link active"
            >
                <span class="nav-icon">⌂</span>
                Dashboard
            </a>

            <a
                href="productos.php"
                class="nav-link"
            >
                <span class="nav-icon">◈</span>
                Productos
            </a>

            <a
                href="categorias.php"
                class="nav-link"
            >
                <span class="nav-icon">▦</span>
                Categorías
            </a>

            <a
                href="pedidos.php"
                class="nav-link"
            >
                <span class="nav-icon">□</span>
                Pedidos
            </a>

            <div
                class="nav-title"
                style="margin-top: 28px;"
            >
                Tienda
            </div>

            <a
                href="../index.php"
                target="_blank"
                class="nav-link"
            >
                <span class="nav-icon">↗</span>
                Ver tienda
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="admin-user">

                <div class="admin-avatar">
                    A
                </div>

                <div>

                    <strong>
                        <?= escapar($_SESSION["admin_nombre"] ?? "Administrador") ?>
                    </strong>

                    <span>
                        Administrador
                    </span>

                </div>

            </div>

            <a
                href="logout.php"
                class="logout"
            >
                ↪ Cerrar sesión
            </a>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <!-- TOPBAR -->

        <header class="topbar">

            <div style="display:flex;align-items:center;">

                <button
                    class="mobile-menu"
                    id="mobileMenu"
                    type="button"
                >
                    ☰
                </button>

                <div class="topbar-title">

                    <h1>
                        Dashboard
                    </h1>

                    <p>
                        Resumen general de Bellaé
                    </p>

                </div>

            </div>


            <div class="topbar-actions">

                <a
                    href="../index.php"
                    target="_blank"
                    class="top-btn"
                >
                    ↗ <span>Ver tienda</span>
                </a>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">


            <!-- STATS -->

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-top">

                        <span class="stat-label">
                            Ventas totales
                        </span>

                        <div class="stat-icon">
                            $
                        </div>

                    </div>

                    <div class="stat-value">
                        <?= formatoPrecio($pedidosStats["ventas_totales"] ?? 0) ?>
                    </div>

                    <div class="stat-sub">
                        Pedidos no cancelados
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <span class="stat-label">
                            Pedidos
                        </span>

                        <div class="stat-icon">
                            □
                        </div>

                    </div>

                    <div class="stat-value">
                        <?= (int)($pedidosStats["total_pedidos"] ?? 0) ?>
                    </div>

                    <div class="stat-sub">
                        <?= (int)($pedidosStats["pedidos_pendientes"] ?? 0) ?>
                        pendientes
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <span class="stat-label">
                            Productos
                        </span>

                        <div class="stat-icon">
                            ◈
                        </div>

                    </div>

                    <div class="stat-value">
                        <?= (int)($productosStats["total_productos"] ?? 0) ?>
                    </div>

                    <div class="stat-sub">
                        <?= (int)($productosStats["productos_activos"] ?? 0) ?>
                        activos
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">

                        <span class="stat-label">
                            Stock disponible
                        </span>

                        <div class="stat-icon">
                            ▣
                        </div>

                    </div>

                    <div class="stat-value">
                        <?= (int)($productosStats["unidades_stock"] ?? 0) ?>
                    </div>

                    <div class="stat-sub">
                        <?= (int)($productosStats["agotados"] ?? 0) ?>
                        productos agotados
                    </div>

                </div>

            </div>


            <!-- CHART + RECENT ORDERS -->

            <div class="dashboard-grid">


                <!-- CHART -->

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Ventas de los últimos 7 días
                            </h2>

                            <p>
                                Rendimiento de la tienda
                            </p>

                        </div>

                    </div>

                    <div class="chart-wrapper">

                        <canvas id="ventasChart"></canvas>

                    </div>

                </div>


                <!-- RECENT ORDERS -->

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Pedidos recientes
                            </h2>

                            <p>
                                Últimas compras realizadas
                            </p>

                        </div>

                        <a
                            href="pedidos.php"
                            class="panel-link"
                        >
                            Ver todos →
                        </a>

                    </div>


                    <div class="orders-list">

                        <?php if (empty($pedidosRecientes)): ?>

                            <div class="empty">
                                Aún no hay pedidos.
                            </div>

                        <?php else: ?>

                            <?php foreach ($pedidosRecientes as $pedido): ?>

                                <a
                                    href="pedidos.php"
                                    class="order-item"
                                >

                                    <div class="order-info">

                                        <strong>
                                            #<?= escapar($pedido["numero_pedido"]) ?>
                                        </strong>

                                        <span>
                                            <?= escapar($pedido["nombre_cliente"]) ?>
                                        </span>

                                        <span>
                                            <?= formatoFecha($pedido["created_at"]) ?>
                                        </span>

                                    </div>

                                    <div class="order-right">

                                        <span class="order-total">
                                            <?= formatoPrecio($pedido["total"]) ?>
                                        </span>

                                        <span
                                            class="status <?= claseEstado($pedido["estado"]) ?>"
                                        >
                                            <?= escapar($pedido["estado"]) ?>
                                        </span>

                                    </div>

                                </a>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </div>


            <!-- STOCK + CATEGORIES -->

            <div class="dashboard-grid">


                <!-- LOW STOCK -->

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Stock bajo
                            </h2>

                            <p>
                                Productos que necesitan atención
                            </p>

                        </div>

                        <a
                            href="productos.php"
                            class="panel-link"
                        >
                            Gestionar →
                        </a>

                    </div>


                    <div class="stock-list">

                        <?php if (empty($stockBajo)): ?>

                            <div class="empty">
                                Todo el inventario está en buen nivel. ✨
                            </div>

                        <?php else: ?>

                            <?php foreach ($stockBajo as $producto): ?>

                                <div class="stock-item">

                                    <div class="product-icon">
                                        ◈
                                    </div>

                                    <div class="stock-info">

                                        <strong>
                                            <?= escapar($producto["nombre"]) ?>
                                        </strong>

                                        <span>
                                            <?= escapar($producto["categoria"] ?? "Sin categoría") ?>
                                            ·
                                            <?= formatoPrecio($producto["precio"]) ?>
                                        </span>

                                    </div>

                                    <div class="stock-number">

                                        <?php if ((int)$producto["stock"] === 0): ?>

                                            AGOTADO

                                        <?php else: ?>

                                            <?= (int)$producto["stock"] ?>
                                            uds.

                                        <?php endif; ?>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- STORE SUMMARY -->

                <div class="panel">

                    <div class="panel-header">

                        <div>

                            <h2>
                                Resumen de la tienda
                            </h2>

                            <p>
                                Estado actual
                            </p>

                        </div>

                    </div>


                    <div class="stock-list">

                        <div class="stock-item">

                            <div class="product-icon">
                                ▦
                            </div>

                            <div class="stock-info">

                                <strong>
                                    Categorías
                                </strong>

                                <span>
                                    <?= (int)($categoriasStats["categorias_activas"] ?? 0) ?>
                                    activas
                                </span>

                            </div>

                            <div class="stock-number"
                                 style="color:var(--accent);">

                                <?= (int)($categoriasStats["total_categorias"] ?? 0) ?>

                            </div>

                        </div>


                        <div class="stock-item">

                            <div class="product-icon">
                                ✓
                            </div>

                            <div class="stock-info">

                                <strong>
                                    Pedidos entregados
                                </strong>

                                <span>
                                    Total histórico
                                </span>

                            </div>

                            <div
                                class="stock-number"
                                style="color:var(--success);"
                            >
                                <?= (int)($pedidosStats["pedidos_entregados"] ?? 0) ?>
                            </div>

                        </div>


                        <div class="stock-item">

                            <div class="product-icon">
                                ◈
                            </div>

                            <div class="stock-info">

                                <strong>
                                    Productos activos
                                </strong>

                                <span>
                                    Disponibles en tienda
                                </span>

                            </div>

                            <div
                                class="stock-number"
                                style="color:var(--blue);"
                            >
                                <?= (int)($productosStats["productos_activos"] ?? 0) ?>
                            </div>

                        </div>


                        <div class="stock-item">

                            <div class="product-icon">
                                !
                            </div>

                            <div class="stock-info">

                                <strong>
                                    Stock agotado
                                </strong>

                                <span>
                                    Requieren reposición
                                </span>

                            </div>

                            <div
                                class="stock-number"
                                style="color:var(--danger);"
                            >
                                <?= (int)($productosStats["agotados"] ?? 0) ?>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- QUICK ACTIONS -->

            <div class="panel">

                <div class="panel-header">

                    <div>

                        <h2>
                            Acciones rápidas
                        </h2>

                        <p>
                            Administra tu tienda rápidamente
                        </p>

                    </div>

                </div>


                <div class="quick-grid">

                    <a
                        href="productos.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            +
                        </div>

                        <strong>
                            Nuevo producto
                        </strong>

                        <span>
                            Agrega productos al catálogo.
                        </span>

                    </a>


                    <a
                        href="categorias.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            ▦
                        </div>

                        <strong>
                            Nueva categoría
                        </strong>

                        <span>
                            Organiza mejor tus productos.
                        </span>

                    </a>


                    <a
                        href="pedidos.php"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            □
                        </div>

                        <strong>
                            Gestionar pedidos
                        </strong>

                        <span>
                            Revisa y actualiza pedidos.
                        </span>

                    </a>


                    <a
                        href="../index.php"
                        target="_blank"
                        class="quick-card"
                    >

                        <div class="quick-icon">
                            ↗
                        </div>

                        <strong>
                            Ver tienda
                        </strong>

                        <span>
                            Comprueba cómo ven los clientes la tienda.
                        </span>

                    </a>

                </div>

            </div>

        </section>

    </main>

</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

const labels = <?= $ventasLabelsJson ?>;
const valores = <?= $ventasValoresJson ?>;

const canvas = document.getElementById("ventasChart");

if (canvas) {

    const ctx = canvas.getContext("2d");

    const gradient = ctx.createLinearGradient(
        0,
        0,
        0,
        260
    );

    gradient.addColorStop(
        0,
        "rgba(233,184,200,.28)"
    );

    gradient.addColorStop(
        1,
        "rgba(233,184,200,0)"
    );

    new Chart(ctx, {

        type: "line",

        data: {

            labels: labels,

            datasets: [

                {
                    data: valores,

                    borderColor: "#e9b8c8",

                    backgroundColor: gradient,

                    borderWidth: 2,

                    fill: true,

                    tension: .38,

                    pointRadius: 3,

                    pointHoverRadius: 5,

                    pointBackgroundColor: "#e9b8c8",

                    pointBorderWidth: 0
                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                },

                tooltip: {

                    backgroundColor: "#18181c",

                    borderColor: "#303036",

                    borderWidth: 1,

                    titleColor: "#fff",

                    bodyColor: "#e9b8c8",

                    padding: 11,

                    displayColors: false,

                    callbacks: {

                        label: function(context) {

                            return "$" +
                                Number(
                                    context.raw
                                ).toLocaleString(
                                    "es-CO"
                                );

                        }

                    }

                }

            },

            scales: {

                x: {

                    grid: {
                        display: false
                    },

                    border: {
                        display: false
                    },

                    ticks: {
                        color: "#707079",
                        font: {
                            size: 9
                        }
                    }

                },

                y: {

                    beginAtZero: true,

                    grid: {
                        color: "rgba(255,255,255,.045)"
                    },

                    border: {
                        display: false
                    },

                    ticks: {

                        color: "#707079",

                        font: {
                            size: 9
                        },

                        callback: function(value) {

                            return "$" +
                                Number(value)
                                    .toLocaleString(
                                        "es-CO"
                                    );

                        }

                    }

                }

            }

        }

    });

}


/* =========================
   MOBILE SIDEBAR
========================= */

const mobileMenu =
    document.getElementById("mobileMenu");

const sidebar =
    document.getElementById("sidebar");

if (mobileMenu && sidebar) {

    mobileMenu.addEventListener(
        "click",
        function() {

            sidebar.classList.toggle("open");

        }
    );

}


/* CERRAR SIDEBAR AL HACER CLICK
   FUERA EN MÓVIL */

document.addEventListener(
    "click",
    function(event) {

        if (
            window.innerWidth <= 800 &&
            sidebar &&
            sidebar.classList.contains("open") &&
            !sidebar.contains(event.target) &&
            event.target !== mobileMenu
        ) {

            sidebar.classList.remove("open");

        }

    }
);

</script>

</body>

</html>