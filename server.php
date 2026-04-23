<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\Chat;

// Incluimos el cargador automático de Composer
require __DIR__ . '/vendor/autoload.php';
// Incluimos nuestra clase Chat
require __DIR__ . '/socket/Chat.php';

// Configuramos el servidor en el puerto 8080
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

$server->run();
?>