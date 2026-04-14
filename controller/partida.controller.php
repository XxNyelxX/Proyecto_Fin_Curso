<?php
class PartidaController {
    
    private $modelo;

    public function __construct() {
        // Aquí conectaremos el modelo de la partida más adelante
    }

    // Esta es la acción que llama tu botón (?a=Crear)
    public function Crear() {
        // SEGURIDAD: Solo usuarios logueados pueden crear partidas
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=acceso&a=Entrar");
            exit();
        }

        // Cargamos la vista del formulario
        require_once '../view/header.php';
        require_once '../view/partida/crear-form.php';
        require_once '../view/footer.php';
    }
}
?>