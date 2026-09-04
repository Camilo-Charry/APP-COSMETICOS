<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

/* =========================================================
   FUNCIONES
========================================================= */

function escapar($texto)
{
    return htmlspecialchars((string)$texto, ENT_QUOTES, "UTF-8");
}

function formatoPrecio($precio)
{
    return "$" . number_format((float)$precio, 0, ",", ".");
}

function formatoFecha($fecha)
{
    if (!$fecha) {
        return "-";
    }

    $timestamp = strtotime($fecha);

    if (!$timestamp) {
        return "-";
    }

    return date("d/m/Y H:i", $timestamp);
}

function claseEstado($estado)
{
    switch ($estado) {
        case "PENDIENTE":
            return "pendiente";

        case "CONFIRMADO":
            return "confirmado";

        case "PREPARANDO":
            return "preparando";

        case "ENVIADO":
            return "enviado";

        case "ENTREGADO":
            return "entregado";

        case "CANCELADO":
            return "cancelado";

        default:
            return "";
    }
}

function iconoEstado($estado)
{
    switch ($estado) {
        case "PENDIENTE":
            return "⏳";

        case "CONFIRMADO":
            return "✓";

        case "PREPARANDO":
            return "📦";

        case "ENVIADO":
            return "🚚";

        case "ENTREGADO":
            return "✓";

        case "CANCELADO":
            return "✕";

        default:
            return "•";
    }
}

function prepararWhatsapp($telefono)
{
    $numero = preg_replace("/[^0-9]/", "", (string)$telefono);

    /*
     * Si el número viene como:
     * 3222329208
     *
     * agregamos Colombia:
     * 57 + número
     */
    if (strlen($numero) === 10 && substr($numero, 0, 1) === "3") {
        $numero = "57" . $numero;
    }

    return $numero;
}

function whatsappUrl($telefono)
{
    $numero = prepararWhatsapp($telefono);

    return "https://wa.me/" . $numero;
}

/* =========================================================
   FILTROS
========================================================= */

$busqueda = trim($_GET["buscar"] ?? "");
$estadoFiltro = trim($_GET["estado"] ?? "");

$estadosValidos = [
    "PENDIENTE",
    "CONFIRMADO",
    "PREPARANDO",
    "ENVIADO",
    "ENTREGADO",
    "CANCELADO"
];

/* =========================================================
   CONSULTAR PEDIDOS
========================================================= */

$where = [];
$params = [];

if ($busqueda !== "") {

    $where[] = "(
        numero_pedido LIKE :buscar
        OR nombre_cliente LIKE :buscar
        OR telefono LIKE :buscar
        OR email LIKE :buscar
    )";

    $params[":buscar"] = "%" . $busqueda . "%";
}

if (in_array($estadoFiltro, $estadosValidos, true)) {

    $where[] = "estado = :estado";

    $params[":estado"] = $estadoFiltro;
}

$whereSql = "";

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

try {

    $sql = "
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
        $whereSql
        ORDER BY id DESC
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);

    $pedidos = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log("Error consultando pedidos: " . $e->getMessage());

    $pedidos = [];
}

/* =========================================================
   ESTADÍSTICAS
========================================================= */

