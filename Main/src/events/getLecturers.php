<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");

$lecturers = $usersCollection->find(['role' => ['$ne' => 'Admin']])->toArray();

// Prepare lecturers array with full names
$lecturerList = [];
foreach ($lecturers as $lecturer) {
    $fullName = $lecturer->first_name . ' ' . $lecturer->last_name;
    $lecturerList[] = [
        'id' => $lecturer->userId, // Include ID for reference if needed
        'name' => $fullName
    ];
}

echo json_encode($lecturerList);
?>
