/* =========================================================
   BELLAÉ COSMETICS
   SISTEMA DE CARRITO
========================================================= */


/* =========================================================
   VARIABLES
========================================================= */

let carrito = JSON.parse(localStorage.getItem("bellae_carrito")) || [];


/* =========================================================
   ELEMENTOS DEL DOM
========================================================= */

const cartButton = document.getElementById("cartButton");
const cartSidebar = document.getElementById("cartSidebar");
const cartOverlay = document.getElementById("cartOverlay");
const closeCart = document.getElementById("closeCart");

const cartItems = document.getElementById("cartItems");
const cartCount = document.getElementById("cartCount");
const cartTotal = document.getElementById("cartTotal");

const menuButton = document.getElementById("menuButton");
const mobileMenu = document.getElementById("mobileMenu");

const checkoutButton = document.getElementById("checkoutButton");


/* =========================================================
   INICIALIZAR
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    actualizarCarrito();

    configurarFavoritos();

    configurarMenuMovil();

});


/* =========================================================
   ABRIR CARRITO
========================================================= */

function abrirCarrito() {

    if (!cartSidebar || !cartOverlay) {
        return;
    }

    cartSidebar.classList.add("active");

    cartOverlay.classList.add("active");

    document.body.classList.add("cart-open");

}


/* =========================================================
   CERRAR CARRITO
========================================================= */

function cerrarCarrito() {

    if (!cartSidebar || !cartOverlay) {
        return;
    }

    cartSidebar.classList.remove("active");

    cartOverlay.classList.remove("active");

    document.body.classList.remove("cart-open");

}


/* =========================================================
   EVENTOS DEL CARRITO
========================================================= */

if (cartButton) {

    cartButton.addEventListener("click", () => {

        abrirCarrito();

    });

}


if (closeCart) {

    closeCart.addEventListener("click", () => {

        cerrarCarrito();

    });

}


if (cartOverlay) {

    cartOverlay.addEventListener("click", () => {

        cerrarCarrito();

    });

}


/* =========================================================
   AGREGAR PRODUCTO
========================================================= */

function agregarAlCarrito(id, nombre, precio, stock) {

    id = Number(id);

    precio = Number(precio);

    stock = Number(stock);


    if (!id || !nombre || precio < 0) {

        console.error("Datos de producto inválidos.");

        return;

    }


    /* -----------------------------------------
       BUSCAR SI YA EXISTE
    ----------------------------------------- */

    const productoExistente = carrito.find(
        producto => Number(producto.id) === id
    );


    /* -----------------------------------------
       SI YA EXISTE
    ----------------------------------------- */

    if (productoExistente) {


        if (productoExistente.cantidad >= stock) {

            mostrarMensaje(
                "No puedes agregar más unidades de este producto."
            );

            return;

        }


        productoExistente.cantidad++;

    }


    /* -----------------------------------------
       SI ES NUEVO
    ----------------------------------------- */

    else {

        carrito.push({

            id: id,

            nombre: nombre,

            precio: precio,

            stock: stock,

            cantidad: 1

        });

    }


    guardarCarrito();

    actualizarCarrito();

    abrirCarrito();

    mostrarMensaje(
        `${nombre} agregado al carrito 🛒`
    );

}


/* =========================================================
   AUMENTAR CANTIDAD
========================================================= */

function aumentarCantidad(id) {

    const producto = carrito.find(
        producto => Number(producto.id) === Number(id)
    );


    if (!producto) {
        return;
    }


    if (producto.cantidad >= producto.stock) {

        mostrarMensaje(
            "Has alcanzado el stock disponible."
        );

        return;

    }


    producto.cantidad++;


    guardarCarrito();

    actualizarCarrito();

}


/* =========================================================
   DISMINUIR CANTIDAD
========================================================= */

function disminuirCantidad(id) {

    const producto = carrito.find(
        producto => Number(producto.id) === Number(id)
    );


    if (!producto) {
        return;
    }


    if (producto.cantidad > 1) {

        producto.cantidad--;

    }

    else {

        eliminarProducto(id);

        return;

    }


    guardarCarrito();

    actualizarCarrito();

}


/* =========================================================
   ELIMINAR PRODUCTO
========================================================= */

function eliminarProducto(id) {

    carrito = carrito.filter(
        producto => Number(producto.id) !== Number(id)
    );


    guardarCarrito();

    actualizarCarrito();

}


/* =========================================================
   ACTUALIZAR CARRITO
========================================================= */

