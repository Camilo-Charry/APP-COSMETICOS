<?php
session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$mensaje = $_GET["mensaje"] ?? "";
$error = $_GET["error"] ?? "";

/* =========================
   CATEGORÍAS
========================= */
$stmtCategorias = $conexion->query("
    SELECT id, nombre
    FROM categorias
    WHERE estado = 1
    ORDER BY nombre ASC
");

$categorias = $stmtCategorias->fetchAll();

/* =========================
   PRODUCTOS
========================= */
$stmtProductos = $conexion->query("
    SELECT 
        p.*,
        c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias c 
        ON c.id = p.categoria_id
    ORDER BY p.id DESC
");

$productos = $stmtProductos->fetchAll();

/* =========================
   HELPERS
========================= */
function formatoPrecio($precio)
{
    return "$" . number_format((float)$precio, 0, ",", ".");
}

function obtenerEstadoProducto($producto)
{
    if ((int)$producto["estado"] !== 1) {
        return [
            "texto" => "Inactivo",
            "clase" => "inactive"
        ];
    }

    if ((int)$producto["stock"] <= 0) {
        return [
            "texto" => "Agotado",
            "clase" => "danger"
        ];
    }

    if ((int)$producto["stock"] <= 5) {
        return [
            "texto" => "Stock bajo",
            "clase" => "warning"
        ];
    }

    return [
        "texto" => "Activo",
        "clase" => "success"
    ];
}

/* =========================
   ESTADÍSTICAS
========================= */
$totalProductos = count($productos);

$totalActivos = 0;
$totalAgotados = 0;
$totalInactivos = 0;
$totalStock = 0;

foreach ($productos as $producto) {
    $totalStock += (int)$producto["stock"];

    if ((int)$producto["estado"] !== 1) {
        $totalInactivos++;
    } elseif ((int)$producto["stock"] <= 0) {
        $totalAgotados++;
    } else {
        $totalActivos++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Productos | BellaE Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg: #09090b;
            --bg-soft: #0f0f12;
            --panel: #151518;
            --panel-2: #1a1a1f;
            --border: rgba(255,255,255,.08);
            --border-hover: rgba(255,255,255,.14);

            --text: #ffffff;
            --text-soft: #a1a1aa;
            --text-muted: #71717a;

            --pink: #ec4899;
            --purple: #a855f7;

            --green: #22c55e;
            --yellow: #f59e0b;
            --red: #ef4444;

            --sidebar: 250px;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(
                    circle at 80% 10%,
                    rgba(168,85,247,.08),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 20% 90%,
                    rgba(236,72,153,.05),
                    transparent 25%
                ),
                var(--bg);

            color: var(--text);
            font-family: "Inter", sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar);

            padding: 28px 18px;

            background:
                linear-gradient(
                    180deg,
                    #101014 0%,
                    #0b0b0e 100%
                );

            border-right: 1px solid var(--border);

            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;

            padding: 0 10px 30px;
        }

        .brand-logo {
            width: 42px;
            height: 42px;

            border-radius: 13px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--purple)
                );

            box-shadow:
                0 10px 30px rgba(236,72,153,.20);

            overflow: hidden;
        }

        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .brand-text strong {
            display: block;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: .4px;
        }

        .brand-text span {
            display: block;
            margin-top: 3px;

            color: var(--text-muted);
            font-size: 11px;
        }

        .nav-title {
            padding: 0 10px;
            margin: 10px 0 10px;

            color: #52525b;

            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 12px;

            min-height: 46px;
            padding: 0 13px;

            border-radius: 12px;

            color: #a1a1aa;

            font-size: 13px;
            font-weight: 600;

            transition: .2s ease;
        }

        .nav a:hover {
            background: rgba(255,255,255,.045);
            color: white;
        }

        .nav a.active {
            color: white;

            background:
                linear-gradient(
                    135deg,
                    rgba(236,72,153,.16),
                    rgba(168,85,247,.12)
                );

            border: 1px solid rgba(236,72,153,.14);

            box-shadow:
                inset 0 0 20px rgba(236,72,153,.025);
        }

        .nav-icon {
            width: 21px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 17px;
        }

        .sidebar-bottom {
            position: absolute;
            left: 18px;
            right: 18px;
            bottom: 20px;
        }

        .admin-card {
            padding: 13px;

            background: rgba(255,255,255,.035);

            border: 1px solid var(--border);
            border-radius: 14px;
        }

        .admin-card-top {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .admin-avatar {
            width: 34px;
            height: 34px;

            border-radius: 10px;

            display: flex;
            align-items: center;
            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--purple)
                );

            font-size: 13px;
            font-weight: 800;
        }

        .admin-info strong {
            display: block;

            font-size: 12px;
            font-weight: 700;
        }

        .admin-info span {
            display: block;
            margin-top: 2px;

            color: #71717a;
            font-size: 10px;
        }

        .logout {
            display: block;

            margin-top: 12px;
            padding-top: 11px;

            border-top: 1px solid var(--border);

            color: #a1a1aa;

            font-size: 11px;
            font-weight: 600;
        }

        .logout:hover {
            color: #f87171;
        }

        /* =========================
           MAIN
        ========================= */

        .main {
            margin-left: var(--sidebar);
            min-height: 100vh;
            padding: 28px 32px 50px;
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;

            margin-bottom: 28px;
        }

        .page-title h1 {
            font-family: "Playfair Display", serif;

            font-size: 31px;
            line-height: 1.1;
            font-weight: 600;

            letter-spacing: -.5px;
        }

        .page-title p {
            margin-top: 8px;

            color: var(--text-soft);

            font-size: 13px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            min-height: 42px;

            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;

            padding: 0 15px;

            border-radius: 11px;

            border: 1px solid var(--border);

            background: rgba(255,255,255,.035);

            color: #e4e4e7;

            font-size: 12px;
            font-weight: 700;

            cursor: pointer;

            transition: .2s ease;
        }

        .btn:hover {
            background: rgba(255,255,255,.07);
            border-color: var(--border-hover);
            transform: translateY(-1px);
        }

        .btn-primary {
            color: white;

            border-color: transparent;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--purple)
                );

            box-shadow:
                0 8px 25px rgba(236,72,153,.16);
        }

        .btn-primary:hover {
            border-color: transparent;

            box-shadow:
                0 10px 30px rgba(236,72,153,.24);
        }

        /* =========================
           ALERTS
        ========================= */

        .alert {
            display: flex;
            align-items: center;
            gap: 10px;

            margin-bottom: 22px;
            padding: 13px 15px;

            border-radius: 12px;

            font-size: 12px;
            font-weight: 600;
        }

        .alert-success {
            background: rgba(34,197,94,.08);
            border: 1px solid rgba(34,197,94,.16);
            color: #86efac;
        }

        .alert-error {
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.16);
            color: #fca5a5;
        }

        /* =========================
           STATS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;

            margin-bottom: 22px;
        }

        .stat {
            position: relative;
            overflow: hidden;

            min-height: 115px;

            padding: 18px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.045),
                    rgba(255,255,255,.018)
                );

            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .stat::after {
            content: "";

            position: absolute;

            width: 100px;
            height: 100px;

            right: -40px;
            top: -45px;

            border-radius: 50%;

            background: rgba(236,72,153,.07);

            filter: blur(8px);
        }

        .stat-label {
            position: relative;
            z-index: 1;

            color: var(--text-muted);

            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .stat-value {
            position: relative;
            z-index: 1;

            margin-top: 10px;

            font-size: 27px;
            font-weight: 800;

            letter-spacing: -.8px;
        }

        .stat-detail {
            position: relative;
            z-index: 1;

            margin-top: 5px;

            color: #71717a;

            font-size: 10px;
        }

        /* =========================
           TOOLBAR
        ========================= */

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;

            margin-bottom: 14px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .results-count {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
        }

        .search {
            position: relative;

            width: 300px;
        }

        .search span {
            position: absolute;
            left: 13px;
            top: 50%;

            transform: translateY(-50%);

            color: #71717a;

            font-size: 15px;

            pointer-events: none;
        }

        .search input {
            width: 100%;
            height: 42px;

            padding: 0 13px 0 38px;

            border: 1px solid var(--border);
            border-radius: 11px;

            outline: none;

            background: rgba(255,255,255,.035);

            color: white;

            font-size: 12px;

            transition: .2s;
        }

        .search input::placeholder {
            color: #52525b;
        }

        .search input:focus {
            border-color: rgba(236,72,153,.35);

            box-shadow:
                0 0 0 3px rgba(236,72,153,.06);
        }

        /* =========================
           TABLE
        ========================= */

        .panel {
            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,.035),
                    rgba(255,255,255,.015)
                );

            border: 1px solid var(--border);
            border-radius: 18px;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;

            min-width: 900px;
        }

        thead {
            background: rgba(255,255,255,.025);
        }

        th {
            padding: 14px 18px;

            color: #71717a;

            font-size: 10px;
            font-weight: 800;

            text-align: left;

            text-transform: uppercase;
            letter-spacing: .8px;

            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 14px 18px;

            border-bottom: 1px solid rgba(255,255,255,.055);

            vertical-align: middle;

            font-size: 12px;
        }

        tbody tr {
            transition: .18s ease;
        }

        tbody tr:hover {
            background: rgba(255,255,255,.025);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 52px;
            height: 52px;

            flex-shrink: 0;

            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #202024,
                    #111114
                );

            border: 1px solid var(--border);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-placeholder {
            color: #52525b;
            font-size: 20px;
        }

        .product-name {
            font-weight: 700;
            color: #f4f4f5;
        }

        .product-id {
            margin-top: 3px;

            color: #52525b;

            font-size: 9px;
        }

        .category {
            color: #a1a1aa;
            font-weight: 600;
        }

        .price {
            color: white;
            font-weight: 800;
        }

        .stock-wrap {
            min-width: 100px;
        }

        .stock-number {
            font-weight: 700;
        }

        .stock-bar {
            width: 75px;
            height: 4px;

            margin-top: 7px;

            overflow: hidden;

            background: #27272a;
            border-radius: 99px;
        }

        .stock-fill {
            height: 100%;

            border-radius: inherit;

            background:
                linear-gradient(
                    90deg,
                    var(--pink),
                    var(--purple)
                );
        }

        .stock-fill.low {
            background: var(--yellow);
        }

        .stock-fill.empty {
            width: 0 !important;
            background: var(--red);
        }

        .badge {
            display: inline-flex;
            align-items: center;

            padding: 6px 9px;

            border-radius: 8px;

            font-size: 9px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .badge.success {
            color: #86efac;
            background: rgba(34,197,94,.09);
            border: 1px solid rgba(34,197,94,.12);
        }

        .badge.warning {
            color: #fcd34d;
            background: rgba(245,158,11,.09);
            border: 1px solid rgba(245,158,11,.12);
        }

        .badge.danger {
            color: #fca5a5;
            background: rgba(239,68,68,.09);
            border: 1px solid rgba(239,68,68,.12);
        }

        .badge.inactive {
            color: #a1a1aa;
            background: rgba(161,161,170,.08);
            border: 1px solid rgba(161,161,170,.10);
        }

        .actions {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-btn {
            width: 34px;
            height: 34px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 9px;

            border: 1px solid var(--border);

            background: rgba(255,255,255,.025);

            color: #a1a1aa;

            cursor: pointer;

            transition: .2s;
        }

        .action-btn:hover {
            color: white;
            background: rgba(255,255,255,.07);
            border-color: var(--border-hover);
        }

        .action-btn.delete:hover {
            color: #fca5a5;
            border-color: rgba(239,68,68,.20);
            background: rgba(239,68,68,.07);
        }

        /* =========================
           EMPTY
        ========================= */

        .empty {
            padding: 70px 20px;

            text-align: center;
        }

        .empty-icon {
            width: 65px;
            height: 65px;

            margin: 0 auto 15px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 18px;

            background: rgba(255,255,255,.04);

            font-size: 27px;
        }

        .empty h3 {
            font-size: 15px;
        }

        .empty p {
            margin-top: 6px;

            color: var(--text-muted);

            font-size: 11px;
        }

        /* =========================
           MODAL
        ========================= */

        .modal {
            position: fixed;
            inset: 0;

            display: none;
            align-items: center;
            justify-content: center;

            padding: 20px;

            background: rgba(0,0,0,.78);

            backdrop-filter: blur(8px);

            z-index: 500;
        }

        .modal.show {
            display: flex;
        }

        .modal-box {
            width: min(650px, 100%);
            max-height: 90vh;

            overflow-y: auto;

            background:
                linear-gradient(
                    145deg,
                    #18181b,
                    #111114
                );

            border: 1px solid rgba(255,255,255,.10);

            border-radius: 20px;

            box-shadow:
                0 30px 80px rgba(0,0,0,.55);
        }

        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 21px 23px;

            border-bottom: 1px solid var(--border);
        }

        .modal-header h2 {
            font-family: "Playfair Display", serif;

            font-size: 21px;
            font-weight: 600;
        }

        .modal-header p {
            margin-top: 4px;

            color: #71717a;
            font-size: 10px;
        }

        .close {
            width: 35px;
            height: 35px;

            border-radius: 9px;

            border: 1px solid var(--border);

            background: rgba(255,255,255,.035);

            color: #a1a1aa;

            cursor: pointer;

            font-size: 18px;
        }

        .close:hover {
            color: white;
            background: rgba(255,255,255,.07);
        }

        .modal-body {
            padding: 22px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            color: #a1a1aa;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;
            letter-spacing: .7px;
        }

        .form-control {
            width: 100%;
            height: 43px;

            padding: 0 12px;

            border-radius: 10px;

            border: 1px solid var(--border);

            background: #0f0f12;

            color: white;

            outline: none;

            font-size: 12px;

            transition: .2s;
        }

        textarea.form-control {
            height: 95px;
            padding: 12px;

            resize: vertical;
        }

        .form-control:focus {
            border-color: rgba(236,72,153,.38);

            box-shadow:
                0 0 0 3px rgba(236,72,153,.055);
        }

        .form-control option {
            background: #151518;
            color: white;
        }

        .file-info {
            color: #52525b;
            font-size: 9px;
            line-height: 1.5;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 9px;

            padding: 17px 22px;

            border-top: 1px solid var(--border);
        }

        /* =========================
           MOBILE
        ========================= */

        @media (max-width: 1100px) {
            .stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 850px) {
            :root {
                --sidebar: 0px;
            }

            .sidebar {
                position: static;

                width: 100%;
                height: auto;

                padding: 15px;

                border-right: none;
                border-bottom: 1px solid var(--border);
            }

            .brand {
                padding: 0 5px 15px;
            }

            .nav-title,
            .sidebar-bottom {
                display: none;
            }

            .nav {
                flex-direction: row;
                overflow-x: auto;

                padding-bottom: 2px;
            }

            .nav a {
                min-width: max-content;
            }

            .main {
                margin-left: 0;
                padding: 24px 18px 40px;
            }
        }

        @media (max-width: 650px) {
            .topbar {
                flex-direction: column;
            }

            .top-actions {
                width: 100%;
            }

            .top-actions .btn {
                flex: 1;
            }

            .stats {
                grid-template-columns: 1fr 1fr;
                gap: 9px;
            }

            .stat {
                min-height: 100px;
                padding: 14px;
            }

            .stat-value {
                font-size: 22px;
            }

            .toolbar {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .search {
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .page-title h1 {
                font-size: 27px;
            }
        }

        @media (max-width: 420px) {
            .stats {
                grid-template-columns: 1fr;
            }

            .main {
                padding-left: 13px;
                padding-right: 13px;
            }
        }
    </style>
</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar">

    <div class="brand">

        <div class="brand-logo">
            <?php if (file_exists("../assets/img/logo.png")): ?>
                <img src="../assets/img/logo.png" alt="BellaE">
            <?php else: ?>
                ✨
            <?php endif; ?>
        </div>

        <div class="brand-text">
            <strong>BellaE</strong>
            <span>Panel administrativo</span>
        </div>

    </div>

    <div class="nav-title">
        Principal
    </div>

    <nav class="nav">

        <a href="index.php">
            <span class="nav-icon">⌂</span>
            Dashboard
        </a>

        <a href="productos.php" class="active">
            <span class="nav-icon">◈</span>
            Productos
        </a>

        <a href="categorias.php">
            <span class="nav-icon">▦</span>
            Categorías
        </a>

        <a href="pedidos.php">
            <span class="nav-icon">◫</span>
            Pedidos
        </a>

    </nav>

    <div class="nav-title" style="margin-top:25px;">
        Tienda
    </div>

    <nav class="nav">

        <a href="../index.php" target="_blank">
            <span class="nav-icon">↗</span>
            Ver tienda
        </a>

    </nav>

    <div class="sidebar-bottom">

        <div class="admin-card">

            <div class="admin-card-top">

                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION["admin_nombre"] ?? "A", 0, 1)) ?>
                </div>

                <div class="admin-info">
                    <strong>
                        <?= htmlspecialchars($_SESSION["admin_nombre"] ?? "Administrador") ?>
                    </strong>

                    <span>
                        Administrador
                    </span>
                </div>

            </div>

            <a href="logout.php" class="logout">
                Cerrar sesión →
            </a>

        </div>

    </div>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <div class="topbar">

        <div class="page-title">
            <h1>Productos</h1>

            <p>
                Administra el catálogo, precios, inventario y disponibilidad.
            </p>
        </div>

        <div class="top-actions">

            <a
                href="../index.php"
                target="_blank"
                class="btn"
            >
                ↗ Ver tienda
            </a>

            <button
                type="button"
                class="btn btn-primary"
                onclick="abrirModal()"
            >
                ＋ Nuevo producto
            </button>

        </div>

    </div>


    <?php if ($mensaje): ?>

        <div class="alert alert-success">
            ✓ <?= htmlspecialchars($mensaje) ?>
        </div>

    <?php endif; ?>


    <?php if ($error): ?>

        <div class="alert alert-error">
            ⚠ <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <!-- =========================
         STATS
    ========================= -->

    <section class="stats">

        <div class="stat">
            <div class="stat-label">
                Total productos
            </div>

            <div class="stat-value">
                <?= $totalProductos ?>
            </div>

            <div class="stat-detail">
                Productos registrados
            </div>
        </div>


        <div class="stat">
            <div class="stat-label">
                Activos
            </div>

            <div class="stat-value">
                <?= $totalActivos ?>
            </div>

            <div class="stat-detail">
                Disponibles en la tienda
            </div>
        </div>


        <div class="stat">
            <div class="stat-label">
                Agotados
            </div>

            <div class="stat-value">
                <?= $totalAgotados ?>
            </div>

            <div class="stat-detail">
                Requieren reposición
            </div>
        </div>


        <div class="stat">
            <div class="stat-label">
                Inventario
            </div>

            <div class="stat-value">
                <?= number_format($totalStock, 0, ",", ".") ?>
            </div>

            <div class="stat-detail">
                Unidades disponibles
            </div>
        </div>

    </section>


    <!-- =========================
         TOOLBAR
    ========================= -->

    <div class="toolbar">

        <div class="toolbar-left">

            <span class="results-count">
                Mostrando <?= $totalProductos ?> productos
            </span>

        </div>

        <div class="search">

            <span>⌕</span>

            <input
                type="text"
                id="buscarProducto"
                placeholder="Buscar producto..."
                autocomplete="off"
            >

        </div>

    </div>


    <!-- =========================
         PRODUCT TABLE
    ========================= -->

    <section class="panel">

        <div class="table-wrapper">

            <?php if (empty($productos)): ?>

                <div class="empty">

                    <div class="empty-icon">
                        ✨
                    </div>

                    <h3>
                        No hay productos
                    </h3>

                    <p>
                        Empieza agregando el primer producto de tu tienda.
                    </p>

                    <br>

                    <button
                        type="button"
                        class="btn btn-primary"
                        onclick="abrirModal()"
                    >
                        ＋ Crear producto
                    </button>

                </div>

            <?php else: ?>

                <table>

                    <thead>

                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>

                    </thead>

                    <tbody id="productosBody">

                    <?php foreach ($productos as $producto): ?>

                        <?php
                        $estado = obtenerEstadoProducto($producto);

                        $stock = (int)$producto["stock"];

                        $porcentajeStock = min(
                            100,
                            max(
                                0,
                                $stock * 4
                            )
                        );

                        $stockClase = "";

                        if ($stock <= 0) {
                            $stockClase = "empty";
                        } elseif ($stock <= 5) {
                            $stockClase = "low";
                        }

                        $searchText = strtolower(
                            ($producto["nombre"] ?? "") . " " .
                            ($producto["categoria_nombre"] ?? "")
                        );

                        $productoJson = htmlspecialchars(
                            json_encode(
                                $producto,
                                JSON_HEX_TAG |
                                JSON_HEX_AMP |
                                JSON_HEX_APOS |
                                JSON_HEX_QUOT |
                                JSON_UNESCAPED_UNICODE
                            ),
                            ENT_QUOTES,
                            "UTF-8"
                        );
                        ?>

                        <tr
                            class="product-row"
                            data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>"
                        >

                            <!-- PRODUCTO -->

                            <td>

                                <div class="product-info">

                                    <div class="product-image">

                                        <?php
                                        $imagen = trim($producto["imagen"] ?? "");

                                        $rutaImagen = "../assets/img/productos/" . $imagen;

                                        if (
                                            $imagen !== "" &&
                                            file_exists($rutaImagen)
                                        ):
                                        ?>

                                            <img
                                                src="<?= htmlspecialchars($rutaImagen) ?>"
                                                alt="<?= htmlspecialchars($producto["nombre"]) ?>"
                                            >

                                        <?php else: ?>

                                            <div class="product-placeholder">
                                                ✨
                                            </div>

                                        <?php endif; ?>

                                    </div>

                                    <div>

                                        <div class="product-name">
                                            <?= htmlspecialchars($producto["nombre"]) ?>
                                        </div>

                                        <div class="product-id">
                                            ID #<?= (int)$producto["id"] ?>
                                        </div>

                                    </div>

                                </div>

                            </td>


                            <!-- CATEGORIA -->

                            <td>

                                <span class="category">

                                    <?= htmlspecialchars(
                                        $producto["categoria_nombre"] ?? "Sin categoría"
                                    ) ?>

                                </span>

                            </td>


                            <!-- PRECIO -->

                            <td>

                                <span class="price">

                                    <?= formatoPrecio($producto["precio"]) ?>

                                </span>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <div class="stock-wrap">

                                    <div class="stock-number">
                                        <?= $stock ?> unidades
                                    </div>

                                    <div class="stock-bar">

                                        <div
                                            class="stock-fill <?= $stockClase ?>"
                                            style="width: <?= $porcentajeStock ?>%;"
                                        ></div>

                                    </div>

                                </div>

                            </td>


                            <!-- ESTADO -->

                            <td>

                                <span class="badge <?= $estado["clase"] ?>">

                                    <?= $estado["texto"] ?>

                                </span>

                            </td>


                            <!-- ACCIONES -->

                            <td>

                                <div class="actions">

                                    <button
                                        type="button"
                                        class="action-btn"
                                        title="Editar producto"
                                        data-product="<?= $productoJson ?>"
                                        onclick="editarProducto(this)"
                                    >
                                        ✎
                                    </button>

                                    <a
                                        href="producto_eliminar.php?id=<?= (int)$producto["id"] ?>"
                                        class="action-btn delete"
                                        title="Desactivar producto"
                                        onclick="return confirmarEliminar('<?= htmlspecialchars(addslashes($producto["nombre"])) ?>')"
                                    >
                                        ×
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            <?php endif; ?>

        </div>

    </section>

</main>


<!-- =========================
     MODAL PRODUCTO
========================= -->

<div
    class="modal"
    id="productoModal"
    onclick="cerrarModalExterior(event)"
>

    <div
        class="modal-box"
        onclick="event.stopPropagation()"
    >

        <div class="modal-header">

            <div>

                <h2 id="modalTitulo">
                    Nuevo producto
                </h2>

                <p>
                    Completa la información del producto.
                </p>

            </div>

            <button
                type="button"
                class="close"
                onclick="cerrarModal()"
            >
                ×
            </button>

        </div>


        <form
            action="producto_guardar.php"
            method="POST"
            enctype="multipart/form-data"
        >

            <div class="modal-body">

                <input
                    type="hidden"
                    name="id"
                    id="producto_id"
                    value=""
                >


                <div class="form-grid">

                    <!-- NOMBRE -->

                    <div class="form-group full">

                        <label for="nombre">
                            Nombre del producto
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            name="nombre"
                            id="nombre"
                            placeholder="Ej: Labial Matte"
                            required
                        >

                    </div>


                    <!-- CATEGORIA -->

                    <div class="form-group">

                        <label for="categoria_id">
                            Categoría
                        </label>

                        <select
                            class="form-control"
                            name="categoria_id"
                            id="categoria_id"
                            required
                        >

                            <option value="">
                                Seleccionar categoría
                            </option>

                            <?php foreach ($categorias as $categoria): ?>

                                <option
                                    value="<?= (int)$categoria["id"] ?>"
                                >
                                    <?= htmlspecialchars($categoria["nombre"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>


                    <!-- PRECIO -->

                    <div class="form-group">

                        <label for="precio">
                            Precio
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="precio"
                            id="precio"
                            min="0"
                            step="0.01"
                            placeholder="35000"
                            required
                        >

                    </div>


                    <!-- STOCK -->

                    <div class="form-group">

                        <label for="stock">
                            Stock
                        </label>

                        <input
                            type="number"
                            class="form-control"
                            name="stock"
                            id="stock"
                            min="0"
                            step="1"
                            placeholder="25"
                            required
                        >

                    </div>


                    <!-- ESTADO -->

                    <div class="form-group">

                        <label for="estado">
                            Estado
                        </label>

                        <select
                            class="form-control"
                            name="estado"
                            id="estado"
                            required
                        >

                            <option value="1">
                                Activo
                            </option>

                            <option value="0">
                                Inactivo
                            </option>

                        </select>

                    </div>


                    <!-- IMAGEN -->

                    <div class="form-group full">

                        <label for="imagen">
                            Imagen del producto
                        </label>

                        <input
                            type="file"
                            class="form-control"
                            name="imagen"
                            id="imagen"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <div class="file-info">
                            Formatos permitidos: JPG, PNG o WEBP.
                            Máximo 5 MB.
                        </div>

                    </div>


                    <!-- DESCRIPCION -->

                    <div class="form-group full">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            class="form-control"
                            name="descripcion"
                            id="descripcion"
                            placeholder="Describe el producto..."
                        ></textarea>

                    </div>

                </div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="btn"
                    onclick="cerrarModal()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn btn-primary"
                    id="btnGuardar"
                >
                    Guardar producto
                </button>

            </div>

        </form>

    </div>

</div>


<script>

/* =========================
   MODAL
========================= */

const modal = document.getElementById("productoModal");

function abrirModal() {

    limpiarFormulario();

    document.getElementById("modalTitulo").textContent =
        "Nuevo producto";

    document.getElementById("btnGuardar").textContent =
        "Guardar producto";

    modal.classList.add("show");

    setTimeout(() => {
        document.getElementById("nombre").focus();
    }, 100);
}


function cerrarModal() {
    modal.classList.remove("show");
}


function cerrarModalExterior(event) {

    if (event.target === modal) {
        cerrarModal();
    }

}


function limpiarFormulario() {

    document.getElementById("producto_id").value = "";
    document.getElementById("nombre").value = "";
    document.getElementById("categoria_id").value = "";
    document.getElementById("precio").value = "";
    document.getElementById("stock").value = "";
    document.getElementById("estado").value = "1";
    document.getElementById("imagen").value = "";
    document.getElementById("descripcion").value = "";

}


/* =========================
   EDITAR PRODUCTO
========================= */

function editarProducto(button) {

    let producto;

    try {

        producto = JSON.parse(
            button.getAttribute("data-product")
        );

    } catch (error) {

        console.error(error);

        alert("No se pudo cargar la información del producto.");

        return;
    }


    document.getElementById("producto_id").value =
        producto.id ?? "";

    document.getElementById("nombre").value =
        producto.nombre ?? "";

    document.getElementById("categoria_id").value =
        producto.categoria_id ?? "";

    document.getElementById("precio").value =
        producto.precio ?? "";

    document.getElementById("stock").value =
        producto.stock ?? 0;

    document.getElementById("estado").value =
        producto.estado ?? 1;

    document.getElementById("descripcion").value =
        producto.descripcion ?? "";

    document.getElementById("imagen").value = "";


    document.getElementById("modalTitulo").textContent =
        "Editar producto";

    document.getElementById("btnGuardar").textContent =
        "Actualizar producto";


    modal.classList.add("show");

    setTimeout(() => {
        document.getElementById("nombre").focus();
    }, 100);

}


/* =========================
   BUSCADOR
========================= */

const buscador = document.getElementById("buscarProducto");

if (buscador) {

    buscador.addEventListener("input", function () {

        const texto = this.value
            .toLowerCase()
            .trim();

        const filas = document.querySelectorAll(".product-row");

        let visibles = 0;

        filas.forEach(fila => {

            const contenido =
                fila.dataset.search || "";

            if (contenido.includes(texto)) {

                fila.style.display = "";

                visibles++;

            } else {

                fila.style.display = "none";

            }

        });


        const contador =
            document.querySelector(".results-count");

        if (contador) {

            contador.textContent =
                `Mostrando ${visibles} productos`;

        }

    });

}


/* =========================
   CONFIRMAR DESACTIVACIÓN
========================= */

function confirmarEliminar(nombre) {

    return confirm(
        `¿Seguro que deseas desactivar "${nombre}"?\n\nEl producto dejará de estar disponible en la tienda.`
    );

}


/* =========================
   ESC
========================= */

document.addEventListener("keydown", function(event) {

    if (event.key === "Escape") {
        cerrarModal();
    }

});


/* =========================
   BLOQUEAR SCROLL CON MODAL
========================= */

const observer = new MutationObserver(() => {

    if (modal.classList.contains("show")) {

        document.body.style.overflow = "hidden";

    } else {

        document.body.style.overflow = "";

    }

});

observer.observe(modal, {
    attributes: true,
    attributeFilter: ["class"]
});

</script>

</body>
</html>