<?php
class Database
{
    private static $dbName = 'gbomb';
    private static $dbHost = 'localhost';
    private static $dbUsername = 'root';
    private static $dbUserPassword = '';
    
    private static $con = null;
    
    // Eliminar el acceso al constructor para forzar usar conectar()
    private function __construct() {}

    public static function conectar()
    {
        if (self::$con == null) {
            try {
                // Añadimos charset=utf8mb4 al final del string de conexión
                self::$con = new PDO(
                    "mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName . ";charset=utf8mb4",
                    self::$dbUsername,
                    self::$dbUserPassword
                );
                
                // Forzamos a PDO a mostrar los errores si algo falla
                self::$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
            } catch (PDOException $e) {
                echo 'ERROR: ' . $e->getMessage();
                echo '<br>ERROR NÚMERO: ' . $e->getCode();
                die();
            }
        }
        return self::$con;
    }
    
    private function __clone() {}

    public static function desconectar()
    {
        self::$con = null;
    }
}
?>