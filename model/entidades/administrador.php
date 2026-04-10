<?php

require_once 'Usuario.php';

class Administrador extends Usuario {
    
    public function __construct() {
        parent::__construct();
        // Forzamos que por defecto todo objeto Administrador tenga el rol 1
        $this->id_rol = 1; 
    }
    
    // Aquí en el futuro irán los métodos exclusivos del administrador
    // (Ejemplo: banearJugador, borrarPartida, etc.)
}
?>