<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");

$query = isset($_GET['query']) ? $_GET['query'] : '';

if ($query) {
    try {
        $users = $usersCollection->find(['username' => new MongoDB\BSON\Regex($query, 'i')]);
        $result = [];

        foreach ($users as $user) {
            // Debugging statement
            error_log(print_r($user, true)); 

            $result[] = [
                'id' => (string) $user['_id'],
                'first_name' => isset($user['first_name']) ? $user['first_name'] : 'N/A',
                'last_name' => isset($user['last_name']) ? $user['last_name'] : 'N/A',
                'username' => isset($user['username']) ? $user['username'] : 'N/A'
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No search query provided']);
}
?>