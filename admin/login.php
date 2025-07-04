<?php
/**
 * para guardar los datos de una sesion en php se utiliza la variable superglobal
 * $_SESSION es un array asociativo
 * 
 * para poder utilizar esta variable tenemos que iniciar sesion
 * con la funcion session_start()
 */

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

//comprobar que el usuario está logueado
if(isset($_SESSION['logueado']) && $_SESSION['logueado'] == true){
    //redirigir al index
    header("location: index.php");
    exit();

}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form method="post" action="../controllers/usuarioController.php">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="submit" name="login" value="Iniciar Sesión">
        </form>
        <div class="olvido-password">
            <a class="abrir-modal-recuperar">Recuperar contraseña</a>
        </div>
        
        <div class="crear-cuenta">
        <a class="abrir-modal-registro">Crear Nueva cuenta</a>
        </div>



        <?php
        if(isset($_SESSION['mensaje'])){
//si son incorrectos mostrar un mensaje de error
    echo "<div class='error'>" . $_SESSION['mensaje'] . "</div>";
    unset($_SESSION['mensaje']);
}

?>
<div id="modalRecuperar" class="modal">
    <div class="modal-contenido">
        <span class="cerrarRecuperar">&times;</span>
        <h2>Recuperar contraseña</h2>
        <form method="POST" action="../controllers/usuarioController.php">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="submit" name="recuperar" value="Recuperar Contraseña">
        </form>
    </div>
</div>

<div id="modalRegistro" class="modal">
    <div class="modal-contenido">
        <span class="cerrarRegistro">&times;</span>
        <h2>Registro Cuenta Nueva</h2>
        <form method="POST" action="../controllers/usuarioController.php">
        <input type="email" name="email" placeholder="Correo electrónico" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="submit" name="registro" value="Registrarse">
        </form>
    </div>
</div>
</div>
<script src="js/login.js"></script>
</body>
</html>

