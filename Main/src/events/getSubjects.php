<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$subjectsCollection = $database->selectCollection("subjects");

$subjects = $subjectsCollection->find()->toArray();

// Prepare lecturers array with full names
$subjectList = [];
foreach ($subjects as $subject) {
    $subjectList[] = [
        'subjectId' => $subject["subjectId"],
        'subjectName' => $subject["subjectName"]
    ];
}

echo json_encode($subjectList);
?>
