<?php
namespace MyApp;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface {
    protected $clients;

    public function __construct() {
        // Guardaremos a todos los jugadores conectados aquí
        $this->clients = new \SplObjectStorage;
        echo "Servidor de xogo iniciado...\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Se ejecuta cuando alguien entra en la sala
        $this->clients->attach($conn);
        echo "Nova conexión! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Se ejecuta cuando alguien envía un mensaje (o una palabra)
        foreach ($this->clients as $client) {
            // Reenviamos el mensaje a todos los demás
            $client->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // Se ejecuta cuando alguien cierra la pestaña o pierde conexión
        $this->clients->detach($conn);
        echo "Conexión {$conn->resourceId} pechada.\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}
?>