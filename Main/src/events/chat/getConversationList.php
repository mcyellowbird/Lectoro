<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

session_start();
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$conversationsCollection = $database->selectCollection("conversations");
$usersCollection = $database->selectCollection("users");

$loggedInUserId = $_SESSION['_id'];

try {
    $conversations = $conversationsCollection->find(
        ['participants' => $loggedInUserId],
        ['sort' => ['last_message_timestamp' => -1]]
    );

    $result = [];
    foreach ($conversations as $conversation) {
        // Convert BSONArray to PHP array
        $participants = iterator_to_array($conversation['participants']);
        
        // Filter out the logged-in user ID to find the other participant
        $userIds = array_filter($participants, function ($id) use ($loggedInUserId) {
            return $id !== $loggedInUserId;
        });

        // Get the first user ID from the filtered array (assuming there is only one)
        $userId = array_values($userIds)[0]; 

        // Fetch user details
        $user = $usersCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($userId)]);

        $lastMessage = $conversation['last_message'] ?? null;
        $lastMessageTimestamp = $lastMessage['timestamp'] ?? null;

        $result[] = [
            'conversation_id' => (string) $conversation['_id'],
            'user_id' => (string) $user['_id'],
            'display_name' => $user['first_name'] . " " . $user['last_name'] . " (" . $user['username'] . ")", // Assuming 'username' is stored as 'username'
            'user_type' => (string) $user['user_type'],
            'last_message_timestamp' => $lastMessageTimestamp,
        ];
    }

    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
