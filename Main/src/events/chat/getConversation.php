<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

session_start();
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$conversationsCollection = $database->selectCollection("conversations");
$usersCollection = $database->selectCollection("users");

$userId = isset($_GET['id']) ? $_GET['id'] : '';

if (!$userId) {
    echo json_encode(['error' => 'No user ID provided']);
    exit;
}

try {
    $conversation = $conversationsCollection->findOne([
        'participants' => ['$all' => [$userId, $_SESSION['_id']]]
    ]);

    if ($conversation) {
        $user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);
        $userName = isset($user['username']) ? $user['username'] : 'Unknown User';
        echo json_encode([
            'conversation_id' => (string) $conversation->_id,
            'user_name' => $userName,
            'user_type' => (string) $user['user_type'],
        ]);
    } else {
        echo json_encode(['conversation_id' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
