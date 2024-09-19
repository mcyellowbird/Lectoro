<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Database connection
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$studentsCollection = $database->selectCollection("students");
$subjectsCollection = $database->selectCollection("subjects");

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect POST data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $studentId = $_POST['studentId'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subjects = isset($_POST['subjects']) ? $_POST['subjects'] : []; // Handle multiple selected subjects

    $dateTime = new DateTime();
    $currentTimestamp = $dateTime->format('Y-m-d\TH:i:s\Z');

    // Prepare student data
    $studentData = [
        'firstName' => $firstName,
        'lastName' => $lastName,
        'studentId' => $studentId,
        'email' => $email,
        'phone' => $phone,
        'imageURL' => "https://example.com/images/" . strtolower($firstName) . "_" . strtolower($lastName) . ".jpg"
    ];

    // Insert the new student into the students collection
    $insertResult = $studentsCollection->insertOne($studentData);

    if ($insertResult->getInsertedCount() > 0) {
        // Update the subjects collection to add the student to each subject's students array
        $updateResults = [];
        foreach ($subjects as $subjectId) {
            $updateResult = $subjectsCollection->updateOne(
                ['subjectId' => $subjectId],
                ['$addToSet' => ['students' => $studentId]] // Add studentId to the students array
            );
            $updateResults[] = $updateResult;
        }

        // Check if all updates were successful
        $allUpdatesSuccessful = array_reduce($updateResults, function($carry, $result) {
            return $carry && $result->getModifiedCount() > 0;
        }, true);

        if ($allUpdatesSuccessful) {
            echo json_encode(['success' => true, 'message' => 'Student added and subjects updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student added, but failed to update some subjects.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add student.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
