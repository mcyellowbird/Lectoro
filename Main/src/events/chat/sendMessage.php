<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

header('Content-Type: application/json');

$response = [];

try {
    // Connect to MongoDB
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $messagesCollection = $database->selectCollection("messages");

    // Get input data
    $conversation_id = isset($_POST['conversation_id']) ? $_POST['conversation_id'] : null;
    $message = isset($_POST['message']) ? $_POST['message'] : null;

    // Debugging: Log the input data
    error_log('Received conversation_id: ' . $conversation_id);
    error_log('Received message: ' . $message);

    // Validate input data
    if (!$conversation_id || !$message) {
        throw new Exception('Invalid input data');
    }

    // Assuming user is logged in and we have their ID
    session_start();
    if (!isset($_SESSION['_id'])) {
        throw new Exception('User not logged in');
    }
    $sender_id = $_SESSION['_id'];

    // Insert the message into the collection
    $insertResult = $messagesCollection->insertOne([
        'conversation_id' => new MongoDB\BSON\ObjectId($conversation_id),
        'sender_id' => $sender_id,
        'message' => $message,
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);

    if ($insertResult->getInsertedCount() === 1) {
        $response['success'] = true;
    } else {
        throw new Exception('Failed to insert message');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    error_log('Error in sendMessage.php: ' . $e->getMessage());
}

echo json_encode($response);