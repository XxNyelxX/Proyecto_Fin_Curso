<?php
require_once '../model/clasificacion.model.php';

class ClasificacionController {
    private $modelo;

    public function __construct() {
        // preparamos el modelo para usar sus consultas
        $this->modelo = new ClasificacionModel();
    }

    public function Clasificacion() {
        // 1. Obtenemos el mes actual (del 1 al 12)
        $mesActual = date('n');
        
        // 2. Pedimos los datos al modelo y los guardamos en una variable llamada $jugadores
        $jugadores = $this->modelo->ObtenerClasificacionMensual($mesActual);
        
        // 3. Cargamos las vistas. 
        // Como incluimos la vista DESPUÉS de definir $jugadores, la vista podrá leer esa variable.

        require_once '../view/header.php';
        require_once '../view/clasificacion/clasificacion.php';
        require_once '../view/footer.php';
    }
}
?>