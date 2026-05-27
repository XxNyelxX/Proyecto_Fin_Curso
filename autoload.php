<?php
// Registra el autoload
spl_autoload_register(function ($class) {
    // Construlle el nombre del archivo. 
    $root = __DIR__ ;
    $archivo = '';

    // CASO 1: Si piden la base de datos (Database)
    if ($class === 'Database') {
        $archivo = $root . '/bd/database.php';
    }
    // CASO 2: Si es un Controlador
    elseif (strpos($class, 'Controller') !== false) {
        // Quita la palabra 'Controller'
        $nombre_limpio = str_replace('Controller', '', $class);
        // Se pasa a minúsculas y añadimos la extensión
        $archivo = $root . '/controller/' . strtolower($nombre_limpio) . '.controller.php';
    }
    // CASO 3: Si es un DAO
    elseif (strpos($class, 'DAO') !== false) {
        // Buscamos en model/ y ponemos la primera letra minúscula
        $archivo = $root . '/model/' . lcfirst($class) . '.php';
    }
    // CASO 4: Si es una Entidad (clases simples como Usuario, Partida)
    else {
        // Busca en la carpeta de entidades
        $archivo = $root . '/model/entidades/' . strtolower($class) . '.php';
    }

    // Comprueba si el archivo existe
    if (file_exists($archivo)) {
        // Si existe, lo mete
        require_once $archivo;
    }else {
        echo "ERROR: No encuentro el archivo " . $archivo . "<br>";
    }
});

?>