function actualizarCarrito() {

    if (!cartItems) {
        return;
    }


    /* -----------------------------------------
       CONTADOR
    ----------------------------------------- */

    const cantidadTotal = carrito.reduce(
        (total, producto) => {

            return total + producto.cantidad;

        },
        0
    );


    if (cartCount) {

        cartCount.textContent = cantidadTotal;

    }


    /* -----------------------------------------
       CARRITO VACÍO
    ----------------------------------------- */

    if (carrito.length === 0) {


        cartItems.innerHTML = `

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

        `;


        if (cartTotal) {

            cartTotal.textContent = "$0 COP";

        }


        return;

    }


    /* -----------------------------------------
       RENDERIZAR PRODUCTOS
    ----------------------------------------- */

    cartItems.innerHTML = carrito.map(producto => {

        const subtotal =
            producto.precio * producto.cantidad;


        return `

            <div class="cart-item">

                <div class="cart-item-image">
                    💄
                </div>


                <div class="cart-item-info">

                    <h4>
                        ${escaparHTML(producto.nombre)}
                    </h4>


                    <span class="cart-item-price">

                        ${formatearPrecio(producto.precio)}

                        COP

                    </span>


                    <div class="cart-item-controls">


                        <button
                            type="button"
                            onclick="disminuirCantidad(${producto.id})"
                            aria-label="Disminuir cantidad"
                        >
                            −
                        </button>


                        <span>
                            ${producto.cantidad}
                        </span>


                        <button
                            type="button"
                            onclick="aumentarCantidad(${producto.id})"
                            aria-label="Aumentar cantidad"
                        >
                            +
                        </button>


                    </div>


                    <strong class="cart-item-subtotal">

                        ${formatearPrecio(subtotal)}

                        COP

                    </strong>

                </div>


                <button
                    class="cart-item-remove"
                    type="button"
                    onclick="eliminarProducto(${producto.id})"
                    aria-label="Eliminar producto"
                >

                    ✕

                </button>

            </div>

        `;

    }).join("");


    /* -----------------------------------------
       TOTAL
    ----------------------------------------- */

    const total = carrito.reduce(

        (suma, producto) => {

            return suma +
                (producto.precio * producto.cantidad);

        },

        0

    );


    if (cartTotal) {

        cartTotal.textContent =
            `${formatearPrecio(total)} COP`;

    }

}


/* =========================================================
   GUARDAR CARRITO
========================================================= */

function guardarCarrito() {

    localStorage.setItem(
        "bellae_carrito",
        JSON.stringify(carrito)
    );

}


/* =========================================================
   FORMATEAR PRECIO
========================================================= */

function formatearPrecio(numero) {

    return new Intl.NumberFormat(
        "es-CO",
        {
            maximumFractionDigits: 0
        }
    ).format(Number(numero));

}


/* =========================================================
   ESCAPAR HTML
========================================================= */

function escaparHTML(texto) {

    const div = document.createElement("div");

    div.textContent = texto;

    return div.innerHTML;

}


/* =========================================================
   MENSAJE TEMPORAL
========================================================= */

function mostrarMensaje(mensaje) {

    let mensajeExistente =
        document.getElementById("bellaeToast");


    if (mensajeExistente) {

        mensajeExistente.remove();

    }


    const toast = document.createElement("div");

    toast.id = "bellaeToast";

    toast.textContent = mensaje;


    toast.style.position = "fixed";
    toast.style.bottom = "25px";
    toast.style.left = "50%";
    toast.style.transform = "translateX(-50%)";
    toast.style.background = "#1f1f1f";
    toast.style.color = "#fff";
    toast.style.padding = "14px 22px";
    toast.style.borderRadius = "50px";
    toast.style.fontSize = "14px";
    toast.style.fontWeight = "600";
    toast.style.zIndex = "99999";
    toast.style.boxShadow =
        "0 10px 30px rgba(0,0,0,.20)";


    document.body.appendChild(toast);


    setTimeout(() => {

        toast.style.opacity = "0";

        toast.style.transition =
            "opacity .3s ease";


        setTimeout(() => {

            toast.remove();

        }, 300);

    }, 2200);

}


/* =========================================================
   FAVORITOS
========================================================= */

function configurarFavoritos() {

    const botones =
        document.querySelectorAll(".favorite-button");


    botones.forEach(boton => {

        boton.addEventListener("click", () => {

            boton.classList.toggle("active");


            if (boton.classList.contains("active")) {

                boton.textContent = "♥";

            }

            else {

                boton.textContent = "♡";

            }

        });

    });

}


/* =========================================================
   MENU MOVIL
========================================================= */

function configurarMenuMovil() {

    if (!menuButton || !mobileMenu) {
        return;
    }


    menuButton.addEventListener("click", () => {

        mobileMenu.classList.toggle("active");

    });


    const enlaces =
        mobileMenu.querySelectorAll("a");


    enlaces.forEach(enlace => {

        enlace.addEventListener("click", () => {

            mobileMenu.classList.remove("active");

        });

    });

}


/* =========================================================
   CERRAR CON ESC
========================================================= */

document.addEventListener("keydown", event => {

    if (event.key === "Escape") {

        cerrarCarrito();

    }

});


/* =========================================================
   CHECKOUT
========================================================= */

const checkoutOverlay =
    document.getElementById("checkoutOverlay");

const closeCheckout =
    document.getElementById("closeCheckout");

const checkoutBack =
    document.getElementById("checkoutBack");

const checkoutForm =
    document.getElementById("checkoutForm");

const checkoutSummaryItems =
    document.getElementById("checkoutSummaryItems");

const checkoutSummaryTotal =
    document.getElementById("checkoutSummaryTotal");

