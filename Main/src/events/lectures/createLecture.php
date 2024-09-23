<?php
session_start();
if (!isset($_SESSION['userId'])) {
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$lecturesCollection = $database->selectCollection("lecture");
$lecturersCollection = $database->selectCollection("lecturers");

// Fetch userId from session
$userId = $_SESSION['userId'];

// Get lecturerId and assigned subjects from the lecturers collection by comparing userId
$lecturer = $lecturersCollection->findOne(['userId' => $userId]);
if (!$lecturer) {
    echo json_encode(['error' => 'Lecturer not found.']);
    exit;
}

$lecturerId = $lecturer['lecturerId'];
$subjectId = $_POST['subjectId'] ?? null;

if (!$subjectId) {
    echo json_encode(['error' => 'Subject not specified.']);
    exit;
}

function startLecture($lecturesCollection, $subjectId, $lecturerId) {
    $latestLecture = $lecturesCollection->findOne(
        ['subjectId' => $subjectId],
        ['sort' => ['lectureId' => -1]]
    );
    
    $lectureNumber = $latestLecture ? intval(substr($latestLecture['lectureId'], strrpos($latestLecture['lectureId'], 'L') + 1)) + 1 : 1;
    $lectureId = sprintf('%s_L%03d', $subjectId, $lectureNumber);
    $dateTime = gmdate('Y-m-d\TH:i:s\Z');
    
    $newLecture = [
        'attended_students' => [],
        'lectureId' => $lectureId,
        'subjectId' => $subjectId,
        'dateTime' => $dateTime,
        'lecturerId' => $lecturerId
    ];

    $lecturesCollection->insertOne($newLecture);
    return $lectureId;
}

try {
    $createdLectureId = startLecture($lecturesCollection, $subjectId, $lecturerId);
    echo json_encode(['success' => true, 'lectureId' => $createdLectureId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error creating lecture: ' . $e->getMessage()]);
}
?>
