<?php
require 'vendor/autoload.php';

// Connect to MongoDB
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");

// Select database and collection
$database = $mongoClient->selectDatabase("CSIT321Development");
$subjectsCollection = $database->selectCollection("Subjects");

// Query MongoDB for subject information
$cursor = $subjectsCollection->find([]);

// Process query results
foreach ($cursor as $document) {
    // Check if keys exist before accessing them
    $subjectName = isset($document["subject_name"]) ? $document["subject_name"] : "N/A";
    $students = isset($document["students"]) ? $document["students"] : [];

    // Output subject information
    echo "Subject: $subjectName<br>";
    echo "Students: ";
    foreach ($students as $student) {
        echo "$student, ";
    }
    echo "<br><br>";
}
?>
