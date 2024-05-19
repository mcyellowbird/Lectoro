<?php
require 'vendor/autoload.php';

function getCalendarEvents() {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturesCollection = $database->selectCollection("lectures");

    $lectures = $lecturesCollection->find()->toArray();
    $events = [];

    foreach ($lectures as $lecture) {
        $events[] = [
            'title' => $lecture['lecture_id'],
            'start' => $lecture['date_time']
        ];
    }

    return json_encode($events);
}

if (isset($_SESSION['user_id'])) {
    echo getCalendarEvents();
} else {
    echo json_encode([]);
}
?>
