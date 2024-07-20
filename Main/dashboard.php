<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
else{
    $_SESSION['currentPage'] = 'dashboard';
}

require 'vendor/autoload.php';

function fetchLectureData() {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturesCollection = $database->selectCollection("lectures");

    return $lecturesCollection->find()->toArray();
}

function generateDashboard($lectureData) {
    // Generate dashboard content based on lecture data
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="svg" href="./src/assets/favicon.ico">
        <title>Dashboard</title>

        <!-- Styles -->
        <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
        <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

        <link href="./dist/output.css" rel="stylesheet">

        <!-- Scripts -->
        <script src="./tailwind.config.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.js"
            integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
    </head>

    <body class="bg-background p-0 m-0">
        <div id="sidebar"><?php include './src/components/sidebar.php'; ?></div>

        <div id="main-content" class="p-20 ml-304 flex flex-col h-screen justify-between"><?php include './src/components/dash.php'; ?></div>
    </body>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    </html>
<?php
    return ob_get_clean();
}

$lectureData = fetchLectureData();
echo generateDashboard($lectureData);
?>
