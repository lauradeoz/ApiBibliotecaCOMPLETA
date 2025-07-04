
<?php

include_once '../data/ususarioDB.php';
require_once '../config/database.php';

$database = new Database();
$usuariobd = new UsuarioDB($database);

//verificar si se ha proporcionado un token
if(isset($_GET['token'])){
    $token = $_GET['token'];

    if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['nueva_password'])){
        $resultado = $usuariobd->restablecerPassword($token, $_POST['nueva_password']);
        $mensaje = $resultado['mensaje'];
    }
}else{
    header("Location: login.php");
    exit();
}


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
    <h1>Restablecer Contraseña</h1>
    <?php
        if(!empty($mensaje)): ?>
        <p class="mensaje"><?php echo $mensaje; ?></p>
        <?php if($resultado['success']): ?>
            <a href="login.php" class="volver">Ir a Iniciar Sesión</a>
        <?php endif; 
        else: 
        ?>
        <form method="POST" id="formRestablecer">
            <input type="password" name="nueva_password" id="nuevaPassword" required placeholder="Nueva Contraseña">
            <input type="password" name="confirmar_password" id="confirmarPassword" required placeholder="Confirmar Nueva Contraseña">
            <input type="submit" value="Restablecer Contraseña">
        </form>
        <p class="error" id="mensaje_cliente"></p>
        <script src="js/restablecer.js"></script>
        <?php endif; ?>
    </div>
    
</body>
</html>