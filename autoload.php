<?php
// Registramos el cargador automático
spl_autoload_register(function ($class) {
    // 1. Construimos el nombre del archivo. 
    $root = __DIR__ ;
    $archivo = '';

    // CASO 1: Si piden la base de datos (Database)
    if ($class === 'Database') {
        $archivo = $root . '/bd/database.php';
    }
    // CASO 2: Si es un Controlador (ej: LoginController)
    elseif (strpos($class, 'Controller') !== false) {
        // Quitamos la palabra 'Controller' (LoginController -> Alumno)
        $nombre_limpio = str_replace('Controller', '', $class);
        // Lo pasamos a minúsculas y añadimos la extensión (Alumno -> alumno.controller.php)
        $archivo = $root . '/controller/' . strtolower($nombre_limpio) . '.controller.php';
    }
    // CASO 3: Si es un DAO (ej: UsuarioDAO)
    elseif (strpos($class, 'DAO') !== false) {
        // Buscamos en model/ y ponemos la primera letra minúscula (UsuarioDAO -> usuarioDAO.php)
        $archivo = $root . '/model/' . lcfirst($class) . '.php';
    }
    // CASO 4: Si es una Entidad (clases simples como Usuario, Partida)
    else {
        // Buscamos en la carpeta de entidades
        $archivo = $root . '/model/entidades/' . strtolower($class) . '.php';
    }

    // 2. Comprobamos si el archivo existe
    if (file_exists($archivo)) {
        // 3. Si existe, lo incluimos
        require_once $archivo;
    }else {
        echo "ERROR: No encuentro el archivo " . $archivo . "<br>";
    }
});

?>
