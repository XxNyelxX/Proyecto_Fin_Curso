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

        if (!$partida || $partida['estado'] === 'finalizada') {
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
        } else {
            $id_usuario = $_SESSION['user_id'];
            // Si no es el host ni sus clones, comprobamos si ya está en la sala
            $ya_esta_dentro = $this->modelo->ComprobarSiEstaEnPartida($id_partida, $id_usuario);
            
            // Si hay hueco, lo metemos
            if (!$ya_esta_dentro && $total_actual < $partida['max_jugadores']) {
                $this->modelo->AñadirJugadorAPartida($id_partida, $id_usuario);
                header("Location: ?c=partida&a=Sala&id=" . $id_partida);
                exit();
            }
        }

        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        if (isset($_SESSION['user_id'])) {
            $sigoDentro = false;
            foreach ($jugadores as $j) {
                if ($j['id_usuario'] == $_SESSION['user_id']) {
                    $sigoDentro = true;
                    break;
                }
            }

            // Si el sendBeacon lo borró al recargar, sigoDentro será false y lo echamos
            if (!$sigoDentro) {
                header("Location: ?c=inicio&a=Index");
                exit(); 
            }
        }

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

    public function DatosSalaJSON() {
        header('Content-Type: application/json');
        
        if (!isset($_GET['id'])) {
            echo json_encode(['error' => 'No hay ID']);
            return;
        }

        $id_partida = (int)$_GET['id'];
        
        // Sacamos los datos de la Base de Datos
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        // Los enviamos empaquetados para que JavaScript los lea
        echo json_encode([
            'id_host' => $partida['id_host'],
            'max_jugadores' => $partida['max_jugadores'],
            'vidas' => $partida['vidas'],
            'estado' => $partida['estado'],
            'turno_actual' => $partida['turno_actual'],
            'silaba_actual' => $partida['silaba_actual'],
            'tiempo_bomba' => $partida['tiempo_bomba'],
            'contador_silaba' => $partida['contador_silaba'],
            'jugadores' => $jugadores
        ]);
    }

    public function ExpulsarJugador() {
        header('Content-Type: application/json');

        // Comprobamos que nos mandan los datos
        if (!isset($_GET['id_partida']) || !isset($_GET['id_usuario'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']);
            return;
        }

        $id_partida = (int)$_GET['id_partida'];
        $id_expulsado = (int)$_GET['id_usuario'];

        // SEGURIDAD: Comprobamos que el que pide esto es el Host real
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        if ($partida['id_host'] != $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Non tes permisos']);
            return;
        }

        $this->modelo->SalirDePartida($id_partida, $id_expulsado);

        echo json_encode(['status' => 'ok']);
    }

    public function Empezar() {
        //Le dice al navegador que vamos a enviar json y no una web
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
            echo json_encode(['status' => 'expulsar']);
            return;
        }

        $id_partida = (int)$_GET['id'];
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        // Solo el Host arranca la partida y solo si está en "espera"
        if ($partida && $partida['id_host'] == $_SESSION['user_id'] && $partida['estado'] === 'esperando') {
            
            $silaba_inicial = $this->GenerarSilabaAleatoria();

            // Llamamos a la función que creaste en el modelo
            $this->modelo->IniciarPartida($id_partida, $silaba_inicial, $partida['vidas']);
            
            // Volvemos a la sala (que ahora estará en modo "iniciada")
            echo json_encode(['status' => 'ok']);
            return;
        } else {
            echo json_encode(['status' => 'ignorar']);
        }
    }

    private function GenerarSilabaAleatoria() {
        $silabas = [
            'PA', 'MA', 'TE', 'RO', 'LA', 'DO', 'SA', 'MI',
            'CA', 'LI', 'PO', 'TU', 'RE', 'FI', 'SO', 'NU',
            'BE', 'TO', 'CU', 'VI', 'BA', 'DI', 'FA', 'PI'
        ];
        return $silabas[array_rand($silabas)];
    }

    public function ValidarPalabra() {
        // Obligamos a PHP a devolver JSON
        header('Content-Type: application/json');

        if (isset($_GET['palabra']) && isset($_GET['id_partida'])) {
            $palabra_usuario = mb_strtolower(trim($_REQUEST['palabra']), 'UTF-8');
            $id_partida = (int)$_GET['id_partida'];
            
            // Quitamos tildes por si acaso
            $sustituciones = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ï'=>'i', 'ü'=>'u'];
            $palabra_usuario = strtr($palabra_usuario, $sustituciones);

            $existe = false;
            $ruta_diccionario = __DIR__ . '/../data/diccionario.json';

            if (file_exists($ruta_diccionario)) {
                // Leemos el diccionario
                $json_data = file_get_contents($ruta_diccionario);
                
                // Para hacerlo más rápido
                // primero decodificamos el JSON a un array de PHP.
                $array_palabras = json_decode($json_data, true);

                // Comprobamos si la palabra existe en el array
                if (is_array($array_palabras) && in_array($palabra_usuario, $array_palabras)) {
                    $existe = true;

                    // Obtenemos los datos actuales de la partida
                    $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
                    
                    // Obtenemos los jugadores para saber cuántos son
                    $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
                    $total_jugadores = count($jugadores);
                    
                    if ($partida && $total_jugadores > 0) {

                        // Guardamos el registro de la jugada en la tabla partidas_jugadas
                        // Usamos la sílaba que estaba activa ANTES del cambio
                        $this->modelo->GuardarJugada(
                            $id_partida, 
                            $_SESSION['user_id'], 
                            $partida['silaba_actual'], 
                            $palabra_usuario, 
                            0
                        );
                        // Calculamos el siguiente turno
                        $siguiente_turno = ($partida['turno_actual'] + 1) % $total_jugadores;
                        $vueltas = 0;

                        // Mientras el siguiente jugador tenga 0 vidas, seguimos saltando
                        while ($jugadores[$siguiente_turno]['vidas_restantes'] <= 0 && $vueltas < $total_jugadores) {
                            $siguiente_turno = ($siguiente_turno + 1) % $total_jugadores;
                            $vueltas++;
                        }
                        
                        // Generamos la nueva sílaba y reseteamos el contador
                        $nueva_silaba = $this->GenerarSilabaAleatoria();
                        $nuevo_contador = 1;
                        
                        // Guardamos en la BD
                        $this->modelo->AvanzarTurno($id_partida, $siguiente_turno, $nueva_silaba, $nuevo_contador);
                    }
                }
            }

            echo json_encode([
                'status' => 'ok',
                'palabra' => $palabra_usuario,
                'existe' => $existe
            ]);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'No hay palabra']);
        }
        exit;
    }

    public function TiempoAgotado() {
        header('Content-Type: application/json');

        if (isset($_GET['id_partida'])) {
            $id_partida = (int)$_GET['id_partida'];
            $turno_esperado = (int)$_GET['turno_esperado'];
            $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
            $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
            $total_jugadores = count($jugadores);

            if ($partida && $total_jugadores > 0) {
                // Si el turno actual en la BD ya no es el que el Host nos dice, 
                // lo ignoramos para no restarle vida al jugador equivocado.
                if ($partida['turno_actual'] !== $turno_esperado) {
                    echo json_encode(['status' => 'ignorar']);
                    exit;
                }

                // Ahora usamos el id_partida_jugador en lugar del id_usuario
                $id_pj_afectado = $jugadores[$partida['turno_actual']]['id_partida_jugador'];

                // Simulamos la resta de vida que va a sufrir para que el bucle lo tenga en cuenta
                $jugadores[$partida['turno_actual']]['vidas_restantes'] -= 1;

                // Contamos cuántos siguen vivos después de que alguien pierda
                $supervivientes = [];
                foreach ($jugadores as $j) {
                    if ($j['vidas_restantes'] > 0) {
                        $supervivientes[] = $j;
                    }
                }

                // Si solo queda un ganador
                if (count($supervivientes) === 1) {
                    $ganador = $supervivientes[0];
                    
                    // Primero restamos la vida al que perdió
                    $this->modelo->ProcesarExplosion($id_partida, $id_pj_afectado, $partida['turno_actual'], $partida['silaba_actual'], $partida['contador_silaba']);
                    
                    // Luego declaramos al ganador
                    $this->modelo->DeclararGanador($id_partida, $ganador['id_usuario']);
                    
                    echo json_encode(['status' => 'finalizada', 'ganador' => $ganador['username']]);
                    exit;
                }
                
                // Calculamos el siguiente turno
                $siguiente_turno = ($partida['turno_actual'] + 1) % $total_jugadores;
                $vueltas = 0;
                
                // Mientras el siguiente jugador tenga 0 vidas, seguimos saltando
                while ($jugadores[$siguiente_turno]['vidas_restantes'] <= 0 && $vueltas < $total_jugadores) {
                    $siguiente_turno = ($siguiente_turno + 1) % $total_jugadores;
                    $vueltas++;
                }

                $contador_actual = (int)$partida['contador_silaba'];
                $turnos_maximos = (int)$partida['turnos_silaba'];
                
                // Si explota y aun no llega al límite establecido, se repite
                if ($contador_actual < $turnos_maximos) {
                    $nueva_silaba = $partida['silaba_actual'];
                    $nuevo_contador = $contador_actual + 1;
                } else {
                    $nueva_silaba = $this->GenerarSilabaAleatoria();
                    $nuevo_contador = 1;
                }
                
                // Ejecutamos en la BD
                $exito = $this->modelo->ProcesarExplosion($id_partida, $id_pj_afectado, $siguiente_turno, $nueva_silaba, $nuevo_contador);
                
                echo json_encode(['status' => $exito ? 'ok' : 'error']);
            }
        }
        exit;
    }

    public function Unirse() {
        // Pedimos al modelo todas las partidas con estado 'esperando'
        $partidas = $this->modelo->ListarPartidasAbiertas();

        require_once '../view/header.php';
        require_once '../view/partida/unirse.php';
        require_once '../view/footer.php';
    }

}
?>