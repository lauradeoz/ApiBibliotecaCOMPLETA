<?php
session_start();

if(isset($_SESSION['logueado'])){
    //borra todos los datos de la sesion
    session_unset();
    //destruye la sesion
    session_destroy();
}