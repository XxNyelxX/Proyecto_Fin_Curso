<?php

class Usuario {
    protected $id_usuario;
    protected $username;
    protected $email;
    protected $contrasena;
    protected $id_rol;
    protected $foto;
    protected $puntuacion_mensual;
    protected $mes_ultimo_reinicio;

    // Constructor vacío
    public function __construct() {}

    // GETTERS
    public function getIdUsuario() { return $this->id_usuario; }
    public function getUsername() { return $this->username; }
    public function getEmail() { return $this->email; }
    public function getContrasena() { return $this->contrasena; }
    public function getIdRol() { return $this->id_rol; }
    public function getFoto() { return $this->foto; }
    public function getPuntuacionMensual() { return $this->puntuacion_mensual; }
    public function getMesUltimoReinicio() { return $this->mes_ultimo_reinicio; }

    // SETTERS
    public function setIdUsuario($id_usuario) { $this->id_usuario = $id_usuario; }
    public function setUsername($username) { $this->username = $username; }
    public function setEmail($email) { $this->email = $email; }
    public function setContrasena($contrasena) { $this->contrasena = $contrasena; }
    public function setIdRol($id_rol) { $this->id_rol = $id_rol; }
    public function setFoto($foto) { $this->foto = $foto; }
    public function setPuntuacionMensual($puntuacion_mensual) { $this->puntuacion_mensual = $puntuacion_mensual; }
    public function setMesUltimoReinicio($mes_ultimo_reinicio) { $this->mes_ultimo_reinicio = $mes_ultimo_reinicio; }
}
?>