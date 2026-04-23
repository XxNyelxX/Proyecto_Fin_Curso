<?php
class PartidaModel {

    private $pdo;

    public function __construct() {
        require_once '../config/database.php'; 
        $this->pdo = Database::Conectar();
    }

    public function CrearPartida($nombre, $visibilidad, $contrasena, $tiempo, $turnos, $vidas, $max_jugadores, $id_host) {
        try {
            // Añadimos max_jugadores a la consulta SQL
            $sql = "INSERT INTO partidas (nombre, visibilidad, contrasena, tiempo_bomba, turnos_silaba, vidas, num_jugadores, max_jugadores, id_host) 
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)";
            
            $stm = $this->pdo->prepare($sql);
            
            $stm->execute([$nombre, $visibilidad, $contrasena, $tiempo, $turnos, $vidas, $max_jugadores, $id_host]);
            
            // Guardamos el ID de la partida recién creada
            $id_partida = $this->pdo->lastInsertId();
            
            // Metemos al host automáticamente en la sala
            $this->AñadirJugadorAPartida($id_partida, $id_host);
            
            // Devolvemos el ID al controlador para que pueda redireccionar a la sala
            return $id_partida;
            
        } catch (PDOException $e) {
            return false; 
        }
    }

    public function ExisteUsuario($id_usuario) {
        try {
            $sql = "SELECT id_usuario FROM usuarios WHERE id_usuario = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_usuario]);
            
            // Si devuelve más de 0 filas, el usuario existe
            return $stm->rowCount() > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }

    public function AñadirJugadorAPartida($id_partida, $id_usuario) {
        try {
            $sql = "INSERT INTO partidas_jugadores (id_partida, id_usuario) VALUES (?, ?)";
            $stm = $this->pdo->prepare($sql);
            return $stm->execute([$id_partida, $id_usuario]);
            
        } catch (PDOException $e) {
            return false; 
        }
    }

    public function ObtenerPartidaPorId($id_partida) {
        try {
            $sql = "SELECT * FROM partidas WHERE id_partida = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida]);
            
            return $stm->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return false;
        }
    }

    public function ObtenerJugadoresEnPartida($id_partida) {
        try {
            // Traemos el ID, nombre y foto de los usuarios unidos a esta partida, ordenados por su ID de llegada
            $sql = "SELECT u.id_usuario, u.username, u.foto 
                    FROM partidas_jugadores pj
                    JOIN usuarios u ON pj.id_usuario = u.id_usuario
                    WHERE pj.id_partida = ?
                    ORDER BY pj.id_partida_jugador ASC";
            
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida]);
            
            return $stm->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    public function ObtenerPartida($id_partida) {
        try {
            $sql = "SELECT * FROM partidas WHERE id_partida = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida]);
            return $stm->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return false;
        }
    }

    public function ListarJugadores($id_partida) {
        try {
            $sql = "SELECT u.id_usuario, u.username, u.foto 
                    FROM usuarios u
                    INNER JOIN partidas_jugadores pj ON u.id_usuario = jp.id_usuario
                    WHERE jp.id_partida = ?";
            
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida]);
            return $stm->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    public function ComprobarSiEstaEnPartida($id_partida, $id_usuario) {
        $sql = "SELECT COUNT(*) FROM partidas_jugadores WHERE id_partida = ? AND id_usuario = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$id_partida, $id_usuario]);
        return $stm->fetchColumn() > 0;
    }

    public function UnirJugadorAPartida($id_partida, $id_usuario) {
        $sql = "INSERT INTO partidas_jugadores (id_partida, id_usuario) VALUES (?, ?)";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$id_partida, $id_usuario]);
    }

    public function SalirDePartida($id_partida, $id_usuario) {
        $sql = "DELETE FROM partidas_jugadores WHERE id_partida = ? AND id_usuario = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$id_partida, $id_usuario]);
    }

    public function CambiarHost($id_partida, $nuevo_host_id) {
        $sql = "UPDATE partidas SET id_host = ? WHERE id_partida = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$nuevo_host_id, $id_partida]);
    }

    public function FinalizarPartida($id_partida) {
        $sql = "UPDATE partidas SET estado = 'finalizada' WHERE id_partida = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$id_partida]);
    }

}
?>