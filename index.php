
<?php

require_once "config/database.php";

/*
|--------------------------------------------------------------------------
| OBTENER PRODUCTOS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        p.id,
        p.nombre,
        p.descripcion,
        p.precio,
        p.stock,
        p.imagen,
        c.nombre AS categoria
    FROM productos p
    LEFT JOIN categorias c
        ON p.categoria_id = c.id
    WHERE p.estado = 1
    ORDER BY p.id DESC
";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$productos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Tienda online de cosméticos"
    >

    <title>Cosméticos | Tienda Online</title>


    <!-- ======================================
         GOOGLE FONTS
    ====================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- ======================================
         CSS
    ====================================== -->

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body>


<!-- ======================================
     HEADER
====================================== -->

<header class="header">

    <div class="container header-content">


        <!-- LOGO -->

        <a
            href="index.php"
            class="logo"
        >

            <span class="logo-icon">
                ✦
            </span>

            <span class="logo-text">
                BELLAÉ
            </span>

        </a>


        <!-- MENU DESKTOP -->

        <nav class="nav">

            <a href="#inicio">
                Inicio
            </a>

            <a href="#productos">
                Productos
            </a>

            <a href="#categorias">
                Categorías
            </a>

            <a href="#nosotros">
                Nosotros
            </a>

        </nav>


        <!-- ACCIONES -->

        <div class="header-actions">


            <button
                class="icon-button"
                id="searchButton"
                type="button"
            >
                🔍
            </button>


            <button
                class="cart-button"
                id="cartButton"
                type="button"
            >

                🛒

                <span id="cartCount">
                    0
                </span>

            </button>


            <button
                class="menu-button"
                id="menuButton"
                type="button"
            >
                ☰
            </button>


        </div>

    </div>

</header>



<!-- ======================================
     MOBILE MENU
====================================== -->

<div
    class="mobile-menu"
    id="mobileMenu"
>

    <a href="#inicio">
        Inicio
    </a>

    <a href="#productos">
        Productos
    </a>

    <a href="#categorias">
        Categorías
    </a>

    <a href="#nosotros">
        Nosotros
    </a>

</div>



<!-- ======================================
     HERO
====================================== -->

<section
    class="hero"
    id="inicio"
>

    <div class="container hero-content">


        <!-- TEXTO -->

        <div class="hero-text">


            <span class="hero-tag">
                ✨ BELLEZA QUE TE REPRESENTA
            </span>


            <h1>

                Descubre tu

                <span>
                    mejor versión.
                </span>

            </h1>


            <p>

                Encuentra productos de belleza seleccionados
                para resaltar tu estilo y hacerte sentir increíble.

            </p>


            <div class="hero-buttons">


                <a
                    href="#productos"
                    class="btn btn-primary"
                >
                    Comprar ahora
                </a>


                <a
                    href="#categorias"
                    class="btn btn-secondary"
                >
                    Explorar
                </a>


            </div>

        </div>



        <!-- IMAGEN HERO -->

        <div class="hero-image">


            <div class="hero-decoration"></div>


            <div class="hero-product">

                <div class="product-placeholder">
                    💄
                </div>

            </div>


            <div class="floating-card">


                <span>
                    ⭐
                </span>


                <div>

                    <strong>
                        Productos
                    </strong>

                    <small>
                        Seleccionados para ti
                    </small>

                </div>


            </div>


        </div>

    </div>

</section>



<!-- ======================================
     CATEGORIAS
====================================== -->

<section
    class="categories"
    id="categorias"
>

    <div class="container">


        <div class="section-heading">


            <div>

                <span class="section-tag">
                    CATEGORÍAS
                </span>

                <h2>
                    Encuentra tus favoritos
                </h2>

            </div>


        </div>



        <div class="category-grid">


            <!-- LABIOS -->

            <button
                class="category-card"
                type="button"
            >

                <div class="category-icon">
                    💄
                </div>

                <h3>
                    Labios
                </h3>

                <span>
                    Descubrir →
                </span>

            </button>



            <!-- ROSTRO -->

            <button
                class="category-card"
                type="button"
            >

                <div class="category-icon">
                    ✨
                </div>

                <h3>
                    Rostro
                </h3>

                <span>
                    Descubrir →
                </span>

            </button>



            <!-- OJOS -->

            <button
                class="category-card"
                type="button"
            >

                <div class="category-icon">
                    👁️
                </div>

                <h3>
                    Ojos
                </h3>

                <span>
                    Descubrir →
                </span>

            </button>



            <!-- CUIDADO -->

            <button
                class="category-card"
                type="button"
            >

                <div class="category-icon">
                    🌸
                </div>

                <h3>
                    Cuidado
                </h3>

                <span>
                    Descubrir →
                </span>

            </button>


        </div>

    </div>

</section>



<!-- ======================================
     PRODUCTOS
====================================== -->

<section
    class="products-section"
    id="productos"
>

    <div class="container">


        <!-- TITULO -->

        <div class="section-heading">


            <div>

                <span class="section-tag">
                    NUESTROS PRODUCTOS
                </span>

                <h2>
                    Productos destacados
                </h2>

            </div>


            <button
                class="view-all"
                type="button"
            >
                Ver todos →
            </button>


        </div>



        <!-- GRID -->

        <div class="products-grid">


            <?php if (!empty($productos)): ?>


                <?php foreach ($productos as $producto): ?>


                    <article
                        class="product-card"
                        data-category="<?= htmlspecialchars(
                            $producto['categoria'] ?? 'Sin categoría',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >


                        <!-- ==================================
                             IMAGEN DEL PRODUCTO
                        =================================== -->

                        <div class="product-image">


                            <span class="product-badge">
                                DESTACADO
                            </span>


                            <button
                                class="favorite-button"
                                type="button"
                                aria-label="Agregar a favoritos"
                            >
                                ♡
                            </button>


                            <?php if (!empty($producto['imagen'])): ?>


                                <img
                                    src="assets/img/productos/<?= htmlspecialchars(
                                        $producto['imagen'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    alt="<?= htmlspecialchars(
                                        $producto['nombre'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    loading="lazy"
                                >


                            <?php else: ?>


                                <div class="product-image-placeholder">
                                    💄
                                </div>


                            <?php endif; ?>



                            <!-- ==================================
                                 BOTON AGREGAR
                            =================================== -->

                            <?php if ((int)$producto['stock'] > 0): ?>


                                <button
                                    class="quick-add"
                                    type="button"
                                    onclick="agregarAlCarrito(
                                        <?= (int)$producto['id'] ?>,
                                        <?= htmlspecialchars(
                                            json_encode($producto['nombre'], JSON_UNESCAPED_UNICODE),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>,
                                        <?= (float)$producto['precio'] ?>,
                                        <?= (int)$producto['stock'] ?>
                                    )"
                                >

                                    + Agregar al carrito

                                </button>


                            <?php else: ?>


                                <button
                                    class="quick-add"
                                    type="button"
                                    disabled
                                >

                                    Agotado

                                </button>


                            <?php endif; ?>


                        </div>



                        <!-- ==================================
                             INFORMACION DEL PRODUCTO
                        =================================== -->

                        <div class="product-info">


                            <!-- CATEGORIA -->

                            <span class="product-category">

                                <?= htmlspecialchars(
                                    $producto['categoria'] ?? 'Sin categoría',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>



                            <!-- NOMBRE -->

                            <h3>

                                <?= htmlspecialchars(
                                    $producto['nombre'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </h3>



                            <!-- DESCRIPCION -->

                            <?php if (!empty($producto['descripcion'])): ?>

                                <p>

                                    <?= htmlspecialchars(
                                        $producto['descripcion'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </p>

                            <?php endif; ?>



                            <!-- STOCK -->

                            <?php if ((int)$producto['stock'] > 0): ?>

                                <span class="product-stock">

                                    <?= (int)$producto['stock'] ?>

                                    disponibles

                                </span>

                            <?php else: ?>

                                <span class="product-stock out">

                                    Agotado

                                </span>

                            <?php endif; ?>



                            <!-- PRECIO Y BOTON -->

                            <div class="product-bottom">


                                <strong class="product-price">

                                    $

                                    <?= number_format(
                                        (float)$producto['precio'],
                                        0,
                                        ',',
                                        '.'
                                    ) ?>

                                    <small>
                                        COP
                                    </small>

                                </strong>



                                <?php if ((int)$producto['stock'] > 0): ?>


                                    <button
                                        class="add-button"
                                        type="button"
                                        aria-label="Agregar <?= htmlspecialchars(
                                            $producto['nombre'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?> al carrito"
                                        onclick="agregarAlCarrito(
                                            <?= (int)$producto['id'] ?>,
                                            <?= htmlspecialchars(
                                                json_encode($producto['nombre'], JSON_UNESCAPED_UNICODE),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>,
                                            <?= (float)$producto['precio'] ?>,
                                            <?= (int)$producto['stock'] ?>
                                        )"
                                    >

                                        +

                                    </button>


                                <?php else: ?>


                                    <button
                                        class="add-button"
                                        type="button"
                                        disabled
                                        aria-label="Producto agotado"
                                    >

                                        ×

                                    </button>


                                <?php endif; ?>


                            </div>


                        </div>


                    </article>


                <?php endforeach; ?>


            <?php else: ?>


                <!-- ==================================
                     NO HAY PRODUCTOS
                =================================== -->

                <div class="empty-products">

                    <h3>
                        No hay productos disponibles
                    </h3>

                    <p>
                        Actualmente no tenemos productos publicados.
                    </p>

                </div>


            <?php endif; ?>


        </div>

    </div>

</section>



<!-- ======================================
     BENEFICIOS
====================================== -->

<section class="benefits">

    <div class="container benefits-grid">


        <!-- BENEFICIO 1 -->

        <div class="benefit">


            <div class="benefit-icon">
                🚚
            </div>


            <div>

                <h3>
                    Compra fácil
                </h3>

                <p>
                    Realiza tu pedido de forma rápida.
                </p>

            </div>


        </div>



        <!-- BENEFICIO 2 -->

        <div class="benefit">


            <div class="benefit-icon">
                💬
            </div>


            <div>

                <h3>
                    Atención por WhatsApp
                </h3>

                <p>
                    Habla directamente con nuestro vendedor.
                </p>

            </div>


        </div>



        <!-- BENEFICIO 3 -->

        <div class="benefit">


            <div class="benefit-icon">
                🔒
            </div>


            <div>

                <h3>
                    Compra segura
                </h3>

                <p>
                    Tus datos estarán protegidos.
                </p>

            </div>


        </div>


    </div>

</section>



<!-- ======================================
     FOOTER
====================================== -->

<footer
    class="footer"
    id="nosotros"
>


    <div class="container footer-content">


        <!-- LOGO -->

        <div>


            <div class="logo footer-logo">


                <span class="logo-icon">
                    ✦
                </span>


                <span class="logo-text">
                    BELLAÉ
                </span>


            </div>


            <p>
                Belleza, confianza y estilo en un solo lugar.
            </p>


        </div>



        <!-- CONTACTO -->

        <div class="footer-contact">


            <h3>
                Contáctanos
            </h3>


            <p>
                📱 WhatsApp
            </p>


            <p>
                📍 Colombia
            </p>


        </div>


    </div>



    <div class="footer-bottom">


        © <?= date('Y') ?> BELLAÉ Cosmetics.
        Todos los derechos reservados.


    </div>


</footer>



<!-- ======================================
     CARRITO
====================================== -->

<div
    class="cart-overlay"
    id="cartOverlay"
></div>



<aside
    class="cart-sidebar"
    id="cartSidebar"
>


    <!-- CABECERA -->

    <div class="cart-header">


        <div>

            <span class="section-tag">
                TU COMPRA
            </span>

            <h2>
                Mi carrito
            </h2>

        </div>


        <button
            id="closeCart"
            type="button"
        >
            ✕
        </button>


    </div>



    <!-- PRODUCTOS DEL CARRITO -->

    <div
        class="cart-items"
        id="cartItems"
    >


        <div class="empty-cart">


            <div>
                🛒
            </div>


            <h3>
                Tu carrito está vacío
            </h3>


            <p>
                Agrega productos para comenzar tu compra.
            </p>


        </div>


    </div>



    <!-- TOTAL -->

    <div class="cart-footer">


        <div class="cart-total">


            <span>
                Total
            </span>


            <strong id="cartTotal">
                $0 COP
            </strong>


        </div>



        <button
            class="checkout-button"
            id="checkoutButton"
            type="button"
        >

            Finalizar compra

        </button>


    </div>


</aside>


<!-- ======================================
     MODAL CHECKOUT
====================================== -->

<div class="checkout-overlay" id="checkoutOverlay">

    <div class="checkout-modal">

        <!-- CABECERA -->

        <div class="checkout-header">

            <div>

                <span class="section-tag">
                    FINALIZAR PEDIDO
                </span>

                <h2>
                    Completa tu compra
                </h2>

                <p>
                    Déjanos tus datos para preparar tu pedido.
                </p>

            </div>

            <button
                type="button"
                id="closeCheckout"
                class="checkout-close"
            >
                ✕
            </button>

        </div>


        <!-- FORMULARIO -->

        <form id="checkoutForm">

            <div class="checkout-grid">


                <!-- NOMBRE -->

                <div class="form-group">

                    <label for="nombre">
                        Nombre completo
                    </label>

                    <input
                        type="text"
                        id="nombre"
                        name="nombre"
                        placeholder="Ej: Juan Pérez"
                        autocomplete="name"
                        required
                    >

                </div>


                <!-- TELEFONO -->

                <div class="form-group">

                    <label for="telefono">
                        WhatsApp / Teléfono
                    </label>

                    <input
                        type="tel"
                        id="telefono"
                        name="telefono"
                        placeholder="Ej: 300 123 4567"
                        autocomplete="tel"
                        required
                    >

                </div>


                <!-- EMAIL -->

                <div class="form-group">

                    <label for="email">
                        Correo electrónico
                        <span>(opcional)</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="correo@ejemplo.com"
                        autocomplete="email"
                    >

                </div>


                <!-- CIUDAD -->

                <div class="form-group">

                    <label for="ciudad">
                        Ciudad
                    </label>

                    <input
                        type="text"
                        id="ciudad"
                        name="ciudad"
                        placeholder="Ej: Neiva"
                        autocomplete="address-level2"
                        required
                    >

                </div>


                <!-- DIRECCION -->

                <div class="form-group full-width">

                    <label for="direccion">
                        Dirección de entrega
                    </label>

                    <input
                        type="text"
                        id="direccion"
                        name="direccion"
                        placeholder="Ej: Calle 10 # 20-30"
                        autocomplete="street-address"
                        required
                    >

                </div>


                <!-- OBSERVACIONES -->

                <div class="form-group full-width">

                    <label for="observaciones">
                        Observaciones
                        <span>(opcional)</span>
                    </label>

                    <textarea
                        id="observaciones"
                        name="observaciones"
                        rows="3"
                        placeholder="¿Alguna indicación para tu pedido?"
                    ></textarea>

                </div>

            </div>


            <!-- RESUMEN -->

            <div class="checkout-summary">

                <div class="checkout-summary-title">

                    <span>
                        Resumen del pedido
                    </span>

                    <strong id="checkoutItemsCount">
                        0 productos
                    </strong>

                </div>


                <div
                    class="checkout-summary-items"
                    id="checkoutSummaryItems"
                >
                </div>


                <div class="checkout-summary-total">

                    <span>
                        Total
                    </span>

                    <strong id="checkoutSummaryTotal">
                        $0 COP
                    </strong>

                </div>

            </div>


            <!-- BOTONES -->

            <div class="checkout-actions">

                <button
                    type="button"
                    class="checkout-back"
                    id="checkoutBack"
                >
                    ← Volver al carrito
                </button>


                <button
                    type="submit"
                    class="checkout-submit"
                >
                    Continuar con el pedido →
                </button>

            </div>

        </form>

    </div>

</div>
<script
    src="assets/js/app.js"
></script>


</body>

</html>
```
