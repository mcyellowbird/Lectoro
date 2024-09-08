<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Database connection
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$subjectsCollection = $database->selectCollection("subjects");
$studentsCollection = $database->selectCollection("students");
$lecturersCollection = $database->selectCollection("lecturers");


// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectName = $_POST['subjectName'];
    $subjectCode = $_POST['subjectCode'];
    $students = $_POST['studentIds']; // 'studentIds' should be obtained from the form
    $lecturerIds = $_POST['lecturerIds']; // 'lecturerIds' should be obtained from the form

    $dateTime = new DateTime(); // or however you get your DateTime
    $currentTimestamp = $dateTime->format('Y-m-d\TH:i:s\Z');

    // Get the next subject_id
    $subjectId = getNextSubjectId($subjectsCollection);

    // Insert the new subject into the subjects collection
    $subjectData = [
        'subject_id' => $subjectId,
        'subject_code' => $subjectCode,
        'subject_name' => $subjectName,
        'students' => $students,
        'timetable' => [$currentTimestamp],
        'duration' => 60
    ];

    $insertResult = $subjectsCollection->insertOne($subjectData);

    if ($insertResult->getInsertedCount() > 0) {
        // Update the lecturers' assigned_subjects list
        // foreach ($lecturerIds as $lecturerId) {
            $lecturersCollection->updateOne(
                ['user_id' => $lecturerIds],
                ['$addToSet' => ['assigned_subjects' => $subjectId]]
            );
        // }

        echo json_encode(['success' => true, 'message' => 'Subject added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add subject.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
