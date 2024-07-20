<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php'; // Adjust the path as needed

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $mongoClient;
    protected $database;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $this->database = $this->mongoClient->selectDatabase("CSIT321Development");
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        // Assuming data contains conversation_id, sender_id, and message
        $conversationId = $data['conversation_id'];
        $senderId = $data['sender_id'];
        $messageText = $data['message'];

        // Save the message to MongoDB
        $messagesCollection = $this->database->selectCollection('messages');
        $messagesCollection->insertOne([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'message' => $messageText,
            'timestamp' => new MongoDB\BSON\UTCDateTime()
        ]);

        // Broadcast the message to all clients in the conversation
        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $conn->close();
    }
}

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatWebSocket()
        )
    ),
    8080 // Port number for WebSocket server
);

$server->run();
?>
