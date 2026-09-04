<?php

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

require_once "../config/database.php";

$mensaje = $_GET["mensaje"] ?? "";
$error = $_GET["error"] ?? "";

/*
|--------------------------------------------------------------------------
| CONSULTAR CATEGORÍAS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.nombre,
        c.descripcion,
        c.estado,
        c.created_at,
        COUNT(p.id) AS total_productos
    FROM categorias c
    LEFT JOIN productos p
        ON p.categoria_id = c.id
    GROUP BY
        c.id,
        c.nombre,
        c.descripcion,
        c.estado,
        c.created_at
    ORDER BY c.id DESC
";

$stmt = $conexion->query($sql);
$categorias = $stmt->fetchAll();

$totalCategorias = count($categorias);
$categoriasActivas = 0;
$categoriasInactivas = 0;
$totalProductos = 0;

foreach ($categorias as $categoria) {

    if ((int)$categoria["estado"] === 1) {
        $categoriasActivas++;
    } else {
        $categoriasInactivas++;
    }

    $totalProductos += (int)$categoria["total_productos"];
}

function e($texto)
{
    return htmlspecialchars(
        (string)$texto,
        ENT_QUOTES,
        "UTF-8"
    );
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

    <title>Categorías | Administración</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #09090b;
            --bg-soft: #0f0f12;
            --panel: #151518;
            --panel-2: #19191d;
            --border: rgba(255,255,255,.08);
            --text: #f7f7f8;
            --muted: #96969f;
            --muted-2: #6f6f78;
            --pink: #ec4899;
            --purple: #a855f7;
        }

        body {
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background:
                radial-gradient(
                    circle at 80% 0%,
                    rgba(168,85,247,.08),
                    transparent 28%
                ),
                radial-gradient(
                    circle at 20% 20%,
                    rgba(236,72,153,.05),
                    transparent 25%
                ),
                var(--bg);

            color: var(--text);
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        button,
        input,
        textarea {
            font: inherit;
        }

        /* =========================================================
           SIDEBAR
        ========================================================= */

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;

            width: 250px;
            height: 100vh;

            background:
                linear-gradient(
                    180deg,
                    #111114 0%,
                    #0c0c0e 100%
                );

            border-right: 1px solid var(--border);

            padding: 28px 18px;

            z-index: 100;
        }

        .brand {
            padding: 0 12px 30px;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 900;
            letter-spacing: -1px;
        }

        .brand-name span {
            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--purple)
                );

            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .brand small {
            display: block;
            margin-top: 5px;

            color: var(--muted-2);

            font-size: 12px;
        }

        .menu-title {
            padding: 0 12px;

            margin: 20px 0 9px;

            color: #5e5e67;

            font-size: 10px;
            font-weight: 900;

            letter-spacing: 1.5px;
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

            padding: 12px 14px;

            border-radius: 12px;

            color: #92929b;

            font-size: 14px;
            font-weight: 650;

            transition: .2s;
        }

        .menu a:hover {
            background: rgba(255,255,255,.045);
            color: white;
        }

        .menu a.active {
            background:
                linear-gradient(
                    135deg,
                    rgba(236,72,153,.95),
                    rgba(168,85,247,.95)
                );

            color: white;

            box-shadow:
                0 10px 30px rgba(168,85,247,.16);
        }

        .menu-icon {
            width: 22px;
            text-align: center;
            font-size: 17px;
        }

        .sidebar-bottom {
            position: absolute;

            bottom: 25px;
            left: 18px;
            right: 18px;
        }

        .view-store {
            display: flex;
            align-items: center;
            justify-content: center;

            padding: 11px;

            border: 1px solid var(--border);
            border-radius: 12px;

            color: #aaaab2;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 10px;

            transition: .2s;
        }

        .view-store:hover {
            background: rgba(255,255,255,.04);
            color: white;
        }

        .logout {
            display: flex;
            align-items: center;
            justify-content: center;

            padding: 11px;

            border-radius: 12px;

            background: rgba(236,72,153,.07);

            color: #f472b6;

            font-size: 13px;
            font-weight: 700;

            transition: .2s;
        }

        .logout:hover {
            background: rgba(236,72,153,.12);
        }

        /* =========================================================
           MAIN
        ========================================================= */

        .main {
            margin-left: 250px;

            min-height: 100vh;

            padding: 35px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;

            gap: 20px;

            margin-bottom: 28px;
        }

        .eyebrow {
            color: #e45c9d;

            font-size: 11px;
            font-weight: 900;

            letter-spacing: 1.7px;

            margin-bottom: 7px;
        }

        .topbar h1 {
            font-size: 32px;

            letter-spacing: -1.2px;

            line-height: 1.1;
        }

        .topbar p {
            margin-top: 8px;

            color: var(--muted);

            font-size: 14px;
        }

        .btn-primary {
            border: 0;

            cursor: pointer;

            padding: 13px 19px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    var(--pink),
                    var(--purple)
                );

            color: white;

            font-size: 13px;
            font-weight: 850;

            box-shadow:
                0 10px 25px rgba(168,85,247,.17);

            transition: .2s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);

            box-shadow:
                0 14px 30px rgba(168,85,247,.25);
        }

        /* =========================================================
           ALERTAS
        ========================================================= */

        .alert {
            padding: 14px 17px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 13px;
            font-weight: 700;

            border: 1px solid;
        }

        .alert-success {
            background: rgba(34,197,94,.08);
            color: #86efac;
            border-color: rgba(34,197,94,.18);
        }

        .alert-error {
            background: rgba(239,68,68,.08);
            color: #fca5a5;
            border-color: rgba(239,68,68,.18);
        }

        /* =========================================================
           STATS
        ========================================================= */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(3, minmax(0, 1fr));

            gap: 18px;

            margin-bottom: 24px;
        }

        .stat-card {
            position: relative;

            overflow: hidden;

            background:
                linear-gradient(
                    145deg,
                    #17171b,
                    #121215
                );

            border: 1px solid var(--border);

            border-radius: 17px;

            padding: 21px;

            box-shadow:
                0 15px 45px rgba(0,0,0,.18);
        }

        .stat-card::after {
            content: "";

            position: absolute;

            width: 80px;
            height: 80px;

            right: -25px;
            bottom: -30px;

            background:
                radial-gradient(
                    circle,
                    rgba(236,72,153,.13),
                    transparent 70%
                );

            pointer-events: none;
        }

        .stat-label {
            color: var(--muted);

            font-size: 11px;
            font-weight: 800;

            letter-spacing: .5px;
        }

        .stat-number {
            margin-top: 7px;

            font-size: 30px;
            font-weight: 900;

            letter-spacing: -1px;
        }

        .stat-card:nth-child(2) .stat-number {
            color: #86efac;
        }

        .stat-card:nth-child(3) .stat-number {
            color: #fbbf24;
        }

        /* =========================================================
           CONTENT CARD
        ========================================================= */

        .content-card {
            background:
                linear-gradient(
                    145deg,
                    #151518,
                    #111114
                );

            border: 1px solid var(--border);

            border-radius: 18px;

            box-shadow:
                0 18px 55px rgba(0,0,0,.22);

            overflow: hidden;
        }

        .content-header {
            display: flex;

            justify-content: space-between;
            align-items: center;

            padding: 21px 23px;

            border-bottom: 1px solid var(--border);

            gap: 15px;
        }

        .content-header h2 {
            font-size: 17px;
            font-weight: 800;
        }

        .search {
            width: 280px;

            border: 1px solid rgba(255,255,255,.09);

            background: #0e0e11;

            color: white;

            border-radius: 10px;

            padding: 11px 13px;

            outline: none;

            font-size: 13px;

            transition: .2s;
        }

        .search::placeholder {
            color: #64646c;
        }

        .search:focus {
            border-color: rgba(236,72,153,.5);

            box-shadow:
                0 0 0 3px rgba(236,72,153,.08);
        }

        /* =========================================================
           TABLE
        ========================================================= */

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;

            border-collapse: collapse;
        }

        th {
            background: rgba(255,255,255,.018);

            color: #707079;

            font-size: 10px;
            font-weight: 900;

            letter-spacing: .8px;

            text-transform: uppercase;

            padding: 13px 18px;

            text-align: left;

            white-space: nowrap;
        }

        td {
            padding: 17px 18px;

            border-top: 1px solid rgba(255,255,255,.055);

            font-size: 13px;

            vertical-align: middle;
        }

        tbody tr {
            transition: .18s;
        }

        tbody tr:hover {
            background: rgba(255,255,255,.025);
        }

        .category-name {
            color: #f5f5f6;

            font-weight: 800;
        }

        .category-description {
            color: #85858e;

            max-width: 320px;

            line-height: 1.45;
        }

        .count {
            display: inline-flex;
            align-items: center;

            padding: 6px 10px;

            border-radius: 999px;

            background: rgba(168,85,247,.10);

            border: 1px solid rgba(168,85,247,.13);

            color: #c084fc;

            font-size: 11px;
            font-weight: 800;

            white-space: nowrap;
        }

        .badge {
            display: inline-flex;

            padding: 6px 10px;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 900;

            letter-spacing: .3px;
        }

        .badge.active {
            background: rgba(34,197,94,.10);

            color: #86efac;

            border: 1px solid rgba(34,197,94,.15);
        }

        .badge.inactive {
            background: rgba(255,255,255,.055);

            color: #9999a2;

            border: 1px solid rgba(255,255,255,.07);
        }

        .actions {
            display: flex;
            gap: 7px;
        }

        .action-btn {
            border: 0;

            cursor: pointer;

            padding: 8px 11px;

            border-radius: 9px;

            font-size: 11px;
            font-weight: 800;

            transition: .18s;

            white-space: nowrap;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .edit-btn {
            background: rgba(168,85,247,.10);

            color: #c084fc;

            border: 1px solid rgba(168,85,247,.12);
        }

        .edit-btn:hover {
            background: rgba(168,85,247,.17);
        }

        .toggle-btn {
            background: rgba(236,72,153,.08);

            color: #f472b6;

            border: 1px solid rgba(236,72,153,.12);
        }

        .toggle-btn:hover {
            background: rgba(236,72,153,.15);
        }

        .empty {
            text-align: center;

            padding: 70px 20px;

            color: #85858e;
        }

        .empty-icon {
            font-size: 42px;

            margin-bottom: 12px;
        }

        .empty h3 {
            color: #e7e7e9;

            font-size: 17px;

            margin-bottom: 6px;
        }

        .empty p {
            font-size: 13px;
        }

        /* =========================================================
           MODAL
        ========================================================= */

        .modal-overlay {
            position: fixed;

            inset: 0;

            display: none;

            align-items: center;
            justify-content: center;

            padding: 20px;

            background:
                rgba(0,0,0,.72);

            backdrop-filter: blur(7px);

            z-index: 1000;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal {
            width: 100%;

            max-width: 520px;

            background:
                linear-gradient(
                    145deg,
                    #19191d,
                    #111114
                );

            border: 1px solid rgba(255,255,255,.09);

            border-radius: 20px;

            padding: 26px;

            box-shadow:
                0 30px 90px rgba(0,0,0,.55);

            animation: modalIn .2s ease;
        }

        @keyframes modalIn {

            from {
                opacity: 0;
                transform: translateY(10px) scale(.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }

        }

        .modal-header {
            display: flex;

            justify-content: space-between;
            align-items: flex-start;

            margin-bottom: 22px;
        }

        .modal-header h2 {
            font-size: 21px;
            font-weight: 850;
        }

        .modal-header p {
            margin-top: 5px;

            color: #7d7d86;

            font-size: 12px;
        }

        .close {
            width: 35px;
            height: 35px;

            border: 1px solid rgba(255,255,255,.07);

            border-radius: 10px;

            background: rgba(255,255,255,.045);

            cursor: pointer;

            font-size: 17px;

            color: #aaaab2;

            transition: .2s;
        }

        .close:hover {
            background: rgba(255,255,255,.08);

            color: white;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;

            margin-bottom: 7px;

            font-size: 12px;
            font-weight: 800;

            color: #d4d4d8;
        }

        .optional {
            color: #777780;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;

            border: 1px solid rgba(255,255,255,.09);

            background: #0d0d10;

            color: white;

            border-radius: 11px;

            padding: 12px 13px;

            outline: none;

            font-size: 13px;

            resize: vertical;

            transition: .2s;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #5f5f67;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: rgba(236,72,153,.55);

            box-shadow:
                0 0 0 3px rgba(236,72,153,.08);
        }

        .modal-actions {
            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 22px;
        }

        .btn-secondary {
            border: 1px solid rgba(255,255,255,.09);

            background: rgba(255,255,255,.035);

            color: #aaaab2;

            padding: 11px 17px;

            border-radius: 10px;

            cursor: pointer;

            font-size: 12px;
            font-weight: 800;

            transition: .2s;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,.07);
            color: white;
        }

        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .sidebar {
                width: 215px;
            }

            .main {
                margin-left: 215px;
                padding: 25px;
            }

            .stats {
                grid-template-columns:
                    repeat(3, minmax(0, 1fr));
            }

        }

        @media (max-width: 760px) {

            .sidebar {
                position: relative;

                width: 100%;
                height: auto;

                padding: 18px;
            }

            .brand {
                padding-bottom: 15px;
            }

            .menu-title {
                margin-top: 12px;
            }

            .menu {
                display: grid;

                grid-template-columns:
                    repeat(2, 1fr);
            }

            .sidebar-bottom {
                position: static;

                margin-top: 15px;
            }

            .main {
                margin-left: 0;

                padding: 20px;
            }

            .topbar {
                flex-direction: column;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .content-header {
                flex-direction: column;

                align-items: stretch;
            }

            .search {
                width: 100%;
            }

        }

        @media (max-width: 500px) {

            .main {
                padding: 15px;
            }

            .menu {
                grid-template-columns: 1fr;
            }

            .topbar h1 {
                font-size: 27px;
            }

            .btn-primary {
                width: 100%;
            }

            .modal {
                padding: 20px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .modal-actions button {
                width: 100%;
            }

        }

    </style>

</head>

<body>

    <!-- =========================================================
         SIDEBAR
    ========================================================== -->

    <aside class="sidebar">

        <div class="brand">

            <div class="brand-name">
                COSME<span>TICOS</span>
            </div>

            <small>
                Panel administrativo
            </small>

        </div>

        <div class="menu-title">
            PRINCIPAL
        </div>

        <nav class="menu">

            <a href="index.php">
                <span class="menu-icon">📊</span>
                Dashboard
            </a>

            <a href="productos.php">
                <span class="menu-icon">💄</span>
                Productos
            </a>

            <a
                href="categorias.php"
                class="active"
            >
                <span class="menu-icon">📂</span>
                Categorías
            </a>

            <a href="pedidos.php">
                <span class="menu-icon">🛍️</span>
                Pedidos
            </a>

        </nav>

        <div class="menu-title">
            CUENTA
        </div>

        <nav class="menu">

            <a href="../index.php">
                <span class="menu-icon">🌐</span>
                Ver tienda
            </a>

        </nav>

        <div class="sidebar-bottom">

            <a
                href="index.php"
                class="view-store"
            >
                ← Volver al dashboard
            </a>

            <a
                href="logout.php"
                class="logout"
            >
                🚪 Cerrar sesión
            </a>

        </div>

    </aside>

    <!-- =========================================================
         MAIN
    ========================================================== -->

    <main class="main">

        <div class="topbar">

            <div>

                <div class="eyebrow">
                    ADMINISTRACIÓN
                </div>

                <h1>
                    Categorías
                </h1>

                <p>
                    Organiza y administra las categorías de tu tienda.
                </p>

            </div>

            <button
                type="button"
                class="btn-primary"
                id="btnNuevaCategoria"
            >
                ＋ Nueva categoría
            </button>

        </div>

        <?php if ($mensaje !== ""): ?>

            <div class="alert alert-success">
                ✅ <?= e($mensaje) ?>
            </div>

        <?php endif; ?>

        <?php if ($error !== ""): ?>

            <div class="alert alert-error">
                ⚠️ <?= e($error) ?>
            </div>

        <?php endif; ?>

        <!-- =====================================================
             STATS
        ====================================================== -->

        <section class="stats">

            <div class="stat-card">

                <div class="stat-label">
                    TOTAL CATEGORÍAS
                </div>

                <div class="stat-number">
                    <?= $totalCategorias ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    CATEGORÍAS ACTIVAS
                </div>

                <div class="stat-number">
                    <?= $categoriasActivas ?>
                </div>

            </div>

            <div class="stat-card">

                <div class="stat-label">
                    CATEGORÍAS INACTIVAS
                </div>

                <div class="stat-number">
                    <?= $categoriasInactivas ?>
                </div>

            </div>

        </section>

        <!-- =====================================================
             TABLA
        ====================================================== -->

        <section class="content-card">

            <div class="content-header">

                <h2>
                    Todas las categorías
                </h2>

                <input
                    type="text"
                    id="buscarCategoria"
                    class="search"
                    placeholder="🔎 Buscar categoría..."
                >

            </div>

            <div class="table-wrap">

                <?php if (count($categorias) > 0): ?>

                    <table>

                        <thead>

                            <tr>

                                <th>
                                    Categoría
                                </th>

                                <th>
                                    Descripción
                                </th>

                                <th>
                                    Productos
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>

                        <tbody id="tablaCategorias">

                            <?php foreach ($categorias as $categoria): ?>

                                <tr
                                    data-search="<?= e(
                                        strtolower(
                                            $categoria["nombre"]
                                            . " "
                                            . ($categoria["descripcion"] ?? "")
                                        )
                                    ) ?>"
                                >

                                    <td>

                                        <div class="category-name">
                                            <?= e($categoria["nombre"]) ?>
                                        </div>

                                    </td>

                                    <td>

                                        <div class="category-description">

                                            <?=
                                                !empty($categoria["descripcion"])
                                                    ? e($categoria["descripcion"])
                                                    : "Sin descripción"
                                            ?>

                                        </div>

                                    </td>

                                    <td>

                                        <span class="count">

                                            <?= (int)$categoria["total_productos"] ?>

                                            <?= (int)$categoria["total_productos"] === 1
                                                ? "producto"
                                                : "productos"
                                            ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?php if ((int)$categoria["estado"] === 1): ?>

                                            <span class="badge active">
                                                ACTIVA
                                            </span>

                                        <?php else: ?>

                                            <span class="badge inactive">
                                                INACTIVA
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="actions">

                                            <button
                                                type="button"
                                                class="action-btn edit-btn btnEditar"

                                                data-id="<?= (int)$categoria["id"] ?>"

                                                data-nombre="<?= e($categoria["nombre"]) ?>"

                                                data-descripcion="<?= e(
                                                    $categoria["descripcion"] ?? ""
                                                ) ?>"
                                            >
                                                ✏️ Editar
                                            </button>

                                            <a
                                                href="categoria_eliminar.php?id=<?= (int)$categoria["id"] ?>"

                                                class="action-btn toggle-btn"

                                                onclick="return confirmarCambio(
                                                    <?= (int)$categoria["id"] ?>,
                                                    <?= (int)$categoria["estado"] ?>
                                                )"
                                            >

                                                <?=
                                                    (int)$categoria["estado"] === 1
                                                        ? "🔴 Desactivar"
                                                        : "🟢 Activar"
                                                ?>

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                <?php else: ?>

                    <div class="empty">

                        <div class="empty-icon">
                            📂
                        </div>

                        <h3>
                            No hay categorías
                        </h3>

                        <p>
                            Crea tu primera categoría para organizar tus productos.
                        </p>

                    </div>

                <?php endif; ?>

            </div>

        </section>

    </main>

    <!-- =========================================================
         MODAL
    ========================================================== -->

    <div
        class="modal-overlay"
        id="modalCategoria"
    >

        <div class="modal">

            <div class="modal-header">

                <div>

                    <h2 id="modalTitulo">
                        Nueva categoría
                    </h2>

                    <p>
                        Completa la información de la categoría.
                    </p>

                </div>

                <button
                    type="button"
                    class="close"
                    id="cerrarModal"
                >
                    ✕
                </button>

            </div>

            <form
                action="categoria_guardar.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="id"
                    id="categoriaId"
                    value="0"
                >

                <div class="form-group">

                    <label for="categoriaNombre">
                        Nombre de la categoría
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="categoriaNombre"

                        maxlength="100"

                        placeholder="Ej: Maquillaje"

                        required
                    >

                </div>

                <div class="form-group">

                    <label for="categoriaDescripcion">

                        Descripción

                        <span class="optional">
                            (opcional)
                        </span>

                    </label>

                    <textarea
                        name="descripcion"
                        id="categoriaDescripcion"

                        rows="4"

                        placeholder="Describe brevemente esta categoría..."
                    ></textarea>

                </div>

                <div class="modal-actions">

                    <button
                        type="button"
                        class="btn-secondary"
                        id="cancelarModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        💾 Guardar categoría
                    </button>

                </div>

            </form>

        </div>

    </div>

    <script>

        const modal = document.getElementById(
            "modalCategoria"
        );

        const btnNueva = document.getElementById(
            "btnNuevaCategoria"
        );

        const cerrarModal = document.getElementById(
            "cerrarModal"
        );

        const cancelarModal = document.getElementById(
            "cancelarModal"
        );

        const modalTitulo = document.getElementById(
            "modalTitulo"
        );

        const categoriaId = document.getElementById(
            "categoriaId"
        );

        const categoriaNombre = document.getElementById(
            "categoriaNombre"
        );

        const categoriaDescripcion = document.getElementById(
            "categoriaDescripcion"
        );

        /*
        |--------------------------------------------------------------------------
        | ABRIR NUEVA CATEGORÍA
        |--------------------------------------------------------------------------
        */

        function abrirNuevaCategoria() {

            modalTitulo.textContent =
                "Nueva categoría";

            categoriaId.value = "0";

            categoriaNombre.value = "";

            categoriaDescripcion.value = "";

            modal.classList.add("active");

            setTimeout(() => {
                categoriaNombre.focus();
            }, 100);
        }

        /*
        |--------------------------------------------------------------------------
        | CERRAR MODAL
        |--------------------------------------------------------------------------
        */

        function cerrarCategoriaModal() {

            modal.classList.remove("active");

        }

        btnNueva.addEventListener(
            "click",
            abrirNuevaCategoria
        );

        cerrarModal.addEventListener(
            "click",
            cerrarCategoriaModal
        );

        cancelarModal.addEventListener(
            "click",
            cerrarCategoriaModal
        );

        modal.addEventListener(
            "click",
            function(event) {

                if (event.target === modal) {
                    cerrarCategoriaModal();
                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | EDITAR CATEGORÍA
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(".btnEditar")
            .forEach(button => {

                button.addEventListener(
                    "click",
                    function() {

                        modalTitulo.textContent =
                            "Editar categoría";

                        categoriaId.value =
                            this.dataset.id;

                        categoriaNombre.value =
                            this.dataset.nombre;

                        categoriaDescripcion.value =
                            this.dataset.descripcion || "";

                        modal.classList.add("active");

                        setTimeout(() => {
                            categoriaNombre.focus();
                        }, 100);

                    }
                );

            });

        /*
        |--------------------------------------------------------------------------
        | ESCAPE
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            "keydown",
            function(event) {

                if (
                    event.key === "Escape" &&
                    modal.classList.contains("active")
                ) {

                    cerrarCategoriaModal();

                }

            }
        );

        /*
        |--------------------------------------------------------------------------
        | BUSCADOR
        |--------------------------------------------------------------------------
        */

        const buscador =
            document.getElementById(
                "buscarCategoria"
            );

        buscador.addEventListener(
            "input",
            function() {

                const texto =
                    this.value
                        .toLowerCase()
                        .trim();

                document
                    .querySelectorAll(
                        "#tablaCategorias tr"
                    )
                    .forEach(fila => {

                        const contenido =
                            fila.dataset.search || "";

                        fila.style.display =
                            contenido.includes(texto)
                                ? ""
                                : "none";

                    });

            }
        );

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR ACTIVAR / DESACTIVAR
        |--------------------------------------------------------------------------
        */

        function confirmarCambio(id, estado) {

            if (estado === 1) {

                return confirm(
                    "¿Seguro que quieres desactivar esta categoría?\n\n" +
                    "Los productos de esta categoría podrán seguir existiendo, " +
                    "pero la categoría no estará disponible para nuevos productos."
                );

            }

            return confirm(
                "¿Quieres activar nuevamente esta categoría?"
            );

        }

    </script>

</body>

</html>