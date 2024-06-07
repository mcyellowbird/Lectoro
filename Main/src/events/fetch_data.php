<?php
require 'vendor/autoload.php';

function getLectureData() {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturesCollection = $database->selectCollection("lectures");

    $lectures = $lecturesCollection->find()->toArray();
    return json_encode($lectures);
}

if (isset($_SESSION['user_id'])) {
    echo getLectureData();
} else {
    echo json_encode([]);
}
?>
