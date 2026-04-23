<?php
require_once '../model/acceso.model.php';

class AccesoController {
    private $modelo;

    public function __construct() {
        // Inicializamos el modelo para poder usar la base de datos
        $this->modelo = new AccesoModel();
    }
    
    // --- VISTAS ---

    // Acción para mostrar el formulario de INICIO
    public function Entrar() {
        // SEGURIDAD: Si está logueado fuera
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }

        require_once '../view/header.php';
        require_once '../view/acceso/entrar-form.php';
        require_once '../view/footer.php';
    }

    // Acción para mostrar el formulario de CREAR CUENTA
    public function Registrarse() {
        // SEGURIDAD: Si está logueado fuera
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }

        require_once '../view/header.php';
        require_once '../view/acceso/registrarse-form.php';
        require_once '../view/footer.php';
    }

    // Acción para mostrar el formulario de EDITAR PERFIL
    public function EditarPerfil() {
        // SEGURIDAD: Si no está logueado fuera
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=acceso&a=Entrar");
            exit();
        }

        // RECUPERAR DATOS: Pedimos al modelo la info fresca del usuario
        $usuario = $this->modelo->ObterUsuarioPorId($_SESSION['user_id']);

        require_once '../view/header.php';
        require_once '../view/acceso/editar-form.php';
        require_once '../view/footer.php';
    }

    // --- LÓGICA DE REGISTRO ---

    // Acción para validar la entrada
    public function ValidarEntrada() {
        // Bloqueo si ya está logueado
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }

        // Bloqueo si intentan entrar copiando la URL en vez de usar el formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit();
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $contrasinal = isset($_POST['contrasinal']) ? $_POST['contrasinal'] : '';

            if ($email === '') { $errores['email'] = "O correo non pode estar baleiro."; }
            if ($contrasinal === '') { $errores['contrasinal'] = "O contrasinal non pode estar baleiro."; }

            if (empty($errores)) {
                /* Buscamos al usuario */
                $usuario = $this->modelo->LoguearUsuario($email);

                /* Verificamos si existe la contraseña */
                // password_verify compara la clave plana con hash da BD
                if ($usuario && password_verify($contrasinal, $usuario->contrasena)) {
                    /* --- GUARDAR DATOS EN LA SESIÓN --- */
                    $_SESSION['user_id']   = $usuario->id_usuario;
                    $_SESSION['username']  = $usuario->username;
                    $_SESSION['email']     = $usuario->email;
                    $_SESSION['id_rol']    = $usuario->id_rol;
                    $_SESSION['foto']      = $usuario->foto;

                    $mensaje_exito = "Benvido " . $usuario->username;


                    // Aquí é onde máis adiante crearemos a SESIÓN


                } else {
                    $errores['login'] = "O correo ou o contrasinal son incorrectos.";
                }
            }

            require_once '../view/header.php';
            require_once '../view/acceso/entrar-form.php';
            require_once '../view/footer.php';

        }
    }

    // Acción para validar el registro
    public function ValidarRegistro() {
        // Bloqueo si ya está logueado
        if (isset($_SESSION['user_id'])) {
            header("Location: index.php");
            exit();
        }

        // Bloqueo si intentan entrar copiando la URL en vez de usar el formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit();
        }

        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $contrasinal = isset($_POST['contrasinal']) ? $_POST['contrasinal'] : '';
            $confirmar_contrasinal = isset($_POST['confirmar_contrasinal']) ? $_POST['confirmar_contrasinal'] : '';

            /* --- VALIDACIÓN DEL USUARIO --- */
            if ($username === '') {
                $errores['username'] = "O nome de usuario non pode estar baleiro.";
            } else if (strlen($username) < 3) {
                $errores['username'] = "O nome de usuario debe ter polo menos 3 caracteres.";
            } else if (strlen($username) > 50) {
                $errores['username'] = "O nome de usuario non pode ter máis de 50 caracteres.";
            }

            /* --- VALIDACIÓN DEL EMAIL --- */
            if ($email === '') {
                $errores['email'] = "O correo non pode estar baleiro.";
            } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errores['email'] = "O formato de correo non é válido.";
            } else if (strlen($email) > 100) {
                $errores['email'] = "O correo non pode ter máis de 100 caracteres.";
            }

            /* --- VALIDACIÓN DE LA CONTRASEÑA --- */
            if ($contrasinal === '') {
                $errores['contrasinal'] = "O contrasinal non pode estar baleiro.";
            } else if (strlen($contrasinal) < 8) {
                $errores['contrasinal'] = "O contrasinal debe ter polo menos 8 caracteres.";
            } else if (strlen($contrasinal) > 255) {
                $errores['contrasinal'] = "O contrasinal é demasiado longo.";
            } else if (!preg_match('/[A-Z]/', $contrasinal) || !preg_match('/[a-z]/', $contrasinal)) {
                $errores['contrasinal'] = "O contrasinal debe conter letras maiúsculas e minúsculas.";
            } else if (preg_match_all('/[0-9]/', $contrasinal) < 3) {
                $errores['contrasinal'] = "O contrasinal debe conter polo menos 3 números.";
            } else if (!preg_match('/[^a-zA-Z0-9]/', $contrasinal)) {
                $errores['contrasinal'] = "O contrasinal debe conter polo menos 1 carácter especial.";
            }

            /* --- VALIDACIÓN DE REPETIR CONTRASEÑA --- */
            if ($confirmar_contrasinal === '') {
                $errores['confirmar_contrasinal'] = "Debes repetir o contrasinal.";
            } else if ($contrasinal !== $confirmar_contrasinal) {
                $errores['confirmar_contrasinal'] = "Os contrasinais non coinciden.";
            }

            /* --- VALIDACIÓN EN BASE DE DATOS (Duplicados) --- */
            // Solo consultamos la BD si el formato está bien
            if (empty($errores)) {
                $usuarioExistente = $this->modelo->ComprobarUsuarioDuplicado($username, $email);
                
                if ($usuarioExistente) {
                    if ($usuarioExistente->username === $username) {
                        $errores['username'] = "O nome xa está en uso.";
                    }
                    if ($usuarioExistente->email === $email) {
                        $errores['email'] = "O correo xa está rexistrado.";
                    }
                }
            }

            /* --- RESULTADO FINAL --- */
            if (!empty($errores)) {
                require_once '../view/header.php';
                require_once '../view/acceso/registrarse-form.php';
                require_once '../view/footer.php';
            } else {
                /* --- HASH DE LA CONTRASEÑA --- */
                // Usamos PASSWORD_DEFAULT que aplicará BCRYPT automáticamente
                $passwordHash = password_hash($contrasinal, PASSWORD_DEFAULT);

                /* --- REGISTRO EN LA BASE DE DATOS --- */
                $resultado = $this->modelo->RegistrarUsuario($username, $email, $passwordHash);

                if ($resultado) {
                    $mensaje_exito = "CONTA CREADA CON ÉXITO";
                    
                    require_once '../view/header.php';
                    require_once '../view/acceso/registrarse-form.php';
                    require_once '../view/footer.php';
                }
            }
        }
    }

    // Acción para cerrar la sesión
    public function Salir() {
        // Cerramos todas las variables de sesión
        $_SESSION = array();

        // Destruimos la sesión
        session_destroy();

        // Volvemos a la página principal y cerramos la sesión
        header("Location: index.php");
        exit();
    }

    // Acción para procesar el formulario de edición
    public function ActualizarPerfil() {
        // SEGURIDAD: Si no está logueado fuera
        if (!isset($_SESSION['user_id'])) {
            header("Location: ?c=acceso&a=Entrar");
            exit();
        }
        // Bloqueo si intentan entrar copiando la URL en vez de usar el formulario
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: index.php");
            exit();
        }

        $errores = [];
        $id = $_SESSION['user_id'];
        
        // Recuperamos los datos actuales para tener la foto original 
        // y pasarlos a la vista si hay fallos
        $usuario = $this->modelo->ObterUsuarioPorId($id);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Guardamos lo que el usuario ha escrito usando tus nombres del HTML
            $input_username = isset($_POST['nuevo_username']) ? trim($_POST['nuevo_username']) : '';
            $foto = $usuario->foto; // Por defecto, conservamos la foto actual

            // 1. Validar el nombre
            if ($input_username === '') {
                //Si está vacio se mantiene su nombre de usuario
                $username = $_SESSION['username'];
            } else {

                $username = $input_username;

                if (strlen($username) < 3) {
                    $errores['nuevo_username'] = "O nome de usuario debe ter polo menos 3 caracteres.";
                } else if (strlen($username) > 50) {
                    $errores['nuevo_username'] = "O nome de usuario non pode ter máis de 50 caracteres.";
                } else if ($username == $_SESSION['username']) {
                    $errores['nuevo_username'] = "Puxeche o mesmo nome.";
                }else{
                    // Le pasamos el nombre. Como tu método pedía también email, le pasamos un string vacío para el email
                    $usuarioExistente = $this->modelo->ComprobarUsuarioDuplicado($username, '');
                            
                    if ($usuarioExistente && strtolower($usuarioExistente->username) === strtolower($username)) {
                        $errores['nuevo_username'] = "Ese nome de usuario xa está en uso.";
                    }
                }
            }


            // 2. Validar la imagen (solo si se ha subido un archivo nuevo)
            if (isset($_FILES['nueva_foto']) && $_FILES['nueva_foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $archivo = $_FILES['nueva_foto'];
                $tamanoMaximo = 500 * 1024; // Límite de 500kb
                $formatosPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

                if ($archivo['error'] !== UPLOAD_ERR_OK) {
                    $errores['nueva_foto'] = "Houbo un erro ao subir a imaxe.";
                } elseif ($archivo['size'] > $tamanoMaximo) {
                    $errores['nueva_foto'] = "A imaxe supera o límite de 500KB.";
                } elseif (!in_array($archivo['type'], $formatosPermitidos)) {
                    $errores['nueva_foto'] = "Formato non válido (Só JPG, PNG, JPEG ou WEBP).";
                } else {
                    // Si la imagen es válida, intentamos moverla a la carpeta
                    $nombreArchivo = time() . "_" . basename($archivo['name']);

                    $directorioDestino = __DIR__ . "/../public/img/avatars/";

                    $rutaDestino = $directorioDestino . $nombreArchivo;
                    
                    if (move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
                        $foto = $nombreArchivo;

                        //comprobamos que quiera subir una foto y que ya tenga una foto anterior
                        if (!empty($usuario->foto) && $usuario->foto !== 'default.png') {
                            $rutaFotoAntigua = $directorioDestino . $usuario->foto;
                            
                            // Verificamos que la foto antigua exista físicamente antes de intentar borrarla
                            if (file_exists($rutaFotoAntigua)) {
                                unlink($rutaFotoAntigua); // La borramos del disco duro para no tener fotos que no se usen
                            }
                        }
                    } else {
                        $errores['nueva_foto'] = "Non se puido gardar a imaxe no servidor.";
                    }
                }
            }

            // 3. Validar Contraseñas (solo si el usuario escribió en alguno de los 3 campos)
            $input_actual = isset($_POST['contraseña_actual']) ? $_POST['contraseña_actual'] : '';
            $input_nueva = isset($_POST['nueva_contraseña']) ? $_POST['nueva_contraseña'] : '';
            $input_confirmar = isset($_POST['confirmar_contraseña']) ? $_POST['confirmar_contraseña'] : '';

            $cambiandoContraseña = false;

            if ($input_actual !== '' || $input_nueva !== '' || $input_confirmar !== '') {
                $cambiandoContraseña = true;

                // Que ningún campo esté vacío
                if ($input_actual === '') {
                    $errores['contraseña_actual'] = "Debes introducir o teu contrasinal actual.";
                }
                if ($input_nueva === '') {
                    $errores['nueva_contraseña'] = "O novo contrasinal non pode estar baleiro.";
                }
                if ($input_confirmar === '') {
                    $errores['confirmar_contraseña'] = "Debes repetir o novo contrasinal.";
                }

                // Si los 3 campos tienen texto, hacemos las validaciones
                if (empty($errores['contraseña_actual']) && empty($errores['nueva_contraseña']) && empty($errores['confirmar_contraseña'])) {
                    
                    // Comprobar que la contraseña actual es la correcta
                    if (!password_verify($input_actual, $usuario->contrasena)) {
                        $errores['contraseña_actual'] = "O contrasinal actual é incorrecto.";
                    } else {
                        // Comprobar que la nueva no sea exactamente igual a la vieja
                        if (password_verify($input_nueva, $usuario->contrasena)) {
                            $errores['nueva_contraseña'] = "O novo contrasinal non pode ser igual ao actual.";
                        }

                        // Validar la complejidad de la nueva contraseña (igual que en el registro)
                        if (strlen($input_nueva) < 8) {
                            $errores['nueva_contraseña'] = "O contrasinal debe ter polo menos 8 caracteres.";
                        } else if (strlen($input_nueva) > 255) {
                            $errores['nueva_contraseña'] = "O contrasinal é demasiado longo.";
                        } else if (!preg_match('/[A-Z]/', $input_nueva) || !preg_match('/[a-z]/', $input_nueva)) {
                            $errores['nueva_contraseña'] = "Debe conter letras maiúsculas e minúsculas.";
                        } else if (preg_match_all('/[0-9]/', $input_nueva) < 3) {
                            $errores['nueva_contraseña'] = "Debe conter polo menos 3 números.";
                        } else if (!preg_match('/[^a-zA-Z0-9]/', $input_nueva)) {
                            $errores['nueva_contraseña'] = "Debe conter polo menos 1 carácter especial.";
                        }

                        // D) Comprobar que la confirmación coincide
                        if ($input_nueva !== $input_confirmar) {
                            $errores['confirmar_contraseña'] = "Os contrasinais non coinciden.";
                        }
                    }
                }
            }

            // 4. Si no hay errores previos, intentamos actualizar en la BD
            if (empty($errores)) {

                // Si el chivato nos dice que hay cambio de contraseña, la hasheamos. Si no, se queda en null.
                $passwordHash = null;
                if ($cambiandoContraseña) {
                    $passwordHash = password_hash($input_nueva, PASSWORD_DEFAULT);
                }

                if ($this->modelo->ActualizarUsuario($id, $username, $foto, $passwordHash)) {
                    
                    // --- MENSAJES DE ÉXITO ESTILO REXISTRO ---

                    //Para ahorrar elses
                    $texto_contrasena = $cambiandoContraseña ? " e contrasinal modificado" : "";

                    if ($username !== $_SESSION['username'] && isset($nombreArchivo)) {
                        $texto_contrasena = $cambiandoContraseña ? ", contrasinal " : " ";
                        $mensaje_exito = "Avatar" .$texto_contrasena. "e nome actualizados. Novo nome: " . $username ;
                    } else if ($username !== $_SESSION['username']) {
                        $mensaje_exito = "Nome cambiado con éxito a: " . $username . $texto_contrasena;
                    } else if (isset($nombreArchivo)) {
                        $mensaje_exito = "A túa foto de perfil foi actualizada" . $texto_contrasena;
                    } else if($cambiandoContraseña){
                        $mensaje_exito = "Contrasinal modificado con éxito.";
                    }

                    // Actualizamos la sesión con los datos nuevos
                    $_SESSION['username'] = $username;
                    $_SESSION['foto'] = $foto;
                    
                    // Actualizamos la variable $usuario para que el HTML muestre los cambios al instante
                    $usuario->username = $username;
                    $usuario->foto = $foto;
                    // Actualizamos también la contraseña en la variable temporal para que las futuras comprobaciones de la misma sesión no fallen
                    if ($cambiandoContraseña) {
                        $usuario->contrasena = $passwordHash;
                    }

                } else {
                    // Fallo en la BD
                    $errores['nuevo_username'] = "Ese nome de usuario xa está en uso.";
                }
            }

            // 4. Si llegamos aquí es porque hay errores (de validación o de BD)
            if (!empty($errores)) {
                // Sobrescribimos el nombre temporalmente para que el formulario 
                // no le borre al usuario lo que intentó escribir
                $usuario->username = $username;
            }
                require_once '../view/header.php';
                require_once '../view/acceso/editar-form.php';
                require_once '../view/footer.php';
        }
    }
}
?>