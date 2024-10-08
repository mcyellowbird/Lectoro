<?php
require dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// Database connection
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");
$lecturersCollection = $database->selectCollection("lecturers");

// Function to get the next available userId
function getNextUserId($usersCollection) {
    $lastUser = $usersCollection->findOne([], ['sort' => ['userId' => -1]]);
    $lastId = $lastUser ? $lastUser['userId'] : 'U000';
    $nextIdNumber = intval(substr($lastId, 1)) + 1;
    return 'U' . str_pad($nextIdNumber, 3, '0', STR_PAD_LEFT);
}

// Function to get the next available lecturerId
function getNextLecturerId($lecturersCollection) {
    $lastLecturer = $lecturersCollection->findOne([], ['sort' => ['lecturerId' => -1]]);
    $lastId = $lastLecturer ? $lastLecturer['lecturerId'] : 'L000';
    $nextIdNumber = intval(substr($lastId, 1)) + 1;
    return 'L' . str_pad($nextIdNumber, 3, '0', STR_PAD_LEFT);
}

// Function to generate a unique username
function generateUsername($firstName, $lastName, $usersCollection) {
    // Generate the base username from initials
    $baseUsername = strtolower(substr($firstName, 0, 1) . substr($lastName, 0, 1)); // e.g., "js"
    
    // Use the existing function to get the next userId
    $nextUserId = getNextUserId($usersCollection); // This will give us something like 'U001', 'U002', etc.
    
    // Extract the numeric part and increment it
    $nextNum = intval(substr($nextUserId, 1)); // Get the numeric part after 'U' (e.g., from 'U001' get '001')
    
    // Combine the base username with the next number
    return $baseUsername . str_pad($nextNum, 3, '0', STR_PAD_LEFT); // e.g., "js001"
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect data from the form
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $assignedSubjects = $_POST['assignedSubjects']; // Array of subject IDs

    // Generate new userId and lecturerId
    $userId = getNextUserId($usersCollection);
    $lecturerId = getNextLecturerId($lecturersCollection);

    // Generate a unique username
    $username = generateUsername($firstName, $lastName, $usersCollection);
    // Generate email based on the username
    $email = $username . '@uow.edu';

    // Add the new user to the "users" collection
    $userData = [
        'userId' => $userId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'username' => $username,
        'password' => $password, // You should hash this in production
        'role' => 'Lecturer' // Fixed role for this case
    ];

    $insertUserResult = $usersCollection->insertOne($userData);

    if ($insertUserResult->getInsertedCount() > 0) {
        // Add the new lecturer to the "lecturers" collection
        $lecturerData = [
            'lecturerId' => $lecturerId,
            'userId' => $userId, // Reference the user we just added
            'assigned_subjects' => $assignedSubjects
        ];

        $insertLecturerResult = $lecturersCollection->insertOne($lecturerData);

        if ($insertLecturerResult->getInsertedCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'User and lecturer added successfully!']);
        } else {
            // Rollback user creation if lecturer creation fails
            $usersCollection->deleteOne(['userId' => $userId]);
            echo json_encode(['success' => false, 'message' => 'Failed to add lecturer.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
