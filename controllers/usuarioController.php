<?php

if(session_status() == PHP_SESSION_NONE){
    session_start();
}

//incluir las clases que necesitamos
require_once '../config/database.php';
require_once '../data/ususarioDB.php';

//crear instancias UsuarioDB
$database = new Database();
$usuariodb = new UsuarioDB($database);

//comprobar si lo que quiere haces es un inicio de sesion
//comprobar que llegan los datos: email, password y login
//comprobar que el método es POST
if($_SERVER['REQUEST_METHOD'] == 'POST'
&& isset($_POST['login'])
&& isset($_POST['email'])
&& isset($_POST['password'])
){

    //el usuario quiere iniciar sesion
    //comprobar que el usuario existe y que el password es correcto
    $email = $_POST['email'];
    $password = $_POST['password'];
    $resultado = $usuariodb->verificarCredenciales($email, $password);
    //guardar la respuesta
    $_SESSION['logueado'] = $resultado['success'];
    if($resultado['success'] == true){
        $_SESSION['usuario'] = $resultado['usuario'];
        $ruta = '../admin/index.php';
    }else{
        $ruta = '../admin/login.php';
    }
    redirigirConMensaje($ruta, $resultado['success'], $resultado['mensaje']);
}

//comprobar que el usuario quiere registrarse
if($_SERVER['REQUEST_METHOD'] == 'POST'
&& isset($_POST['registro'])
&& isset($_POST['email'])
&& isset($_POST['password'])
){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resultado = $usuariodb->registrarUsuario($email, $password);
    
    //el usuario quiere crear una cuenta nueva
    redirigirConMensaje('../admin/login.php', $resultado['success'], $resultado['mensaje']);

}

//recuperacion de contraseña
if($_SERVER['REQUEST_METHOD'] == "POST" 
    && isset($_POST['recuperar'])
    && isset($_POST['email'])
    ){

    $email = $_POST['email'];

    $resultado = $usuariodb->recuperarPassword($email);
    redirigirConMensaje('../admin/login.php', $resultado['success'], $resultado['mensaje']);

}


function redirigirConMensaje($url, $success, $mensaje){
    //almacena el resultado en la variable de sesion
    $_SESSION['success'] = $success;
    $_SESSION['mensaje'] = $mensaje;

    //realiza la redireccion
    header("Location: $url");
}