try {

    $stmt = $conexion->query("
        SELECT
            COUNT(*) AS total,

            SUM(
                CASE
                    WHEN estado = 'PENDIENTE'
                    THEN 1
                    ELSE 0
                END
            ) AS pendientes,

            SUM(
                CASE
                    WHEN estado = 'ENTREGADO'
                    THEN 1
                    ELSE 0
                END
            ) AS entregados,

            COALESCE(
                SUM(
                    CASE
                        WHEN estado != 'CANCELADO'
                        THEN total
                        ELSE 0
                    END
                ),
                0
            ) AS ventas

        FROM pedidos
    ");

    $estadisticas = $stmt->fetch();

} catch (PDOException $e) {

    error_log("Error calculando estadísticas: " . $e->getMessage());

    $estadisticas = [
        "total" => 0,
        "pendientes" => 0,
        "entregados" => 0,
        "ventas" => 0
    ];
}

/* =========================================================
   JSON PARA JAVASCRIPT
========================================================= */

$pedidosJson = json_encode(
    $pedidos,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES |
    JSON_HEX_TAG |
    JSON_HEX_AMP |
    JSON_HEX_APOS |
    JSON_HEX_QUOT
);

if ($pedidosJson === false) {
    $pedidosJson = "[]";
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

    <title>Pedidos | Bellaé Admin</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background: #0d0d12;
            color: #f5f5f7;
            min-height: 100vh;
        }

        button,
        input,
        select {
            font: inherit;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* =====================================================
           LAYOUT
        ===================================================== */

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: #13131a;
            border-right: 1px solid #292934;

            padding: 25px 16px;

            position: fixed;

            top: 0;
            bottom: 0;
            left: 0;

            z-index: 100;
        }

        .logo {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo img {
            max-width: 155px;
            max-height: 70px;
            object-fit: contain;
        }

        .logo-text {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #ffffff;
        }

        .logo-text span {
            color: #e7a8c0;
        }

        .menu {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .menu a {
            padding: 13px 15px;

            border-radius: 11px;

            color: #aaaab5;

            transition: 0.2s;

            display: flex;
            align-items: center;

            gap: 11px;

            font-size: 14px;
        }

        .menu a:hover {
            background: #1e1e27;
            color: white;
        }

        .menu a.active {
            background: #e7a8c0;
            color: #171118;
            font-weight: 700;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
        }

        .logout {
            position: absolute;

            left: 16px;
            right: 16px;
            bottom: 20px;
        }

        .logout a {
            background: #1c1c24;
        }

        .logout a:hover {
            background: #292933;
        }

        /* =====================================================
           MAIN
        ===================================================== */

        .main {
            margin-left: 250px;

            width: calc(100% - 250px);

            padding: 32px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            margin-bottom: 30px;
        }

        .page-title h1 {
            font-size: 30px;
            font-weight: 800;

            margin-bottom: 5px;
        }

        .page-title p {
            color: #858592;
            font-size: 14px;
        }

        /* =====================================================
           STATS
        ===================================================== */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 18px;

            margin-bottom: 25px;
        }

        .stat-card {
            background: #15151d;

            border: 1px solid #292934;

            border-radius: 16px;

            padding: 21px;
        }

        .stat-top {
            display: flex;

            justify-content: space-between;
            align-items: center;

            margin-bottom: 14px;
        }

        .stat-label {
            color: #92929d;
            font-size: 13px;
        }

        .stat-icon {
            width: 38px;
            height: 38px;

            border-radius: 11px;

            background: #20202a;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 18px;
        }

        .stat-value {
            font-size: 27px;
            font-weight: 800;
        }

        /* =====================================================
           FILTERS
        ===================================================== */

        .filters {
            background: #15151d;

            border: 1px solid #292934;

            border-radius: 16px;

            padding: 17px;

            margin-bottom: 20px;

            display: flex;

            gap: 12px;

            align-items: center;

            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;

            background: #0f0f15;

            border: 1px solid #30303b;

            color: white;

            border-radius: 10px;

            padding: 12px 14px;

            outline: none;
        }

        .search-box input:focus {
            border-color: #e7a8c0;
        }

        .filter-select {
            background: #0f0f15;

            border: 1px solid #30303b;

            color: white;

            border-radius: 10px;

            padding: 12px 14px;

            outline: none;

            min-width: 180px;
        }

        .btn {
            border: none;

            border-radius: 10px;

            padding: 12px 18px;

            cursor: pointer;

            font-weight: 700;

            transition: 0.2s;
        }

        .btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .btn-primary {
            background: #e7a8c0;
            color: #171118;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .btn-secondary {
            background: #24242e;
            color: white;
        }

        .btn-secondary:hover {
            background: #30303b;
        }

        /* =====================================================
           TABLE
        ===================================================== */

        .table-card {
            background: #15151d;

            border: 1px solid #292934;

            border-radius: 16px;

            overflow: hidden;
        }

        .table-header {
            padding: 19px 22px;

            border-bottom: 1px solid #292934;

            display: flex;

            align-items: center;

            justify-content: space-between;
        }

        .table-header h2 {
            font-size: 17px;
        }

        .table-count {
            color: #858592;
            font-size: 13px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;

            border-collapse: collapse;

            min-width: 950px;
        }

        th {
            text-align: left;

            padding: 14px 18px;

            color: #777783;

            font-size: 11px;

            text-transform: uppercase;

            letter-spacing: 0.6px;

            border-bottom: 1px solid #292934;
        }

        td {
            padding: 16px 18px;

            border-bottom: 1px solid #24242d;

            font-size: 13px;

            vertical-align: middle;
        }

        tbody tr {
            transition: 0.15s;
        }

        tbody tr:hover {
            background: #1a1a23;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .pedido-number {
            font-weight: 800;
            color: #e7a8c0;
        }

        .cliente {
            font-weight: 700;
            color: #f2f2f4;
        }

        .cliente-phone {
            color: #797985;

            margin-top: 3px;

            font-size: 12px;
        }

        .total {
            font-weight: 800;
        }

        /* =====================================================
           ESTADOS
        ===================================================== */

        .estado {
            display: inline-flex;

            align-items: center;

            gap: 6px;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 11px;

            font-weight: 800;

            white-space: nowrap;
        }

        .estado.pendiente {
            background: rgba(245, 190, 70, 0.12);
            color: #f5c55d;
        }

        .estado.confirmado {
            background: rgba(100, 190, 255, 0.12);
            color: #70c8ff;
        }

        .estado.preparando {
            background: rgba(180, 130, 255, 0.12);
            color: #b995ff;
        }

        .estado.enviado {
            background: rgba(80, 210, 190, 0.12);
            color: #62d7c2;
        }

        .estado.entregado {
            background: rgba(90, 210, 120, 0.12);
            color: #70db8a;
        }

        .estado.cancelado {
            background: rgba(255, 90, 100, 0.12);
            color: #ff737b;
        }

        /* =====================================================
           ACTIONS
        ===================================================== */

        .actions {
            display: flex;

            gap: 7px;

            align-items: center;
        }

        .action-btn {
            width: 36px;
            height: 36px;

            border: 1px solid #30303b;

            background: #20202a;

            color: white;

            border-radius: 9px;

            cursor: pointer;

            display: flex;

            align-items: center;
            justify-content: center;

            transition: 0.2s;
        }

        .action-btn:hover {
            background: #2b2b37;
            border-color: #484852;
        }

        .action-btn.whatsapp:hover {
            background: #193d2a;
            border-color: #286845;
        }

        /* =====================================================
           EMPTY
        ===================================================== */

        .empty {
            padding: 60px 20px;

            text-align: center;

            color: #777783;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .empty h3 {
            color: #dddde2;
            margin-bottom: 6px;
        }

        /* =====================================================
           MODAL
        ===================================================== */

        .modal-overlay {
            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, 0.72);

            backdrop-filter: blur(5px);

            z-index: 500;

            display: none;

            align-items: center;
            justify-content: center;

            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            width: 100%;

            max-width: 850px;

            max-height: 90vh;

            overflow-y: auto;

            background: #15151d;

            border: 1px solid #353541;

            border-radius: 18px;

            box-shadow:
                0 25px 80px
                rgba(0, 0, 0, 0.45);
        }

        .modal-header {
            padding: 20px 23px;

            border-bottom: 1px solid #292934;

            display: flex;

            justify-content: space-between;
            align-items: center;

            gap: 15px;
        }

        .modal-header h2 {
            font-size: 20px;
        }

        .modal-close {
            width: 35px;
            height: 35px;

            border: none;

            border-radius: 9px;

            background: #24242e;

            color: white;

            cursor: pointer;

            font-size: 18px;
        }

        .modal-close:hover {
            background: #30303b;
        }

        .modal-body {
            padding: 23px;
        }

        .order-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 14px;

            margin-bottom: 22px;
        }

        .info-box {
            background: #101017;

            border: 1px solid #282832;

            border-radius: 11px;

            padding: 14px;
        }

        .info-label {
            color: #747480;

            font-size: 11px;

            text-transform: uppercase;

            margin-bottom: 5px;
        }

        .info-value {
            color: #f0f0f2;

            font-size: 14px;

            font-weight: 600;

            word-break: break-word;
        }

        .products-title {
            font-size: 15px;

            margin-bottom: 12px;
        }

        .products-list {
            border: 1px solid #292934;

            border-radius: 12px;

            overflow: hidden;
        }

        .product-row {
            display: grid;

            grid-template-columns:
                1fr 90px 100px 110px;

            gap: 10px;

            padding: 13px 15px;

            border-bottom: 1px solid #24242d;

            align-items: center;

            font-size: 13px;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row.header {
            color: #777783;

            font-size: 10px;

            text-transform: uppercase;

            font-weight: 700;
        }

        .product-name {
            font-weight: 600;
        }

        .product-price,
        .product-quantity,
        .product-subtotal {
            text-align: right;
        }

        .modal-total {
            display: flex;

            justify-content: space-between;

            align-items: center;

            padding: 18px 3px 4px;

            font-size: 17px;

            font-weight: 800;
        }

        .modal-total span:last-child {
            color: #e7a8c0;

            font-size: 22px;
        }

        .status-section {
            margin-top: 22px;

            padding-top: 20px;

            border-top: 1px solid #292934;
        }

        .status-section label {
            display: block;

            font-size: 12px;

            color: #8b8b96;

            margin-bottom: 8px;
        }

        .status-controls {
            display: flex;

            gap: 10px;
        }

        .status-controls select {
            flex: 1;

            background: #0f0f15;

            color: white;

            border: 1px solid #30303b;

            border-radius: 10px;

            padding: 12px;

            outline: none;
        }

        .status-controls select:focus {
            border-color: #e7a8c0;
        }

        .modal-footer {
            padding: 18px 23px;

            border-top: 1px solid #292934;

            display: flex;

            justify-content: space-between;

            gap: 10px;
        }

        .whatsapp-link {
            display: inline-flex;

            align-items: center;

            gap: 7px;

            background: #1d633e;

            color: white;

            padding: 11px 15px;

            border-radius: 10px;

            font-size: 13px;

            font-weight: 700;
        }

        .whatsapp-link:hover {
            background: #23764b;
        }

        /* =====================================================
           LOADING
        ===================================================== */

        .loading {
            padding: 30px;

            text-align: center;

            color: #888893;
        }

        /* =====================================================
           NOTIFICACIÓN
        ===================================================== */

        .notification {
            position: fixed;

            right: 25px;
            bottom: 25px;

            z-index: 1000;

            background: #20202a;

            border: 1px solid #383842;

            color: white;

            padding: 14px 18px;

            border-radius: 11px;

            box-shadow:
                0 12px 35px
                rgba(0, 0, 0, 0.35);

            transform: translateY(100px);

            opacity: 0;

            pointer-events: none;

            transition: 0.3s;
        }

        .notification.show {
            transform: translateY(0);

            opacity: 1;
        }

        .notification.success {
            border-color: #31744d;
        }

        .notification.error {
            border-color: #8a363d;
        }

        /* =====================================================
           MOBILE
        ===================================================== */

        .mobile-header {
            display: none;
        }

        @media (max-width: 1100px) {

            .stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }

        @media (max-width: 800px) {

            .sidebar {
                transform: translateX(-100%);

                transition: 0.25s;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main {
                margin-left: 0;

                width: 100%;

                padding: 20px;

                padding-top: 80px;
            }

            .mobile-header {
                position: fixed;

                top: 0;
                left: 0;
                right: 0;

                height: 62px;

                background: #13131a;

                border-bottom: 1px solid #292934;

                z-index: 200;

                display: flex;

                align-items: center;

                justify-content: space-between;

                padding: 0 17px;
            }

            .mobile-menu-btn {
                border: none;

                background: #22222c;

                color: white;

                width: 38px;
                height: 38px;

                border-radius: 9px;

                cursor: pointer;

                font-size: 18px;
            }

            .mobile-logo {
                font-weight: 800;
            }

            .topbar {
                margin-bottom: 20px;
            }

            .page-title h1 {
                font-size: 25px;
            }
        }

        @media (max-width: 600px) {

            .main {
                padding: 15px;

                padding-top: 77px;
            }

            .stats {
                grid-template-columns: 1fr 1fr;

                gap: 10px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-value {
                font-size: 21px;
            }

            .filters {
                padding: 12px;
            }

            .search-box {
                min-width: 100%;
            }

            .filter-select {
                flex: 1;

                min-width: 0;
            }

            .filters .btn {
                flex: 1;
            }

            .order-grid {
                grid-template-columns: 1fr;
            }

            .modal {
                max-height: 94vh;
            }

            .modal-body {
                padding: 17px;
            }

            .modal-footer {
                flex-direction: column;
            }

            .whatsapp-link,
            .modal-footer .btn {
                width: 100%;

                justify-content: center;

                text-align: center;
            }

            .status-controls {
                flex-direction: column;
            }
        }

    </style>

</head>

<body>

<!-- =====================================================
     MOBILE HEADER
===================================================== -->

<div class="mobile-header">

    <button
        class="mobile-menu-btn"
        onclick="toggleSidebar()"
        aria-label="Abrir menú"
    >
        ☰
    </button>

    <div class="mobile-logo">
        Bellaé Admin
    </div>

    <div style="width:38px;"></div>

</div>

<div class="admin-layout">

    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar" id="sidebar">

        <div class="logo">

            <?php if (file_exists("../assets/img/logo.png")): ?>

                <img
                    src="../assets/img/logo.png"
                    alt="Bellaé"
                >

            <?php else: ?>

                <div class="logo-text">
                    Bella<span>é</span>
                </div>

            <?php endif; ?>

        </div>

        <nav class="menu">

            <a href="index.php">
                <span class="menu-icon">🏠</span>
                Dashboard
            </a>

            <a href="productos.php">
                <span class="menu-icon">💄</span>
                Productos
            </a>

            <a href="categorias.php">
                <span class="menu-icon">📂</span>
                Categorías
            </a>

            <a
                href="pedidos.php"
                class="active"
            >
                <span class="menu-icon">🛍️</span>
                Pedidos
            </a>

            <a href="estadisticas.php">
                <span class="menu-icon">📊</span>
                Estadísticas
            </a>

        </nav>

        <div class="logout">

            <a href="logout.php">

                <span class="menu-icon">🚪</span>

                Cerrar sesión

            </a>

        </div>

    </aside>

    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="main">

        <div class="topbar">

            <div class="page-title">

                <h1>Pedidos</h1>

                <p>
                    Administra y controla todos los pedidos de tu tienda.
                </p>

            </div>

        </div>

        <!-- =================================================
             ESTADÍSTICAS
        ================================================== -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Total pedidos
                    </span>

                    <div class="stat-icon">
                        🛍️
                    </div>

                </div>

                <div class="stat-value">
                    <?= number_format((int)($estadisticas["total"] ?? 0)) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Pendientes
                    </span>

                    <div class="stat-icon">
                        ⏳
                    </div>

                </div>

                <div class="stat-value">
                    <?= number_format((int)($estadisticas["pendientes"] ?? 0)) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Entregados
                    </span>

                    <div class="stat-icon">
                        ✓
                    </div>

                </div>

                <div class="stat-value">
                    <?= number_format((int)($estadisticas["entregados"] ?? 0)) ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-top">

                    <span class="stat-label">
                        Ventas
                    </span>

                    <div class="stat-icon">
                        💰
                    </div>

                </div>

                <div class="stat-value">
                    <?= formatoPrecio($estadisticas["ventas"] ?? 0) ?>
                </div>

            </div>

        </section>

        <!-- =================================================
             FILTROS
        ================================================== -->

        <form
            method="GET"
            class="filters"
        >

            <div class="search-box">

                <input
                    type="text"
                    name="buscar"
                    placeholder="Buscar por pedido, cliente, teléfono o email..."
                    value="<?= escapar($busqueda) ?>"
                >

            </div>

            <select
                name="estado"
                class="filter-select"
            >

                <option value="">
                    Todos los estados
                </option>

                <?php foreach ($estadosValidos as $estado): ?>

                    <option
                        value="<?= escapar($estado) ?>"
                        <?= $estadoFiltro === $estado ? "selected" : "" ?>
                    >
                        <?= escapar($estado) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Filtrar
            </button>

            <a
                href="pedidos.php"
                class="btn btn-secondary"
            >
                Limpiar
            </a>

        </form>

        <!-- =================================================
             TABLA
        ================================================== -->

        <section class="table-card">

            <div class="table-header">

                <h2>
                    Lista de pedidos
                </h2>

                <span class="table-count">
                    <?= count($pedidos) ?> resultado(s)
                </span>

            </div>

            <?php if (empty($pedidos)): ?>

                <div class="empty">

                    <div class="empty-icon">
                        🛍️
                    </div>

                    <h3>
                        No hay pedidos
                    </h3>

                    <p>
                        No encontramos pedidos con los filtros seleccionados.
                    </p>

                </div>

            <?php else: ?>

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
                                    Ciudad
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php foreach ($pedidos as $pedido): ?>

                            <tr>

                                <td>

                                    <div class="pedido-number">

                                        #<?= escapar($pedido["numero_pedido"]) ?>

                                    </div>

                                </td>

                                <td>

                                    <div class="cliente">

                                        <?= escapar($pedido["nombre_cliente"]) ?>

                                    </div>

                                    <div class="cliente-phone">

                                        <?= escapar($pedido["telefono"]) ?>

                                    </div>

                                </td>

                                <td>

                                    <?= escapar($pedido["ciudad"]) ?>

                                </td>

                                <td>

                                    <div class="total">

                                        <?= formatoPrecio($pedido["total"]) ?>

                                    </div>

                                </td>

                                <td>

                                    <span
                                        class="estado <?= escapar(claseEstado($pedido["estado"])) ?>"
                                    >

                                        <?= iconoEstado($pedido["estado"]) ?>

                                        <?= escapar($pedido["estado"]) ?>

                                    </span>

                                </td>

                                <td>

                                    <?= formatoFecha($pedido["created_at"]) ?>

                                </td>

                                <td>

                                    <div class="actions">

                                        <button
                                            type="button"
                                            class="action-btn"
                                            title="Ver pedido"
                                            onclick="verPedido(<?= (int)$pedido["id"] ?>)"
                                        >
                                            👁️
                                        </button>

                                        <a
                                            class="action-btn whatsapp"
                                            title="WhatsApp"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            href="<?= escapar(whatsappUrl($pedido["telefono"])) ?>"
                                        >
                                            💬
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

            <?php endif; ?>

        </section>

    </main>

</div>

<!-- =====================================================
     MODAL PEDIDO
===================================================== -->

<div
    class="modal-overlay"
    id="pedidoModal"
    onclick="cerrarModalDesdeOverlay(event)"
>

    <div
        class="modal"
        onclick="event.stopPropagation()"
    >

        <div class="modal-header">

            <div>

                <h2 id="modalTitulo">
                    Pedido
                </h2>

            </div>

            <button
                type="button"
                class="modal-close"
                onclick="cerrarModal()"
            >
                ✕
            </button>

        </div>

        <div class="modal-body">

            <div
                class="order-grid"
                id="modalInfo"
            >
            </div>

            <h3 class="products-title">
                Productos
            </h3>

            <div
                class="products-list"
                id="modalProductos"
            >

                <div class="loading">
                    Cargando productos...
                </div>

            </div>

            <div class="modal-total">

                <span>
                    Total del pedido
                </span>

                <span id="modalTotal">
                    $0
                </span>

            </div>

            <div class="status-section">

                <label for="modalEstado">
                    Estado del pedido
                </label>

                <div class="status-controls">

                    <select id="modalEstado">

                        <?php foreach ($estadosValidos as $estado): ?>

                            <option value="<?= escapar($estado) ?>">
                                <?= escapar($estado) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="guardarEstado()"
                        id="btnGuardarEstado"
                    >
                        Guardar estado
                    </button>

                </div>

            </div>

        </div>

        <div class="modal-footer">

            <a
                href="#"
                target="_blank"
                rel="noopener noreferrer"
                id="modalWhatsapp"
                class="whatsapp-link"
            >
                💬 Contactar por WhatsApp
            </a>

            <button
                type="button"
                class="btn btn-secondary"
                onclick="cerrarModal()"
            >
                Cerrar
            </button>

        </div>

    </div>

</div>

<!-- =====================================================
     NOTIFICACIÓN
===================================================== -->

<div
    id="notification"
    class="notification"
></div>

<script>

/* =========================================================
   DATOS
========================================================= */

const pedidos = <?= $pedidosJson ?>;

let pedidoActual = null;


/* =========================================================
   SIDEBAR MOBILE
========================================================= */

function toggleSidebar()
{
    const sidebar =
        document.getElementById("sidebar");

    sidebar.classList.toggle("open");
}


/* =========================================================
   VER PEDIDO
========================================================= */

async function verPedido(id)
{
    pedidoActual = pedidos.find(
        pedido =>
            Number(pedido.id) === Number(id)
    );

    if (!pedidoActual)
    {
        mostrarNotificacion(
            "No se encontró el pedido.",
            "error"
        );

        return;
    }

    document.getElementById("modalTitulo").textContent =
        "Pedido #" + pedidoActual.numero_pedido;

    document.getElementById("modalTotal").textContent =
        formatearPrecio(pedidoActual.total);

    document.getElementById("modalEstado").value =
        pedidoActual.estado;


    /* =====================================================
       WHATSAPP
    ===================================================== */

    const whatsappNumero =
        prepararWhatsapp(
            pedidoActual.telefono
        );

    document.getElementById("modalWhatsapp").href =
        "https://wa.me/" + whatsappNumero;


    /* =====================================================
       INFORMACIÓN DEL CLIENTE
    ===================================================== */

    document.getElementById("modalInfo").innerHTML = `

        <div class="info-box">

            <div class="info-label">
                Cliente
            </div>

            <div class="info-value">
                ${escapeHtml(pedidoActual.nombre_cliente)}
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Teléfono
            </div>

            <div class="info-value">
                ${escapeHtml(pedidoActual.telefono)}
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Email
            </div>

            <div class="info-value">
                ${escapeHtml(
                    pedidoActual.email ||
                    "No proporcionado"
                )}
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Ciudad
            </div>

            <div class="info-value">
                ${escapeHtml(pedidoActual.ciudad)}
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Dirección
            </div>

            <div class="info-value">
                ${escapeHtml(pedidoActual.direccion)}
            </div>

        </div>


        <div class="info-box">

            <div class="info-label">
                Fecha
            </div>

            <div class="info-value">
                ${escapeHtml(
                    formatearFechaJS(
                        pedidoActual.created_at
                    )
                )}
            </div>

        </div>


        ${
            pedidoActual.observaciones
            ?
            `

                <div
                    class="info-box"
                    style="grid-column:1/-1;"
                >

                    <div class="info-label">
                        Observaciones
                    </div>

                    <div class="info-value">
                        ${escapeHtml(
                            pedidoActual.observaciones
                        )}
                    </div>

                </div>

            `
            :
            ""
        }

    `;


    /* =====================================================
       PRODUCTOS
    ===================================================== */

    document.getElementById("modalProductos").innerHTML = `

        <div class="loading">
            Cargando productos...
        </div>

    `;


    document
        .getElementById("pedidoModal")
        .classList
        .add("active");


    /* =====================================================
       CONSULTAR DETALLE
    ===================================================== */

    try
    {
        const respuesta = await fetch(
            "pedido_detalle.php?id=" +
            encodeURIComponent(
                pedidoActual.id
            )
        );

        const data =
            await respuesta.json();

        if (!data.success)
        {
            throw new Error(
                data.mensaje ||
                "No se pudieron cargar los productos."
            );
        }

        renderProductos(
            data.productos || []
        );

    }
    catch (error)
    {
        console.error(error);

        document.getElementById(
            "modalProductos"
        ).innerHTML = `

            <div class="loading">
                No se pudieron cargar los productos.
            </div>

        `;

        mostrarNotificacion(
            "Error cargando los productos.",
            "error"
        );
    }
}


/* =========================================================
   RENDER PRODUCTOS
========================================================= */

function renderProductos(productos)
{
    const contenedor =
        document.getElementById(
            "modalProductos"
        );

    if (!productos.length)
    {
        contenedor.innerHTML = `

            <div class="loading">
                Este pedido no tiene productos registrados.
            </div>

        `;

        return;
    }


    let html = `

        <div class="product-row header">

            <div>
                Producto
            </div>

            <div class="product-price">
                Precio
            </div>

            <div class="product-quantity">
                Cantidad
            </div>

            <div class="product-subtotal">
                Subtotal
            </div>

        </div>

    `;


    productos.forEach(producto =>
    {
        html += `

            <div class="product-row">

                <div class="product-name">

                    ${escapeHtml(
                        producto.nombre_producto
                    )}

                </div>


                <div class="product-price">

                    ${formatearPrecio(
                        producto.precio_unitario
                    )}

                </div>


                <div class="product-quantity">

                    x${Number(
                        producto.cantidad
                    )}

                </div>


                <div class="product-subtotal">

                    ${formatearPrecio(
                        producto.subtotal
                    )}

                </div>

            </div>

        `;
    });


    contenedor.innerHTML = html;
}


/* =========================================================
   GUARDAR ESTADO
========================================================= */

async function guardarEstado()
{
    if (!pedidoActual)
    {
        return;
    }

    const nuevoEstado =
        document.getElementById(
            "modalEstado"
        ).value;

    const boton =
        document.getElementById(
            "btnGuardarEstado"
        );

    const estadoAnterior =
        pedidoActual.estado;


    boton.disabled = true;
    boton.textContent = "Guardando...";


    try
    {
        const respuesta = await fetch(
            "pedido_estado.php",
            {
                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body: JSON.stringify({
                    id: Number(
                        pedidoActual.id
                    ),

                    estado: nuevoEstado
                })
            }
        );


        const data =
            await respuesta.json();


        if (!data.success)
        {
            throw new Error(
                data.mensaje ||
                "No se pudo actualizar el estado."
            );
        }


        pedidoActual.estado =
            nuevoEstado;


        const pedidoEnLista =
            pedidos.find(
                pedido =>
                    Number(pedido.id) ===
                    Number(pedidoActual.id)
            );


        if (pedidoEnLista)
        {
            pedidoEnLista.estado =
                nuevoEstado;
        }


        mostrarNotificacion(
            "Estado actualizado correctamente.",
            "success"
        );


        setTimeout(() =>
        {
            window.location.reload();

        }, 700);

    }
    catch (error)
    {
        console.error(error);

        document.getElementById(
            "modalEstado"
        ).value = estadoAnterior;


        mostrarNotificacion(
            error.message ||
            "Ocurrió un error.",
            "error"
        );

    }
    finally
    {
        boton.disabled = false;

        boton.textContent =
            "Guardar estado";
    }
}


/* =========================================================
   CERRAR MODAL
========================================================= */

function cerrarModal()
{
    document
        .getElementById("pedidoModal")
        .classList
        .remove("active");

    pedidoActual = null;
}


function cerrarModalDesdeOverlay(event)
{
    if (
        event.target ===
        document.getElementById(
            "pedidoModal"
        )
    )
    {
        cerrarModal();
    }
}


/* =========================================================
   PRECIO
========================================================= */

function formatearPrecio(valor)
{
    const numero =
        Number(valor) || 0;

    return "$" +
        numero.toLocaleString(
            "es-CO",
            {
                maximumFractionDigits: 0
            }
        );
}


/* =========================================================
   FECHA
========================================================= */

function formatearFechaJS(fecha)
{
    if (!fecha)
    {
        return "-";
    }

    const fechaObj =
        new Date(
            String(fecha).replace(" ", "T")
        );

    if (Number.isNaN(fechaObj.getTime()))
    {
        return fecha;
    }

    return fechaObj.toLocaleString(
        "es-CO",
        {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        }
    );
}


/* =========================================================
   WHATSAPP
========================================================= */

function prepararWhatsapp(telefono)
{
    let numero =
        String(telefono || "")
        .replace(/\D/g, "");


    if (
        numero.length === 10 &&
        numero.startsWith("3")
    )
    {
        numero = "57" + numero;
    }


    return numero;
}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(texto)
{
    return String(texto ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}


/* =========================================================
   NOTIFICACIÓN
========================================================= */

let notificationTimer = null;


function mostrarNotificacion(
    mensaje,
    tipo = "success"
)
{
    const notification =
        document.getElementById(
            "notification"
        );


    notification.textContent =
        mensaje;


    notification.className =
        "notification " +
        tipo +
        " show";


    clearTimeout(
        notificationTimer
    );


    notificationTimer =
        setTimeout(() =>
        {
            notification.classList.remove(
                "show"
            );

        }, 3000);
}


/* =========================================================
   ESC PARA CERRAR MODAL
========================================================= */

document.addEventListener(
    "keydown",
    function(event)
    {
        if (event.key === "Escape")
        {
            cerrarModal();
        }
    }
);


/* =========================================================
   CERRAR SIDEBAR AL HACER CLICK FUERA
========================================================= */

document.addEventListener(
    "click",
    function(event)
    {
        const sidebar =
            document.getElementById(
                "sidebar"
            );

        const mobileButton =
            document.querySelector(
                ".mobile-menu-btn"
            );


        if (
            window.innerWidth <= 800 &&
            sidebar.classList.contains("open") &&
            !sidebar.contains(event.target) &&
            !mobileButton.contains(event.target)
        )
        {
            sidebar.classList.remove(
                "open"
            );
        }
    }
);

</script>

</body>

</html>