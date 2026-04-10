<?php
class ClasificacionModel {
    
    private $pdo;

    public function __construct() {
        require_once '../config/database.php'; 
        $this->pdo = Database::Conectar();
    }

    public function ObtenerClasificacionMensual($mesActual) {
        try {
            $sql = "SELECT username, foto, puntuacion_mensual 
                    FROM usuarios 
                    WHERE mes_ultimo_reinicio = ? AND puntuacion_mensual != 0
                    ORDER BY puntuacion_mensual DESC";
            
            $stm = $this->pdo->prepare($sql);
            
            $stm->execute([$mesActual]);
            
            return $stm->fetchAll(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            die();
        }
    }
}
?>