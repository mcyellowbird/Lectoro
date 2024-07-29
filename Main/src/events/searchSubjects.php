<?php
require dirname(dirname(__DIR__)) . '/vendor/autoload.php';

function searchSubjects($query)
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $subjectsCollection = $database->selectCollection("subjects");

    $subjects = $subjectsCollection->find([
        '$or' => [
            ['subject_code' => ['$regex' => $query, '$options' => 'i']],
            ['subject_name' => ['$regex' => $query, '$options' => 'i']]
        ]
    ]);

    return iterator_to_array($subjects);
}

if (isset($_GET['query'])) {
    $query = $_GET['query'];
    header('Content-Type: application/json');

    try {
        $subjects = searchSubjects($query);
        echo json_encode($subjects);
    } catch (Exception $e) {
        echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>