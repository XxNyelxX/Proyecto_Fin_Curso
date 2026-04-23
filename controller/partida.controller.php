<?php
class PartidaController {
    
    private $modelo;

    public function __construct() {
        require_once '../model/partida.model.php';
        $this->modelo = new PartidaModel();
    }

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

    public function GuardarPartida() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=acceso&a=Entrar");
            exit();
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Recogemos y saneamos los datos
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $visibilidad = isset($_POST['visibilidad']) ? $_POST['visibilidad'] : 'publica';
            $contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';
            $tiempo = isset($_POST['tiempo_bomba']) ? (int)$_POST['tiempo_bomba'] : 5;
            $turnos = isset($_POST['turnos_silaba']) ? (int)$_POST['turnos_silaba'] : 2;
            $vidas = isset($_POST['vidas']) ? (int)$_POST['vidas'] : 2;
            $max_jugadores = isset($_POST['max_jugadores']) ? (int)$_POST['max_jugadores'] : 4;
            $id_host = $_SESSION['user_id'];

            // Validaciones
            if ($nombre === '') {
                $errores['nombre'] = "O nome da sala non pode estar baleiro.";
            } else {
                if (strlen($nombre) < 5) {
                    $errores['nombre'] = "O nome da sala debe ter polo menos 5 caracteres.";
                } else if (strlen($nombre) > 60) {
                    $errores['nombre'] = "O nome da sala non pode superar os 60 caracteres.";
                }
            }

            if ($visibilidad !== 'publica' && $visibilidad !== 'privada') {
                $errores['db'] = "Valor de visibilidade non válido.";
            }
            if ($visibilidad === 'publica') {
                $contrasena = '';
            } else if ($visibilidad === 'privada' && $contrasena === '') {
                $visibilidad = 'publica';
            }

            if ($tiempo < 1 || $tiempo > 10) {
                $errores['db'] = "O tempo da bomba debe estar entre 1 e 10 segundos.";
            }

            if ($turnos < 1 || $turnos > 16) {
                $errores['db'] = "Os turnos máximos da sílaba deben estar entre 1 e 16.";
            }

            if ($vidas < 1 || $vidas > 3) {
                $errores['db'] = "As vidas deben estar entre 1 e 3.";
            }

            if ($max_jugadores < 2 || $max_jugadores > 16) {
                $errores['db'] = "O máximo de xogadores debe estar entre 2 e 16.";
            }

            if (!$this->modelo->ExisteUsuario($id_host)) {
                $errores['db'] = "O usuario non é válido ou a sesión expirou.";
            }

            if (empty($errores)) {
                // Llamamos al modelo para insertar en la BD
                $id_partida = $this->modelo->CrearPartida($nombre, $visibilidad, $contrasena, $tiempo, $turnos, $vidas, $max_jugadores, $id_host);

                if ($id_partida) {
                    // Si se creó con éxito, vamos a la sala
                    header("Location: ?c=partida&a=Sala&id=" . $id_partida);
                    exit();
                } else {
                    $errores['db'] = "Houbo un erro ao crear a partida. Inténtao de novo.";
                }
            }

            // Si llegamos aquí es porque hubo errores, recargamos la vista
            require_once '../view/header.php';
            require_once '../view/partida/crear-form.php';
            require_once '../view/footer.php';
        }
    }

    public function Sala() {
        if (!isset($_GET['id'])) {
            header("Location: index.php");
            exit();
        }

        $id_partida = (int)$_GET['id'];
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        if (!$partida) {
            header("Location: index.php");
            exit();
        }

        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
        $total_actual = count($jugadores);

        // --- LÓGICA DE INVITADOS ANÓNIMOS ---
        if (!isset($_SESSION['user_id'])) {
            
            // Si la sala está llena, al index sin avisos
            if ($total_actual >= $partida['max_jugadores']) {
                header("Location: index.php");
                exit();
            }

            // Buscamos un Anónimo libre (del 1 al 16)
            $ids_ocupadas = array_column($jugadores, 'id_usuario');
            $id_anonimo_asignar = null;

            for ($i = 1; $i <= 16; $i++) {
                if (!in_array($i, $ids_ocupadas)) {
                    $id_anonimo_asignar = $i;
                    break;
                }
            }

            // Si hay hueco, le prestamos la ID y recargamos
            if ($id_anonimo_asignar) {
                $_SESSION['user_id'] = $id_anonimo_asignar;
                // Guardamos el nombre en sesión para evitar una consulta extra a la BD
                $_SESSION['username'] = 'Anónimo ' . $id_anonimo_asignar; 
                
                $this->modelo->UnirJugadorAPartida($id_partida, $id_anonimo_asignar);
                
                header("Location: ?c=partida&a=Sala&id=" . $id_partida);
                exit();
            } else {
                // Si no quedan anónimos libres, al index sin avisos
                header("Location: index.php");
                exit();
            }
        }
        // ------------------------------------

        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        require_once '../view/header.php';
        require_once '../view/partida/sala.php';
    }

    public function Abandonar() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
            header("Location: index.php");
            exit();
        }

        $id_partida = (int)$_GET['id'];
        $id_usuario = $_SESSION['user_id'];
        
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        if ($partida) {
            // Si el que se va es el Host
            if ($partida['id_host'] == $id_usuario) {
                
                $nuevoHost = null;
                // Buscamos al primer jugador que no sea el host ni sus clones
                foreach ($jugadores as $j) {
                    if ($j['id_usuario'] != $id_usuario) {
                        $nuevoHost = $j['id_usuario'];
                        break;
                    }
                }

                if ($nuevoHost) {
                    // Hay un jugador real, le damos el host
                    $this->modelo->CambiarHost($id_partida, $nuevoHost);
                } else {
                    // Estaba solo, cerramos la partida
                    $this->modelo->FinalizarPartida($id_partida);
                }
            }

            // Borramos al jugador
            $this->modelo->SalirDePartida($id_partida, $id_usuario);
        }

        // Si es un anónimo (ID 1-16), destruimos la sesión al salir
        if ($id_usuario >= 1 && $id_usuario <= 16) {
            session_destroy();
        }

        // Lo mandamos al inicio
        header("Location: index.php");
        exit();
    }

    public function AnadirClon() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'No autorizado']);
            return;
        }

        $id_partida = (int)$_GET['id'];
        $id_usuario = $_SESSION['user_id']; // Usamos tu propia ID

        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        if ($partida && $partida['id_host'] == $id_usuario) {
            $total_actual = count($jugadores);
            
            if ($total_actual < $partida['max_jugadores']) {
                // Hay hueco, te clonamos metiendo tu misma ID
                $this->modelo->UnirJugadorAPartida($id_partida, $id_usuario);
                
                echo json_encode([
                    'status' => 'ok', 
                    'jugadores_actuales' => $total_actual + 1, 
                    'max_jugadores' => $partida['max_jugadores']
                ]);
                return;
            }
        }

        echo json_encode(['status' => 'error', 'mensaje' => 'Sala llena o no autorizado']);
    }

}
?>