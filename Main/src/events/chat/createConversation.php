<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

session_start();

$userId = isset($_POST['userId']) ? $_POST['userId'] : '';
$loggedInUserId = $_SESSION['_id'];

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$conversationsCollection = $database->selectCollection("conversations");

try {
    $conversation = $conversationsCollection->insertOne([
        'participants' => [$loggedInUserId, $userId]
    ]);

    if ($conversation->getInsertedCount() > 0) {
        echo json_encode([
            'success' => true,
            'conversationId' => (string) $conversation->getInsertedId()
        ]);
    } else {
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'error' => 'Failed to create conversation']);
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