const checkoutItemsCount =
    document.getElementById("checkoutItemsCount");


/* =========================================================
   ABRIR CHECKOUT
========================================================= */

function abrirCheckout() {

    if (!checkoutOverlay) {
        return;
    }


    if (carrito.length === 0) {

        mostrarMensaje(
            "Agrega al menos un producto al carrito."
        );

        return;

    }


    actualizarResumenCheckout();


    checkoutOverlay.classList.add("active");

}


/* =========================================================
   CERRAR CHECKOUT
========================================================= */

function cerrarCheckout() {

    if (!checkoutOverlay) {
        return;
    }


    checkoutOverlay.classList.remove("active");

}


/* =========================================================
   BOTÓN FINALIZAR COMPRA
========================================================= */

if (checkoutButton) {

    checkoutButton.addEventListener("click", () => {

        abrirCheckout();

    });

}


/* =========================================================
   CERRAR CHECKOUT
========================================================= */

if (closeCheckout) {

    closeCheckout.addEventListener("click", () => {

        cerrarCheckout();

    });

}


if (checkoutBack) {

    checkoutBack.addEventListener("click", () => {

        cerrarCheckout();

    });

}


/* =========================================================
   CLICK FUERA DEL MODAL
========================================================= */

if (checkoutOverlay) {

    checkoutOverlay.addEventListener("click", event => {

        if (event.target === checkoutOverlay) {

            cerrarCheckout();

        }

    });

}


/* =========================================================
   RESUMEN DEL CHECKOUT
========================================================= */

function actualizarResumenCheckout() {

    if (
        !checkoutSummaryItems ||
        !checkoutSummaryTotal ||
        !checkoutItemsCount
    ) {

        return;

    }


    /* -----------------------------------------
       CANTIDAD TOTAL
    ----------------------------------------- */

    const cantidadTotal = carrito.reduce(
        (total, producto) => {

            return total + producto.cantidad;

        },
        0
    );


    checkoutItemsCount.textContent =
        cantidadTotal === 1
            ? "1 producto"
            : `${cantidadTotal} productos`;


    /* -----------------------------------------
       PRODUCTOS
    ----------------------------------------- */

    checkoutSummaryItems.innerHTML =
        carrito.map(producto => {

            const subtotal =
                producto.precio * producto.cantidad;


            return `

                <div class="checkout-summary-item">

                    <span>
                        ${escaparHTML(producto.nombre)}
                        × ${producto.cantidad}
                    </span>

                    <strong>
                        ${formatearPrecio(subtotal)} COP
                    </strong>

                </div>

            `;

        }).join("");


    /* -----------------------------------------
       TOTAL
    ----------------------------------------- */

    const total = carrito.reduce(
        (suma, producto) => {

            return suma +
                (producto.precio * producto.cantidad);

        },
        0
    );


    checkoutSummaryTotal.textContent =
        `${formatearPrecio(total)} COP`;

}


/* =========================================================
   ENVIAR FORMULARIO
========================================================= */

if (checkoutForm) {

    checkoutForm.addEventListener("submit", event => {

        event.preventDefault();


        /* -----------------------------------------
           VALIDAR CARRITO
        ----------------------------------------- */

        if (carrito.length === 0) {

            cerrarCheckout();

            mostrarMensaje(
                "Tu carrito está vacío."
            );

            return;

        }


        /* -----------------------------------------
           OBTENER DATOS
        ----------------------------------------- */

        const datos = {

            nombre:
                document.getElementById("nombre").value.trim(),

            telefono:
                document.getElementById("telefono").value.trim(),

            email:
                document.getElementById("email").value.trim(),

            ciudad:
                document.getElementById("ciudad").value.trim(),

            direccion:
                document.getElementById("direccion").value.trim(),

            observaciones:
                document.getElementById("observaciones").value.trim()

        };


        /* -----------------------------------------
           VALIDACIONES
        ----------------------------------------- */

        if (!datos.nombre) {

            mostrarMensaje(
                "Escribe tu nombre completo."
            );

            return;

        }


        if (!datos.telefono) {

            mostrarMensaje(
                "Escribe tu número de WhatsApp."
            );

            return;

        }


        if (!datos.ciudad) {

            mostrarMensaje(
                "Escribe tu ciudad."
            );

            return;

        }


        if (!datos.direccion) {

            mostrarMensaje(
                "Escribe tu dirección."
            );

            return;

        }


        /* -----------------------------------------
           POR AHORA
        ----------------------------------------- */

        console.log("Datos del cliente:", datos);

        console.log("Carrito:", carrito);


        mostrarMensaje(
            "¡Datos recibidos correctamente! 🛍️"
        );


        /*
        -------------------------------------------------
        EN EL SIGUIENTE PASO:

        Estos datos viajarán a PHP.

        PHP verificará:

        - Productos
        - Precios reales
        - Stock
        - Total

        Luego crearemos:

        pedido
        +
        detalle_pedido

        Y finalmente enviaremos el pedido
        a WhatsApp.
        -------------------------------------------------
        */


        setTimeout(() => {

            cerrarCheckout();

        }, 1000);

    });

}