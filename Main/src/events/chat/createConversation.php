<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

session_start();
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$conversationsCollection = $database->selectCollection("conversations");

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['userId']) ? $input['userId'] : '';

if ($userId) {
    $loggedInUserId = $_SESSION['_id'];

    try {
        $conversation = $conversationsCollection->insertOne([
            'participants' => [$loggedInUserId, $userId]
        ]);

        if ($conversation->getInsertedCount() > 0) {
            echo json_encode([
                'success' => true,
                'conversation_id' => (string) $conversation->getInsertedId()
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create conversation']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
