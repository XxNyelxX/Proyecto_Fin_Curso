<?php
session_start();

//Llamo a autoload
require_once '../autoload.php';

//Si no viene ningún controlador en la URL (?c=...), usamos Inicio por defecto
$controller = isset($_REQUEST['c']) ? $_REQUEST['c'] : 'Inicio';
//ucwords convierte en mayus la primera letra de cada palabra en un string
$controller = ucwords($controller) . 'Controller';

//Si no viene ningúna accion en la URL (?a=...), usamos Index por defecto
$accion = isset($_REQUEST['a']) ? $_REQUEST['a'] : 'Index';

//Instancia automáticamente a la clase definida en la variable a la propia variable
$controller = new $controller;
//LLama a un método, en este caso recibe un array con el objeto y el método
//Si en la URL pone ?c=login&a=Form ejecutará la función Form() dentro de LoginController
call_user_func(array($controller, $accion));