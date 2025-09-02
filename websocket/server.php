<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require dirname(__DIR__) . '/vendor/autoload.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class SignalingServer implements MessageComponentInterface {
    protected $clients = [];

    public function onOpen(ConnectionInterface $conn) {
        // Store the client with user ID from query parameter
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $queryParams);
        $userId = isset($queryParams['userId']) ? $queryParams['userId'] : null;
        if ($userId) {
            $this->clients[$userId] = $conn;
        }
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (isset($data['to']) && isset($this->clients[$data['to']])) {
            $recipient = $this->clients[$data['to']];
            if ($recipient->resourceId !== $from->resourceId) {
                $recipient->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $queryParams);
        $userId = isset($queryParams['userId']) ? $queryParams['userId'] : null;
        if ($userId && isset($this->clients[$userId])) {
            unset($this->clients[$userId]);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SignalingServer()
        )
    ),
    8080
);

$server->run();