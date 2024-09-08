<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$messagesCollection = $database->selectCollection("messages");
$usersCollection = $database->selectCollection("users");

$conversationId = isset($_GET['conversationId']) ? $_GET['conversationId'] : '';

if (!$conversationId) {
    echo json_encode(['error' => 'No conversation ID provided']);
    exit;
}

try {
    $messages = $messagesCollection->find([
        'conversationId' => new MongoDB\BSON\ObjectId($conversationId)
    ], [
        'sort' => ['timestamp' => 1] // Sort messages by timestamp ascending
    ]);

    $result = [];
    foreach ($messages as $message) {
        $user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($message['senderId'])]);
        $userName = isset($user['username']) ? $user['username'] : 'Unknown User';

        $result[] = [
            'senderId' => (string) $message['senderId'],
            'username' => $userName,
            'role' => $user['role'],
            'message' => $message['message'],
            'timestamp' => $message['timestamp']->toDateTime()->format('Y-m-d H:i:s')
        ];
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
