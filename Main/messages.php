<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
}
else{
    $_SESSION['currentPage'] = 'messages';
}

require 'vendor/autoload.php';
$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$usersCollection = $database->selectCollection("users");

// Fetch the user document based on userId
$userId = $_SESSION['userId'];
$user = $usersCollection->findOne(['userId' => $userId]);

// Check if the user document exists and if the sidebar field is present
$sidebarStatus = 'large'; // Default value
if ($user && isset($user->options['sidebar'])) {
    $sidebar = (string)$user->options['sidebar'];
    if (in_array($sidebar, ['1', '0'])) {
        $sidebarStatus = $sidebar === '1' ? 'small' : 'large';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="svg" href="./src/assets/favicon.ico">

    <!-- Styles -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@200..800&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
    <link href="./dist/output.css" rel="stylesheet">

    <!-- Scripts -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <script src="./tailwind.config.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
    <script>
        $(document).ready(function () {
            if ("<?php echo $sidebarStatus?>" === "small"){
                $("#sidebar-content").addClass("hidden");
                $("#sidebar-content").addClass("-translate-x-full");
                $("#main-content").toggleClass("ml-sidebarSmall")
            }
            else{
                $("#main-content").toggleClass("ml-sidebarLarge")
            }
            
            $("#sidebar-large-button").click(function (){
                $("#main-content").toggleClass("ml-sidebarLarge")
                $("#main-content").toggleClass("ml-sidebarSmall")
            })
            $("#sidebar-small-button").click(function (){
                $("#main-content").toggleClass("ml-sidebarSmall")
                $("#main-content").toggleClass("ml-sidebarLarge")
                $("#sidebar-content").removeClass("hidden");
            })
        });
    </script>
</head>

<body class="bg-background p-0 m-0">
    <div id="sidebar"><?php include './src/components/sidebar.php';?></div>

    <div id="main-content" class="p-20 flex flex-col h-screen justify-between"><?php include './src/components/chat.php'; ?></div>
</body>

<script src="./node_modules/flowbite/dist/flowbite.min.js"></script>
</html>