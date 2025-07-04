<?php
include_once '../data/ususarioDB.php';
require_once '../config/database.php';

$database = new Database();
$usuarioDB = new UsuarioDB($database);

//comprobar si se ha recibido un token
if(isset($_GET['token'])){
    $token = $_GET['token'];
    $resultado = $usuarioDB->verificarToken($token);
    $mensaje = $resultado['mensaje'];
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
    <title>Verificación de cuenta</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="container">
        <h1>Verificación de cuenta</h1>
        <p class="mensaje"><?php echo $mensaje; ?></p>
        <a href="login.php" class="volver">Ir a Iniciar Sesión</a>
    </div>
</body>
</html>