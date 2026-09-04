
<?php

session_start();

require_once "../config/database.php";


/* =========================================================
   SI YA ESTÁ LOGUEADO
========================================================= */

if (isset($_SESSION["admin_id"])) {

    header("Location: index.php");
    exit;

}


$error = "";


/* =========================================================
   PROCESAR LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $usuario = trim($_POST["usuario"] ?? "");
    $password = $_POST["password"] ?? "";


    if ($usuario === "" || $password === "") {

        $error = "Completa todos los campos.";

    } else {

        try {

            $sql = "
                SELECT
                    id,
                    nombre,
                    usuario,
                    password,
                    estado
                FROM administradores
                WHERE usuario = :usuario
                LIMIT 1
            ";

            $stmt = $conexion->prepare($sql);

            $stmt->execute([
                ":usuario" => $usuario
            ]);

            $admin = $stmt->fetch();


            if (
                $admin &&
                (int)$admin["estado"] === 1 &&
                password_verify($password, $admin["password"])
            ) {

                session_regenerate_id(true);

                $_SESSION["admin_id"] = $admin["id"];
                $_SESSION["admin_nombre"] = $admin["nombre"];
                $_SESSION["admin_usuario"] = $admin["usuario"];


                header("Location: index.php");
                exit;

            } else {

                $error = "Usuario o contraseña incorrectos.";

            }


        } catch (PDOException $e) {

            $error = "Ocurrió un error al iniciar sesión.";

        }

    }

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

    <title>Acceso administrativo | Cosméticos</title>

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #fdf2f8,
                    #fce7f3,
                    #f5f3ff
                );
        }

        .login-container {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 42px 36px;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.10);
        }

        .logo {
            width: 72px;
            height: 72px;

            margin: 0 auto 24px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 22px;

            background:
                linear-gradient(
                    135deg,
                    #ec4899,
                    #a855f7
                );

            color: white;
            font-size: 32px;

            box-shadow:
                0 12px 30px rgba(236, 72, 153, 0.25);
        }

        .login-card h1 {
            text-align: center;
            color: #18181b;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .subtitle {
            text-align: center;
            color: #71717a;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;

            padding: 12px 14px;
            border-radius: 12px;

            font-size: 14px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;

            color: #27272a;
            font-size: 14px;
            font-weight: 700;
        }

        input {
            width: 100%;

            padding: 14px 15px;

            border: 1px solid #e4e4e7;
            border-radius: 12px;

            outline: none;

            font-size: 15px;

            transition: 0.2s;
        }

        input:focus {
            border-color: #ec4899;

            box-shadow:
                0 0 0 4px rgba(236, 72, 153, 0.10);
        }

        button {
            width: 100%;

            border: none;
            border-radius: 12px;

            padding: 15px;

            background:
                linear-gradient(
                    135deg,
                    #ec4899,
                    #a855f7
                );

            color: white;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;
        }

        button:hover {
            transform: translateY(-1px);

            box-shadow:
                0 10px 25px rgba(168, 85, 247, 0.25);
        }

        .footer {
            text-align: center;
            margin-top: 22px;

            color: #a1a1aa;
            font-size: 12px;
        }

        @media (max-width: 480px) {

            .login-card {
                padding: 32px 24px;
            }

            .login-card h1 {
                font-size: 24px;
            }

        }

    </style>

</head>


<body>

    <div class="login-container">

        <div class="login-card">

            <div class="logo">
                💄
            </div>

            <h1>
                Panel administrativo
            </h1>

            <p class="subtitle">
                Ingresa para administrar tu tienda
            </p>


            <?php if ($error !== ""): ?>

                <div class="error">
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>


            <form method="POST">

                <div class="form-group">

                    <label for="usuario">
                        Usuario
                    </label>

                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        placeholder="Ingresa tu usuario"
                        autocomplete="username"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="password">
                        Contraseña
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Ingresa tu contraseña"
                        autocomplete="current-password"
                        required
                    >

                </div>


                <button type="submit">
                    Iniciar sesión
                </button>

            </form>


            <div class="footer">
                Panel privado · Tu tienda de cosméticos
            </div>

        </div>

    </div>

</body>

</html>

