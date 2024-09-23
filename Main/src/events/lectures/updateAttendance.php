<?php
session_start();
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$lecturesCollection = $database->selectCollection("lecture");

// Get data from POST request
$lectureId = $_POST['lectureId'] ?? null;
$studentId = $_POST['studentId'] ?? null;
$isAttending = $_POST['isAttending'] === '1';

if (!$lectureId || !$studentId || !isset($_POST['isAttending'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
    exit;
}

// Fetch the lecture
$lecture = $lecturesCollection->findOne(['lectureId' => $lectureId]);

if (!$lecture) {
    echo json_encode(['success' => false, 'error' => 'Lecture not found.']);
    exit;
}

// Update attended_students array
if ($isAttending) {
    // Add student ID to attended_students
    $result = $lecturesCollection->updateOne(
        ['lectureId' => $lectureId],
        ['$addToSet' => ['attended_students' => $studentId]] // Use $addToSet to avoid duplicates
    );
} else {
    // Remove student ID from attended_students
    $result = $lecturesCollection->updateOne(
        ['lectureId' => $lectureId],
        ['$pull' => ['attended_students' => $studentId]]
    );
}

// Check if the update was successful
if ($result->getModifiedCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Attendance update failed.']);
}
?>
