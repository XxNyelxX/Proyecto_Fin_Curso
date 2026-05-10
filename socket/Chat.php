<?php
//Este archivo define que pasa cunado alguien interactua con el servidor

//si creo ota clase chat usa este "apellido" para diferenciar
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $players;

    public function __construct() {
        // Guardaremos a todos los jugadores conectados aquí
        $this->players = new \SplObjectStorage;
        echo "Servidor iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Se ejecuta cuando alguien entra en la sala
        $this->players->attach($conn);
        echo "Nueva conexión ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Traducimos el texto que llega a un array de PHP
        $datos = json_decode($msg, true);

        // Comprobamos si es un JSON válido y si trae un 'tipo' de acción
        if ($datos !== null && isset($datos['tipo'])) {
            
            // Evaluamos qué quiere hacer el usuario
            switch ($datos['tipo']) {
                //Cuando alguien se mete
                case 'conexion_nueva':
                    $from->id_usuario = $datos['id_usuario'];
                    $from->id_partida = $datos['id_partida'];
                    
                    echo "Xogador {$datos['username']} uniuse a sala {$from->id_partida}\n";
                    
                    // Preparamos el aviso con los datos del nuevo jugador
                    $aviso = [
                        'tipo' => 'nuevo_jugador',
                        'id_usuario' => $datos['id_usuario'],
                        'username' => $datos['username'],
                        'foto' => $datos['foto'],
                        'es_host' => $datos['es_host']
                    ];
                    
                    // Recorremos a TODOS los clientes conectados
                    foreach ($this->players as $player) {
                        // Si el jugador está en ESTA MISMA SALA y NO ES el que acaba de entrar...
                        if (isset($player->id_partida) && $player->id_partida == $from->id_partida && $player !== $from) {
                            // ... le enviamos el aviso
                            $player->send(json_encode($aviso));
                        }
                    }
                    break;

                    //Cuando se añade un clon se recarga la mesa para mostrarlo
                    case 'recargar_mesa':
                    // Un jugador pide que todos los de su sala actualicen la pantalla
                    $aviso = [
                        'tipo' => 'recargar_mesa'
                    ];
                    
                    foreach ($this->players as $player) {
                        if (isset($player->id_partida) && $player->id_partida == $from->id_partida && $player !== $from) {
                            $player->send(json_encode($aviso));
                        }
                    }
                    break;

                    case 'expulsion':
                    $aviso = [
                        'tipo' => 'expulsion',
                        'id_expulsado' => $datos['id_expulsado']
                    ];
                    
                    // Avisamos a todos los de la sala de que alguien ha sido echado
                    foreach ($this->players as $player) {
                        if (isset($player->id_partida) && $player->id_partida == $from->id_partida && $player !== $from) {
                            $player->send(json_encode($aviso));
                        }
                    }
                    break;

                    case 'tecleando':
                    // Empaquetamos lo que está escribiendo el jugador
                    $aviso_teclado = [
                        'tipo' => 'tecleando',
                        'palabra' => $datos['palabra'],
                        'slot' => $datos['slot']
                    ];
                    
                    // Repartimos las letras a todos los que estén en la MISMA partida, excepto al que las escribió
                    foreach ($this->players as $player) {
                        if (isset($player->id_partida) && $player->id_partida == $datos['id_partida'] && $player !== $from) {
                            $player->send(json_encode($aviso_teclado));
                        }
                    }
                    break;

                    case 'palabra_acertada':
                    $aviso_acierto = [
                        'tipo' => 'palabra_acertada',
                        'palabra' => $datos['palabra'],
                        'slot' => $datos['slot']
                    ];
                    
                    // Repartimos el aviso a todos los de la sala, excepto al que acertó
                    foreach ($this->players as $player) {
                        if (isset($player->id_partida) && $player->id_partida == $datos['id_partida'] && $player !== $from) {
                            $player->send(json_encode($aviso_acierto));
                        }
                    }
                    break;

                    case 'palabra_fallada':
                    $aviso_fallo = [
                        'tipo' => 'palabra_fallada',
                        'slot' => $datos['slot']
                    ];
                    
                    // Repartimos el aviso a todos los de la sala, excepto al que falló
                    foreach ($this->players as $player) {
                        if (isset($player->id_partida) && $player->id_partida == $datos['id_partida'] && $player !== $from) {
                            $player->send(json_encode($aviso_fallo));
                        }
                    }
                    break;
                    
                // Aquí iremos añadiendo más 'case' (escribir_letra, arrancar_juego, etc.)
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Lo sacamos de la lista del servidor
        $this->players->detach($conn);

        // Si el jugador había llegado a identificarse con ID y Sala
        if (isset($conn->id_partida) && isset($conn->id_usuario)) {
            
            $aviso = [
                'tipo' => 'desconexion',
                'id_usuario' => $conn->id_usuario
            ];

            // Avisamos al resto de jugadores de su sala
            foreach ($this->players as $player) {
                if (isset($player->id_partida) && $player->id_partida == $conn->id_partida) {
                    $player->send(json_encode($aviso));
                }
            }
            
            echo "Xogador {$conn->id_usuario} desconectouse da sala {$conn->id_partida}\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}
?>