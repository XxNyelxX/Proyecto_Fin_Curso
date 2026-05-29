-- Crea la base de datos, usa el juego de carcateres estandar utf8, mb4 es más potente y así me curo en salud, 
-- unicode hace que el orden alfabético sea el correcto y ci hace que no distinga mayusculas y minúsculas para no repetir palabra
CREATE DATABASE IF NOT EXISTS gbomb DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gbomb;

-- Tabla ROLES
CREATE TABLE roles (
    id_rol TINYINT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(25) NOT NULL
);

-- Tabla USUARIOS
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    id_rol TINYINT NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.png',
    puntuacion_mensual BIGINT DEFAULT 0,
    mes_ultimo_reinicio TINYINT DEFAULT 1,
    anho_ultimo_reinicio INT DEFAULT 2026,
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tabla PARTIDAS
-- id_ganador apunta directamente a usuario porque solo hay 1 ganador por partida, no por jugada
-- TIMESTAMP guarda del año al segundo
-- CURRENT_TIMESTAMP dice que si no viene fecha de php la pone automáticamente
CREATE TABLE partidas (
    id_partida INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60),
    fecha_partida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visibilidad VARCHAR(10) DEFAULT 'publica',
    contrasena VARCHAR(15) DEFAULT '',
    tiempo_bomba INT DEFAULT 10,
    turnos_silaba INT DEFAULT 2,
    vidas INT DEFAULT 2,
    num_jugadores TINYINT DEFAULT 1,
    max_jugadores TINYINT DEFAULT 4,
    id_host INT NOT NULL,
    id_ganador INT DEFAULT NULL,
    palabras_usadas TEXT DEFAULT '',
    estado VARCHAR(15) DEFAULT 'espera',
    turno_actual INT DEFAULT 0,
    silaba_actual VARCHAR(10) DEFAULT '',
    contador_silaba INT DEFAULT 1,
    FOREIGN KEY (id_host) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_ganador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- Tabla PARTIDAS_JUGADORES (Sala de espera y orden de sillas)
CREATE TABLE partidas_jugadores (
    id_partida_jugador INT AUTO_INCREMENT PRIMARY KEY,
    id_partida INT NOT NULL,
    id_usuario INT NOT NULL,
    vidas_restantes INT DEFAULT 0,
    FOREIGN KEY (id_partida) REFERENCES partidas(id_partida) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tabla PARTIDAS_JUGADAS
CREATE TABLE partidas_jugadas (
    id_jugada INT AUTO_INCREMENT PRIMARY KEY,
    id_partida INT NOT NULL,
    id_usuario INT NOT NULL,
    silaba VARCHAR(5) NOT NULL,
    palabra_acertada VARCHAR(100) NOT NULL,
    puntos_ganados INT DEFAULT 0,
    FOREIGN KEY (id_partida) REFERENCES partidas(id_partida) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Roles
INSERT INTO roles (id_rol, nombre_rol) VALUES 
(1, 'administrador'),
(2, 'usuario');

--Usuarios anonimos
INSERT INTO usuarios (id_usuario, username, email, contrasena, foto, id_rol) VALUES
(1, 'Anónimo 1', 'anonimo1@test.local', 'anonimo', 'default.png', 2),
(2, 'Anónimo 2', 'anonimo2@test.local', 'anonimo', 'default.png', 2),
(3, 'Anónimo 3', 'anonimo3@test.local', 'anonimo', 'default.png', 2),
(4, 'Anónimo 4', 'anonimo4@test.local', 'anonimo', 'default.png', 2),
(5, 'Anónimo 5', 'anonimo5@test.local', 'anonimo', 'default.png', 2),
(6, 'Anónimo 6', 'anonimo6@test.local', 'anonimo', 'default.png', 2),
(7, 'Anónimo 7', 'anonimo7@test.local', 'anonimo', 'default.png', 2),
(8, 'Anónimo 8', 'anonimo8@test.local', 'anonimo', 'default.png', 2),
(9, 'Anónimo 9', 'anonimo9@test.local', 'anonimo', 'default.png', 2),
(10, 'Anónimo 10', 'anonimo10@test.local', 'anonimo', 'default.png', 2),
(11, 'Anónimo 11', 'anonimo11@test.local', 'anonimo', 'default.png', 2),
(12, 'Anónimo 12', 'anonimo12@test.local', 'anonimo', 'default.png', 2),
(13, 'Anónimo 13', 'anonimo13@test.local', 'anonimo', 'default.png', 2),
(14, 'Anónimo 14', 'anonimo14@test.local', 'anonimo', 'default.png', 2),
(15, 'Anónimo 15', 'anonimo15@test.local', 'anonimo', 'default.png', 2),
(16, 'Anónimo 16', 'anonimo16@test.local', 'anonimo', 'default.png', 2);
