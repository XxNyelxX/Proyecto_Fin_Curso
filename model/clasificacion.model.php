<?php
class ClasificacionModel {
    
    private $pdo;

    public function __construct() {
        require_once '../config/database.php'; 
        $this->pdo = Database::Conectar();
    }

    public function ObtenerClasificacionMensual($mesActual, $anhoActual) {
        try {
            $sql = "SELECT username, foto, puntuacion_mensual 
                    FROM usuarios 
                    WHERE mes_ultimo_reinicio = ? 
                    AND anho_ultimo_reinicio = ? 
                    AND id_usuario > 16 
                    AND puntuacion_mensual != 0
                    ORDER BY puntuacion_mensual DESC";
            
            $stm = $this->pdo->prepare($sql);
            
            $stm->execute([$mesActual, $anhoActual]);
            
            return $stm->fetchAll(PDO::FETCH_OBJ);
            
        } catch (Exception $e) {
            error_log("Error al consultar la clasificación: " . $e->getMessage());
            return [];
        }
    }
}
?>