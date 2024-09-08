<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$studentsCollection = $database->selectCollection("students");

$students = $studentsCollection->find()->toArray();

// Prepare lecturers array with full names
$studentList = [];
foreach ($students as $student) {
    $fullName = $student->firstName . ' ' . $student->lastName;
    $studentList[] = [
        'id' => $student->studentId, // Include ID for reference if needed
        'name' => $fullName
    ];
}

echo json_encode($studentList);
?>
