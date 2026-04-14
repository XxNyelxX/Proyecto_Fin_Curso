-- Creamos la base de datos, usamos el juego de carcateres estandar utf8, mb4 es más potente y así me curo en salud, 
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
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Tabla PARTIDAS
-- id_ganador apunta directamente a usuario porque solo hay 1 ganador por partida, no por jugada
-- TIMESTAMP guarda del año al segundo
-- CURRENT_TIMESTAMP dice que si no viene fecha de php la pone automáticamente
CREATE TABLE partidas (
    id_partida INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50),
    fecha_partida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    visibilidad VARCHAR(10) DEFAULT 'publica',
    tiempo_bomba INT DEFAULT 5,
    turnos_silaba INT DEFAULT 2,
    vidas INT DEFAULT 2,
    num_jugadores TINYINT DEFAULT 1,
    id_ganador INT DEFAULT NULL,
    palabras_usadas TEXT DEFAULT '',
    FOREIGN KEY (id_ganador) REFERENCES usuarios(id_usuario) ON DELETE SET NULL ON UPDATE CASCADE
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

-- 6. Tabla DICCIONARIO_LOCAL
CREATE TABLE diccionario_local (
    id_palabra INT AUTO_INCREMENT PRIMARY KEY,
    palabra VARCHAR(100) NOT NULL UNIQUE,
    fecha_anadida TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Roles
INSERT INTO roles (id_rol, nombre_rol) VALUES 
(1, 'administrador'),
(2, 'usuario');

--Usuarios prueba
-- INSERT INTO usuarios (username, email, contrasena, id_rol, puntuacion_mensual, mes_ultimo_reinicio) VALUES 
--     ('JugadorTest1', 'test1@gbomb.com', 'clavefalsa123', 1, 45000, 3),
--     ('JugadorTest2', 'test2@gbomb.com', 'clavefalsa123', 2, 120500, 3),
--     ('JugadorTest3', 'test3@gbomb.com', 'clavefalsa123', 2, 8900, 3),
--     ('JugadorTest4', 'test4@gbomb.com', 'clavefalsa123', 2, 340000, 3),
--     ('JugadorTest5', 'test5@gbomb.com', 'clavefalsa123', 2, 21500, 3),
--     ('JugadorTest6', 'test6@gbomb.com', 'clavefalsa123', 2, 567000, 3),
--     ('JugadorTest7', 'test7@gbomb.com', 'clavefalsa123', 2, 0, 3),
--     ('JugadorTest8', 'test8@gbomb.com', 'clavefalsa123', 2, 1200, 3),
--     ('JugadorTest9', 'test9@gbomb.com', 'clavefalsa123', 2, 98000, 3),
--     ('JugadorTest10', 'test10@gbomb.com', 'clavefalsa123', 2, 43200, 3),
--     ('JugadorTest11', 'test11@gbomb.com', 'clavefalsa123', 2, 150000, 3),
--     ('JugadorTest12', 'test12@gbomb.com', 'clavefalsa123', 2, 87600, 3),
--     ('JugadorTest13', 'test13@gbomb.com', 'clavefalsa123', 2, 23000, 3),
--     ('JugadorTest14', 'test14@gbomb.com', 'clavefalsa123', 2, 765000, 3),
--     ('JugadorTest15', 'test15@gbomb.com', 'clavefalsa123', 2, 11000, 3),
--     ('JugadorTest16', 'test16@gbomb.com', 'clavefalsa123', 2, 54300, 3),
--     ('JugadorTest17', 'test17@gbomb.com', 'clavefalsa123', 2, 290000, 3),
--     ('JugadorTest18', 'test18@gbomb.com', 'clavefalsa123', 2, 4000, 3),
--     ('JugadorTest19', 'test19@gbomb.com', 'clavefalsa123', 2, 67800, 3),
--     ('JugadorTest20', 'test20@gbomb.com', 'clavefalsa123', 2, 999999, 3);

--Borrar usuarios prueba
    -- DELETE FROM usuarios WHERE username LIKE 'JugadorTest%';