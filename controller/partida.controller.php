<?php
class PartidaController {
    
    private $modelo;

    public function __construct() {
        require_once '../model/partida.model.php';
        $this->modelo = new PartidaModel();
    }

    public function Crear() {
        // Solo los usuarios sin sesión anónima entran
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] <= 16) {
            header("Location: index.php");
            exit();
        }

        if (isset($_SESSION['username'])) {
            $nombre_defecto = "Sala de " . strtoupper($_SESSION['username']);
        } else {
            $nombre_defecto = "Sala de " . rand(100, 999);
        }

        // Carga la vista del formulario
        require_once '../view/header.php';
        require_once '../view/partida/crear-form.php';
        require_once '../view/footer.php';
    }

    public function GuardarPartida() {
        // Solo los usuarios sin sesión anónima entran
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] <= 16) {
            header("Location: index.php");
            exit();
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Recoge y sanea los datos
            $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
            $visibilidad = isset($_POST['visibilidad']) ? $_POST['visibilidad'] : 'publica';
            $contrasena = isset($_POST['contrasena']) ? trim($_POST['contrasena']) : '';
            $tiempo = isset($_POST['tiempo_bomba']) ? (int)$_POST['tiempo_bomba'] : 10;
            $turnos = isset($_POST['turnos_silaba']) ? (int)$_POST['turnos_silaba'] : 2;
            $vidas = isset($_POST['vidas']) ? (int)$_POST['vidas'] : 2;
            $max_jugadores = isset($_POST['max_jugadores']) ? (int)$_POST['max_jugadores'] : 4;

            
            $es_invitado = false;
            if (!isset($_SESSION['user_id'])) {
                $id_host = 1; // Al crearla, se le da automáticamente el puesto de Anónimo 1
                $es_invitado = true;
            } else {
                $id_host = $_SESSION['user_id'];
            }

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
            } else if ($visibilidad === 'privada') {
                if ($contrasena === '') {
                    $visibilidad = 'publica';
                } else if (strlen($contrasena) < 3) {
                    $errores['contrasena'] = "O contrasinal debe ter mínimo 3 caracteres.";
                }
            }

            if ($tiempo < 1 || $tiempo > 20) {
                $errores['db'] = "O tempo da bomba debe estar entre 1 e 20 segundos.";
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

            // Solo comprueba si el usuario existe si NO es un invitado
            if (!$es_invitado && !$this->modelo->ExisteUsuario($id_host)) {
                $errores['db'] = "O usuario non é válido ou a sesión expirou.";
            }

            if (empty($errores)) {
                // Llama al modelo para insertar en la BD
                $id_partida = $this->modelo->CrearPartida($nombre, $visibilidad, $contrasena, $tiempo, $turnos, $vidas, $max_jugadores, $id_host);

                if ($id_partida) {
                    // Crea la sesión del invitado justo antes de entrar
                    if ($es_invitado) {
                        $_SESSION['user_id'] = 1;
                        $_SESSION['username'] = 'Anónimo 1';
                    }
                    // Si se creó con éxito, va a la sala
                    header("Location: ?c=partida&a=Acceder&id=" . $id_partida);
                    exit();
                } else {
                    $errores['db'] = "Houbo un erro ao crear a partida. Inténtao de novo.";
                }
            }

            // Si hay errores y recarga, vuelve a generar la variable por si acaso
            if (isset($_SESSION['username'])) {
                $nombre_defecto = "Sala de " . strtoupper($_SESSION['username']);
            } else {
                $nombre_defecto = "Sala de " . rand(100, 999);
            }

            // Si llega aquí es porque hubo errores, recarga la vista
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
        $id_usuario = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // Comprueba si el usuario ya está registrado en la base de datos de esta partida
        $ya_esta_dentro = $this->modelo->ComprobarSiEstaEnPartida($id_partida, $id_usuario);

        // Comprueba si el usuario es Administrador (Rol 1)
        $es_admin = (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1);

        // Si intenta entrar por URL sin estar en la tabla, se expulsa al índice
        if (!$ya_esta_dentro && !$es_admin) {
            header("Location: index.php");
            exit();
        }

        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        if (!$partida || $partida['estado'] === 'finalizada') {
            header("Location: index.php");
            exit();
        }

        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        require_once '../view/header.php';
        require_once '../view/partida/sala.php';
    }

    public function Acceder() {
        if (!isset($_GET['id'])) {
            header("Location: index.php");
            exit();
        }

        $id_partida = (int)$_GET['id'];
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        // Si la partida no existe o no está en espera, prohíbe la entrada
        if (!$partida || $partida['estado'] !== 'esperando') {
            header("Location: index.php");
            exit();
        }

        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
        $total_actual = count($jugadores);

        // Gestión de usuarios anónimos y logueados
        if (!isset($_SESSION['user_id'])) {
            if ($total_actual >= $partida['max_jugadores']) {
                header("Location: index.php");
                exit();
            }

            // Busca un ID de anónimo libre (1-16)
            $ids_ocupadas = array_column($jugadores, 'id_usuario');
            $id_anonimo_asignar = null;
            for ($i = 1; $i <= 16; $i++) {
                if (!in_array($i, $ids_ocupadas)) {
                    $id_anonimo_asignar = $i;
                    break;
                }
            }

            if ($id_anonimo_asignar) {
                $_SESSION['user_id'] = $id_anonimo_asignar;
                $_SESSION['username'] = 'Anónimo ' . $id_anonimo_asignar;
                $this->modelo->UnirJugadorAPartida($id_partida, $id_anonimo_asignar);
            } else {
                header("Location: index.php");
                exit();
            }
        } else {
            $id_usuario = $_SESSION['user_id'];
            $ya_esta_dentro = $this->modelo->ComprobarSiEstaEnPartida($id_partida, $id_usuario);
            
            // Si hay hueco y no estaba dentro, se añade a la base de datos
            if (!$ya_esta_dentro && $total_actual < $partida['max_jugadores']) {
                $this->modelo->AñadirJugadorAPartida($id_partida, $id_usuario);
            }
        }

        // Tras realizar el registro en la base de datos, redirige a la sala real
        header("Location: ?c=partida&a=Sala&id=" . $id_partida);
        exit();
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
            // Si la partida ya ha empezado, le quita las vidas si se va
            if ($partida['estado'] === 'iniciada') {
                $this->modelo->EliminarVidasPorAbandono($id_partida, $id_usuario);

                // Vuelve a pedir la lista para ver las vidas actualizadas
                $jugadores_actualizados = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
                $vivos = 0;
                $ultimo_vivo = null;
                
                // Cuenta cuántos siguen vivos
                foreach ($jugadores_actualizados as $j) {
                    if ($j['vidas_restantes'] > 0) {
                        $vivos++;
                        $ultimo_vivo = $j['id_usuario'];
                    }
                }
                
                // Si solo queda un jugador o ninguno, se acaba la partida
                if ($vivos <= 1) {
                    if ($vivos == 1 && $ultimo_vivo) {
                        $this->modelo->DeclararGanador($id_partida, $ultimo_vivo);
                    } else {
                        $this->modelo->FinalizarPartida($id_partida);
                    }
                }

            } else if ($partida['estado'] === 'esperando'){
                // Si estaba en espera, se aplica la lógica normal de borrado libre
                if ($partida['id_host'] == $id_usuario) {
                    $nuevoHost = null;
                    foreach ($jugadores as $j) {
                        if ($j['id_usuario'] != $id_usuario) {
                            $nuevoHost = $j['id_usuario'];
                            break;
                        }
                    }

                    if ($nuevoHost) {
                        $this->modelo->CambiarHost($id_partida, $nuevoHost);
                    } else {
                        $this->modelo->FinalizarPartida($id_partida);
                    }
                }
                // Borra al jugador solo si no ha empezado
                $this->modelo->SalirDePartida($id_partida, $id_usuario);
            }
        }

        // Si es un anónimo (ID 1-16), destruye la sesión al salir
        if ($id_usuario >= 1 && $id_usuario <= 16) {
            session_destroy();
        }

        // Lo manda al inicio
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
        $id_usuario = $_SESSION['user_id']; // Usa la propia ID

        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        if ($partida && $partida['id_host'] == $id_usuario) {
            $total_actual = count($jugadores);
            
            if ($total_actual < $partida['max_jugadores']) {
                // Si hay hueco, lo clona metiendo su misma ID
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
        
        // Saca los datos de la Base de Datos
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);

        // Los envía empaquetados para que JavaScript los lea
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

        // Comprueba que se mandan los datos
        if (!isset($_GET['id_partida']) || !isset($_GET['id_usuario'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Datos incompletos']);
            return;
        }

        $id_partida = (int)$_GET['id_partida'];
        $id_expulsado = (int)$_GET['id_usuario'];

        // SEGURIDAD: Comprueba que el que pide esto es el Host real
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
        if ($partida['id_host'] != $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Non tes permisos']);
            return;
        }

        $this->modelo->SalirDePartida($id_partida, $id_expulsado);

        echo json_encode(['status' => 'ok']);
    }

    public function Empezar() {
        // Le dice al navegador que va a enviar json y no una web
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

            // Llama a la función del modelo
            $this->modelo->IniciarPartida($id_partida, $silaba_inicial, $partida['vidas']);
            
            // Vuelve a la sala (que ahora estará en modo "iniciada")
            echo json_encode(['status' => 'ok']);
            return;
        } else {
            echo json_encode(['status' => 'ignorar']);
        }
    }

    private function GenerarSilabaAleatoria() {
        $silabas = [
            // Básicas
            "MA", "PA", "TA", "CA", "LA", "SA", "DA", "BA", "VA", "RA", "NA", "FA", "GA", "CHA", "LLA", "NHA", "ZA", "XA",
            "ME", "PE", "TE", "CE", "LE", "SE", "DE", "BE", "VE", "RE", "NE", "FE", "CHE", "LLE", "XE",
            "MI", "PI", "TI", "CI", "LI", "SI", "DI", "BI", "VI", "RI", "NI", "FI", "CHI", "LLI", "XI",
            "MO", "PO", "TO", "CO", "LO", "SO", "DO", "BO", "VO", "RO", "NO", "FO", "GO", "CHO", "LLO", "ZO", "XO",
            "MU", "PU", "TU", "CU", "LU", "SU", "DU", "BU", "VU", "RU", "NU", "FU", "GU", "CHU", "LLU", "ZU", "XU",
            
            // Inversas
            "AL", "EL", "IL", "OL", "UL", "AR", "ER", "IR", "OR", "UR", "AS", "ES", "IS", "OS", "US", 
            "AN", "EN", "IN", "ON", "UN", "AM", "EM", "IM", "OM", "UM", "AC", "EC", "IC", "OC", "UC", "AD", "ED", "ID", "OD", "UD",
            
            // Pares de consonantes
            "CH", "LL", "NH", "RR", "PR", "TR", "CR", "BR", "GR", "FR", "BL", "CL", "FL", "GL", "PL", 
            "MB", "MP", "ND", "NT", "NC", "NG", "ST", "SP", "SC", "RS", "RT", "RC", "RD", "RN",
            
            // Trabadas
            "TRA", "TRE", "TRI", "TRO", "TRU", "BRA", "BRE", "BRI", "BRO", "BRU", "CRA", "CRE", "CRI", "CRO", "CRU", 
            "PRA", "PRE", "PRI", "PRO", "PRU", "GRA", "GRE", "GRI", "GRO", "GRU", "FRA", "FRE", "FRI", "FRO", "FRU",
            "BLA", "BLE", "BLI", "BLO", "BLU", "CLA", "CLE", "CLI", "CLO", "CLU", "PLA", "PLE", "PLI", "PLO", "PLU", 
            "FLA", "FLE", "FLI", "FLO", "FLU", "GLA", "GLE", "GLI", "GLO", "GLU",
            
            // Terminaciones y compuestas de 3 letras
            "CON", "COM", "CAN", "CAM", "CEN", "CEM", "CIN", "CIM", "COL", "CAL", "CUL", "CEL", "CIL",
            "DES", "DIS", "DAS", "DOS", "DUS", "DAN", "DEN", "DIN", "DON", "DUN", "DAL", "DEL", "DIL",
            "ENT", "EST", "AST", "IST", "OST", "UST",
            "PER", "POR", "PAR", "PUR", "PIR",
            "TAR", "TOR", "TIR", "TER", "TUR",
            "SUR", "SUB", "SUS", "SIN", "SON", "SAN", "SEN", "SUN",
            "MEN", "MAN", "MIN", "MON", "MUN", "MAL", "MEL", "MIL", "MOL", "MUL",
            "DAD", "TUD", "BLE", "QUE", "QUI", "GUE", "GUI", "GUA",
            "PAN", "PEN", "PIN", "PON", "PUN", "PAL", "PEL", "PIL", "POL", "PUL",
            "RAN", "REN", "RIN", "RON", "RUN", "RAL", "REL", "RIL", "ROL", "RUL",
            "TAN", "TEN", "TIN", "TON", "TUN", "TAL", "TEL", "TIL", "TOL", "TUL",
            "VAN", "VEN", "VIN", "VON", "VAL", "VEL", "VIL", "VOL", "VUL",
            "FAN", "FEN", "FIN", "FON", "FUN", "FAL", "FEL", "FIL", "FOL", "FUL"
        ];
        return $silabas[array_rand($silabas)];
    }

    public function ValidarPalabra() {
        // Obliga a PHP a devolver JSON
        header('Content-Type: application/json');

        if (isset($_GET['palabra']) && isset($_GET['id_partida'])) {
            $palabra_usuario = mb_strtolower(trim($_REQUEST['palabra']), 'UTF-8');
            $id_partida = (int)$_GET['id_partida'];
            $tiempo_restante = (int)$_GET['tiempo'];
            
            // Quita tildes por si acaso
            $sustituciones = ['á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u', 'ï'=>'i', 'ü'=>'u'];
            $palabra_usuario = strtr($palabra_usuario, $sustituciones);

            $existe = false;
            $ruta_diccionario = __DIR__ . '/../data/diccionario.json';

            if (file_exists($ruta_diccionario)) {
                // Lee el diccionario
                $json_data = file_get_contents($ruta_diccionario);
                
                // Para hacerlo más rápido
                // primero decodifica el JSON a un array de PHP.
                $array_palabras = json_decode($json_data, true);

                // Comprueba si la palabra existe en el array
                if (is_array($array_palabras) && in_array($palabra_usuario, $array_palabras)) {
                    // Comprueba en la BD si la palabra ya fue usada en esta partida
                    $ya_usada = $this->modelo->PalabraYaUsada($id_partida, $palabra_usuario);
                    
                    if (!$ya_usada) {
                        $existe = true;

                        // Obtiene los datos actuales de la partida
                        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);
                        
                        // Obtiene los jugadores para saber cuántos son
                        $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
                        $total_jugadores = count($jugadores);
                        
                        if ($partida && $total_jugadores > 0) {
                            $puntos_letras = mb_strlen($palabra_usuario, 'UTF-8') * 10;
                            $puntos_tiempo = $tiempo_restante * 100;
                            
                            // Si el tiempo restante llega como 0, se asegura un mínimo multiplicador para no dar 0 puntos
                            if ($puntos_tiempo <= 0) {
                                $puntos_tiempo = 100;
                            }
                            
                            $puntos_totales = $puntos_letras * $puntos_tiempo;

                            // Guarda el registro de la jugada en la tabla partidas_jugadas
                            // Usa la sílaba que estaba activa ANTES del cambio
                            $this->modelo->GuardarJugada(
                                $id_partida, 
                                $_SESSION['user_id'], 
                                $partida['silaba_actual'], 
                                $palabra_usuario, 
                                $puntos_totales
                            );

                            // Suma los puntos al perfil global del usuario
                            $this->modelo->SumarPuntosUsuario($_SESSION['user_id'], $puntos_totales);

                            // Calcula el siguiente turno
                            $siguiente_turno = ($partida['turno_actual'] + 1) % $total_jugadores;
                            $vueltas = 0;

                            // Mientras el siguiente jugador tenga 0 vidas, sigue saltando
                            while ($jugadores[$siguiente_turno]['vidas_restantes'] <= 0 && $vueltas < $total_jugadores) {
                                $siguiente_turno = ($siguiente_turno + 1) % $total_jugadores;
                                $vueltas++;
                            }
                            
                            // Genera la nueva sílaba y resetea el contador
                            $nueva_silaba = $this->GenerarSilabaAleatoria();
                            $nuevo_contador = 1;
                            
                            // Guarda en la BD
                            $this->modelo->AvanzarTurno($id_partida, $siguiente_turno, $nueva_silaba, $nuevo_contador);
                        }
                    }
                }
            }

            echo json_encode([
                'status' => 'ok',
                'palabra' => $palabra_usuario,
                'existe' => $existe,
                'puntos_obtenidos' => isset($puntos_totales) ? $puntos_totales : 0,
                'repetida' => isset($ya_usada) ? $ya_usada : false
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
                // Si el turno actual en la BD ya no es el que el Host dice, 
                // lo ignora para no restarle vida al jugador equivocado.
                if ($partida['turno_actual'] !== $turno_esperado) {
                    echo json_encode(['status' => 'ignorar']);
                    exit;
                }

                // Ahora usa el id_partida_jugador en lugar del id_usuario
                $id_pj_afectado = $jugadores[$partida['turno_actual']]['id_partida_jugador'];

                // Simula la resta de vida que va a sufrir para que el bucle lo tenga en cuenta
                $jugadores[$partida['turno_actual']]['vidas_restantes'] -= 1;

                // Cuenta cuántos siguen vivos después de que alguien pierda
                $supervivientes = [];
                foreach ($jugadores as $j) {
                    if ($j['vidas_restantes'] > 0) {
                        $supervivientes[] = $j;
                    }
                }

                // Si solo queda un ganador
                if (count($supervivientes) === 1) {
                    $ganador = $supervivientes[0];
                    
                    // Primero resta la vida al que perdió
                    $this->modelo->ProcesarExplosion($id_partida, $id_pj_afectado, $partida['turno_actual'], $partida['silaba_actual'], $partida['contador_silaba']);
                    
                    // Luego declara al ganador
                    $this->modelo->DeclararGanador($id_partida, $ganador['id_usuario']);
                    
                    echo json_encode(['status' => 'finalizada', 'ganador' => $ganador['username']]);
                    exit;
                }
                
                // Calcula el siguiente turno
                $siguiente_turno = ($partida['turno_actual'] + 1) % $total_jugadores;
                $vueltas = 0;
                
                // Mientras el siguiente jugador tenga 0 vidas, sigue saltando
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
                
                // Ejecuta en la BD
                $exito = $this->modelo->ProcesarExplosion($id_partida, $id_pj_afectado, $siguiente_turno, $nueva_silaba, $nuevo_contador);
                
                echo json_encode(['status' => $exito ? 'ok' : 'error']);
            }
        }
        exit;
    }

    public function Unirse() {
        $esAdmin = (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1);
        // Pide al modelo todas las partidas con estado 'esperando' (e 'iniciada' si es admin)
        $partidas = $this->modelo->ListarPartidasAbiertas($esAdmin);

        require_once '../view/header.php';
        require_once '../view/partida/unirse.php';
        require_once '../view/footer.php';
    }

    public function ListaPartidasJSON() {
        // Obliga a devolver JSON
        header('Content-Type: application/json');

        // Comprueba si el usuario es admin para saber qué botones dibujar luego en JS
        $esAdmin = (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == 1);
        
        // Pide las partidas actualizadas al modelo
        $partidas = $this->modelo->ListarPartidasAbiertas($esAdmin);
        
        echo json_encode([
            'status' => 'ok',
            'partidas' => $partidas,
            'esAdmin' => $esAdmin
        ]);
        exit;
    }

    public function VerificarContrasena() {
        // Obliga a PHP a devolver una respuesta JSON
        header('Content-Type: application/json');

        // Comprueba que se reciben los datos
        if (!isset($_GET['id']) || !isset($_GET['pass'])) {
            echo json_encode(['status' => 'error', 'mensaje' => 'Faltan datos.']);
            return;
        }

        $id_partida = (int)$_GET['id'];
        $pass_usuario = trim($_GET['pass']);

        // Ejecuta las validaciones de tamaño y contenido
        if ($pass_usuario === '') {
            echo json_encode(['status' => 'error', 'mensaje' => 'O contrasinal non pode estar baleiro.']);
            return;
        }
        if (strlen($pass_usuario) < 3) {
            echo json_encode(['status' => 'error', 'mensaje' => 'O contrasinal debe ter polo menos 3 caracteres.']);
            return;
        }
        if (strlen($pass_usuario) > 15) {
            echo json_encode(['status' => 'error', 'mensaje' => 'O contrasinal non pode superar os 15 caracteres.']);
            return;
        }

        // Busca la partida en la Base de Datos
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        if (!$partida) {
            echo json_encode(['status' => 'error', 'mensaje' => 'A sala non existe ou foi pechada.']);
            return;
        }

        // Comprueba que la contraseña coincida
        if ($partida['visibilidad'] === 'privada' && $partida['contrasena'] === $pass_usuario) {
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['status' => 'error', 'mensaje' => 'Contrasinal incorrecto.']);
        }
        exit;
    }

    public function Espectar() {
        if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
            header("Location: index.php");
            exit();
        }

        $id_partida = (int)$_GET['id'];
        $partida = $this->modelo->ObtenerPartidaPorId($id_partida);

        if ($partida) {
            // Obtiene los jugadores para dibujar las sillas, pero NO añade a este usuario
            $jugadores = $this->modelo->ObtenerJugadoresEnPartida($id_partida);
            
            // Carga la vista de la sala de juego
            require_once '../view/header.php';
            require_once '../view/partida/sala.php';
            require_once '../view/footer.php';
        } else {
            // Si la partida no existe, vuelve al inicio
            header("Location: index.php");
            exit();
        }
    }

}
?>