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
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)";
            
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
            $sql = "INSERT INTO partidas_jugadores (id_partida, id_usuario, vidas_restantes) 
                    SELECT ?, ?, vidas FROM partidas WHERE id_partida = ?";
            $stm1 = $this->pdo->prepare($sql);
            // Pasamos el id_partida dos veces (una para el INSERT y otra para el WHERE del SELECT)
            $exito = $stm1->execute([$id_partida, $id_usuario, $id_partida]);

            // Suma 1 al contador de la partida
            if ($exito) {
                $sql2 = "UPDATE partidas SET num_jugadores = num_jugadores + 1 WHERE id_partida = ?";
                $stm2 = $this->pdo->prepare($sql2);
                $stm2->execute([$id_partida]);
            }
            return $exito;
            
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
            $sql = "SELECT pj.id_partida_jugador, u.id_usuario, u.username, u.foto, pj.vidas_restantes 
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
        $sql = "INSERT INTO partidas_jugadores (id_partida, id_usuario, vidas_restantes) 
        SELECT ?, ?, vidas FROM partidas WHERE id_partida = ?";
        $stm1 = $this->pdo->prepare($sql);
        $stm1->execute([$id_partida, $id_usuario, $id_partida]);

        // Suma 1 al contador de la partida
        $sql2 = "UPDATE partidas SET num_jugadores = num_jugadores + 1 WHERE id_partida = ?";
        $stm2 = $this->pdo->prepare($sql2);
        $stm2->execute([$id_partida]);
    }

    public function SalirDePartida($id_partida, $id_usuario) {
        // Elimina el registro del jugador
        $sql1 = "DELETE FROM partidas_jugadores WHERE id_partida = ? AND id_usuario = ?";
        $stm1 = $this->pdo->prepare($sql1);
        $stm1->execute([$id_partida, $id_usuario]);

        // Resta 1 al contador de la partida
        $sql2 = "UPDATE partidas SET num_jugadores = num_jugadores - 1 WHERE id_partida = ?";
        $stm2 = $this->pdo->prepare($sql2);
        $stm2->execute([$id_partida]);
    }

    public function CambiarHost($id_partida, $nuevo_host_id) {
        $sql = "UPDATE partidas SET id_host = ? WHERE id_partida = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$nuevo_host_id, $id_partida]);
    }

    public function IniciarPartida($id_partida, $silaba_inicial) {
        try {
            // 1. Cambiamos estado, ponemos el turno al 0 (el host) y añadimos la sílaba
            $sql = "UPDATE partidas SET estado = 'iniciada', turno_actual = 0, silaba_actual = ?, contador_silaba = 1 WHERE id_partida = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$silaba_inicial, $id_partida]);

        } catch (Exception $e) {
            // Si hay error, lo podemos ver en la respuesta del servidor
            error_log($e->getMessage());
        }
    }

    public function FinalizarPartida($id_partida) {
        $sql = "UPDATE partidas SET estado = 'finalizada' WHERE id_partida = ?";
        $stm = $this->pdo->prepare($sql);
        $stm->execute([$id_partida]);
    }

    public function AvanzarTurno($id_partida, $siguiente_turno, $nueva_silaba, $nuevo_contador) {
        try {
            $sql = "UPDATE partidas SET turno_actual = ?, silaba_actual = ?, contador_silaba = ? WHERE id_partida = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$siguiente_turno, $nueva_silaba, $nuevo_contador, $id_partida]);
        } catch (Exception $e) {
            error_log("Error al avanzar turno: " . $e->getMessage());
        }
    }

    public function GuardarJugada($id_partida, $id_usuario, $silaba, $palabra_acertada, $puntos_ganados) {
        try {
            $sql = "INSERT INTO partidas_jugadas (id_partida, id_usuario, silaba, palabra_acertada, puntos_ganados) 
                    VALUES (?, ?, ?, ?, ?)";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida, $id_usuario, $silaba, $palabra_acertada, $puntos_ganados]);
        } catch (Exception $e) {
            error_log("Error al guardar la jugada: " . $e->getMessage());
        }
    }

    public function ProcesarExplosion($id_partida, $id_pj_afectado, $siguiente_turno, $nueva_silaba, $nuevo_contador) {
        try {
            // Restamos una vida SOLO a la silla que perdió el turno
            $sql1 = "UPDATE partidas_jugadores 
                    SET vidas_restantes = vidas_restantes - 1 
                    WHERE id_partida_jugador = ?";
            $stm1 = $this->pdo->prepare($sql1);
            $stm1->execute([$id_pj_afectado]);

            // Actualizamos la partida con el nuevo turno y la nueva sílaba
            $sql2 = "UPDATE partidas SET turno_actual = ?, silaba_actual = ?, contador_silaba = ? WHERE id_partida = ?";
            $stm2 = $this->pdo->prepare($sql2);
            $stm2->execute([$siguiente_turno, $nueva_silaba, $nuevo_contador, $id_partida]);

            return true;
        } catch (Exception $e) {
            error_log("Error en ProcesarExplosion: " . $e->getMessage());
            return false;
        }
    }

    public function DeclararGanador($id_partida, $id_ganador) {
        try {
            $sql = "UPDATE partidas SET estado = 'finalizada', id_ganador = ? WHERE id_partida = ?";
            $stm = $this->pdo->prepare($sql);
            return $stm->execute([$id_ganador, $id_partida]);
        } catch (Exception $e) {
            error_log("Error al declarar ganador: " . $e->getMessage());
            return false;
        }
    }

    public function ListarPartidasAbiertas() {
        try {
            // Traemos las partidas en espera. 
            // También traemos el nombre del host por si quieres mostrarlo.
            $sql = "SELECT p.*, u.username as nombre_host 
                    FROM partidas p
                    JOIN usuarios u ON p.id_host = u.id_usuario
                    WHERE p.estado = 'esperando'
                    ORDER BY p.fecha_partida DESC";
            
            $stm = $this->pdo->prepare($sql);
            $stm->execute();
            
            // Devolvemos los objetos para que el foreach del HTML funcione
            return $stm->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Error al listar partidas: " . $e->getMessage());
            return [];
        }
    }

    public function SumarPuntosUsuario($id_usuario, $puntos) {
        try {
            // Evita que los usuarios anónimos (IDs del 1 al 16) sumen puntos
            if ($id_usuario <= 16) {
                return true;
            }

            // Obtiene el mes y año actuales reales
            $mesActual = (int)date('n');
            $anhoActual = (int)date('Y');

            // Consulta el estado actual del usuario
            $sqlCheck = "SELECT mes_ultimo_reinicio, anho_ultimo_reinicio FROM usuarios WHERE id_usuario = ?";
            $stmCheck = $this->pdo->prepare($sqlCheck);
            $stmCheck->execute([$id_usuario]);
            $usuario = $stmCheck->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                // Si el mes o el año no coinciden, limpia la puntuación vieja
                if ((int)$usuario['mes_ultimo_reinicio'] !== $mesActual || (int)$usuario['anho_ultimo_reinicio'] !== $anhoActual) {
                    $sqlReset = "UPDATE usuarios SET puntuacion_mensual = 0, mes_ultimo_reinicio = ?, anho_ultimo_reinicio = ? WHERE id_usuario = ?";
                    $stmReset = $this->pdo->prepare($sqlReset);
                    $stmReset = $stmReset->execute([$mesActual, $anhoActual, $id_usuario]);
                }
            }

            // Suma los puntos acumulados en la jugada
            $sqlSumar = "UPDATE usuarios SET puntuacion_mensual = puntuacion_mensual + ? WHERE id_usuario = ?";
            $stmSumar = $this->pdo->prepare($sqlSumar);
            $stmSumar->execute([$puntos, $id_usuario]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error al gestionar la puntuación mensual del usuario: " . $e->getMessage());
            return false;
        }
    }

    public function PalabraYaUsada($id_partida, $palabra) {
        try {
            $sql = "SELECT COUNT(*) FROM partidas_jugadas WHERE id_partida = ? AND palabra_acertada = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id_partida, $palabra]);
            
            // Si devuelve más de 0, es que ya se usó
            return $stm->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function EliminarVidasPorAbandono($id_partida, $id_usuario) {
        try {
            $sql = "UPDATE partidas_jugadores 
                    SET vidas_restantes = 0 
                    WHERE id_partida = ? AND id_usuario = ?";
            $stm = $this->pdo->prepare($sql);
            return $stm->execute([$id_partida, $id_usuario]);
        } catch (Exception $e) {
            error_log("Error al quitar vidas por abandono: " . $e->getMessage());
            return false;
        }
    }

}
?>