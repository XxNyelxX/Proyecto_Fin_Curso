<?php
class InicioController {
    // Acción que carga la vista de inicio
    public function Index() {
        require_once '../view/header.php';
        require_once '../view/inicio/inicio.php'; // Página principal
        require_once '../view/footer.php';
    }
}
?>