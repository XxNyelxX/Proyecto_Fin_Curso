<?php
// Requerimos la conexión a la base de datos
require_once '../config/database.php';

class AccesoModel {
    
    private $pdo;

    public function __construct() {
        $this->pdo = Database::conectar();
    }

    public function ComprobarUsuarioDuplicado($username, $email) {
        try {
            $sql = "SELECT username, email FROM usuarios WHERE LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?)";
            $stm = $this->pdo->prepare($sql);
            
            $stm->execute([$username, $email]);
            
            return $stm->fetch(PDO::FETCH_OBJ);
            
        } catch (PDOException $e) {
            die("Erro ao comprobar a conta");
        }
    }

    public function RegistrarUsuario($username, $email, $passwordHash) {
        try {
            // Obtenemos el mes actual para la columna de reinicio
            $mesActual = date('n');

            // Preparamos la consulta. 
            // id_rol = 2 (Usuario estándar)
            // foto = 'default.png' (Avatar por defecto)
            // puntuacion_mensual = 0 (Empieza de cero)
            $sql = "INSERT INTO usuarios (username, email, contrasena, id_rol, foto, puntuacion_mensual, mes_ultimo_reinicio) 
                    VALUES (?, ?, ?, 2, 'default.png', 0, ?)";
            
            $stm = $this->pdo->prepare($sql);
            
            // Ejecutamos la inserción con los datos recibidos
            return $stm->execute([$username, $email, $passwordHash, $mesActual]);

        } catch (PDOException $e) {
            // Si hay un error (por ejemplo, la base de datos se cae), lo mostramos
            die("Erro ao rexistrar o usuario");
        }
    }

    public function LoguearUsuario($email) {
        try {
            // Buscamos al usuario por su email
            $sql = "SELECT * FROM usuarios WHERE email = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$email]);
            
            // Devolvemos el objeto con los datos o 'false' si no existe
            return $stm->fetch(PDO::FETCH_OBJ);
            
        } catch (PDOException $e) {
            die("Erro ao acceder á conta");
        }
    }

    public function ObterUsuarioPorId($id) {
        try {
            $sql = "SELECT * FROM usuarios WHERE id_usuario = ?";
            $stm = $this->pdo->prepare($sql);
            $stm->execute([$id]);
            
            return $stm->fetch(PDO::FETCH_OBJ);
            
        } catch (PDOException $e) {
            die("Erro ao recuperar os datos do perfil");
        }
    }

    public function ActualizarUsuario($id, $username, $foto, $passwordHash = null) {
        try {
            // Si nos pasan una contraseña, actualizamos los 3 campos
            if ($passwordHash !== null) {
                $sql = "UPDATE usuarios SET username = ?, foto = ?, contrasena = ? WHERE id_usuario = ?";
                $stm = $this->pdo->prepare($sql);
                $stm->execute([$username, $foto, $passwordHash, $id]);
            } 
            // Si no hay contraseña, actualizamos solo el nombre y la foto
            else {
                $sql = "UPDATE usuarios SET username = ?, foto = ? WHERE id_usuario = ?";
                $stm = $this->pdo->prepare($sql);
                $stm->execute([$username, $foto, $id]);
            }
            
            return true;
        } catch (PDOException $e) {
            // Se hai un error (por ejemplo, nombre inválido), devolvemos false
            return false;
        }
    }

}
?>