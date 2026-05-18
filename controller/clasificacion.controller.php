<?php
require_once '../model/clasificacion.model.php';

class ClasificacionController {
    private $modelo;

    public function __construct() {
        // preparamos el modelo para usar sus consultas
        $this->modelo = new ClasificacionModel();
    }

    public function Clasificacion() {

        // Solo los usuarios sin sesión anónima entran
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] <= 16) {
            header("Location: index.php");
            exit();
        }

        // mes y el año actuales
        $mesActual = date('n');
        $anhoActual = date('Y');
        
        // Pide los datos al modelo y los guarda
        $jugadores = $this->modelo->ObtenerClasificacionMensual($mesActual, $anhoActual);
        
        // Carga las vistas. 
        // Como incluye la vista DESPUÉS de definir $jugadores, la vista podrá leer esa variable.

        require_once '../view/header.php';
        require_once '../view/clasificacion/clasificacion.php';
        require_once '../view/footer.php';
    }
}
?>