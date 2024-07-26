<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
else{
    $_SESSION['currentPage'] = 'messages';
}

require 'vendor/autoload.php';

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

    <link href="./dist/output.css" rel="stylesheet">

    <!-- Scripts -->
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <script src="./tailwind.config.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
</head>

<body class="bg-background p-0 m-0">
    <div id="sidebar"><?php include './src/components/sidebar.php';?></div>

    <div id="main-content" class="p-20 ml-304 flex flex-col h-screen justify-between"><?php include './src/components/chat.php'; ?></div>
</body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</html>