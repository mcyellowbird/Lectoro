<?php
require 'vendor/autoload.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Connect to MongoDB
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");

        // Select database and collection
        $database = $mongoClient->selectDatabase("CSIT321Development");
        $usersCollection = $database->selectCollection("users");

        // Find user by username and password
        $user = $usersCollection->findOne(['username' => $username, 'password' => $password]);

        if ($user) {
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_id'] = $user['user_id'];
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Invalid username or password.";
            $alert_id = 'popup-alert-' . rand();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="svg" href="/Images/NexTech_logo.svg">

    <!-- Styles -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300..700&display=swap" rel="stylesheet">

    <link href="./src/css/index.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="./tailwind.config.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
</head>

<body class="bg-background p-0 m-0">
    <div id="main-content" class="flex flex-col h-screen justify-center items-center">
        <div class="absolute top-20 flex flex-col items-center">
            <img class="h-80" src="./src/assets/logo_text.png" />
        </div>
        <div>
            <div class="w-fit h-fit bg-menu text-textColour rounded-lg shadow-lg">
                <form class="py-6 px-10" method="post">
                    <div class="flex flex-col items-center gap-4">
                    <span class="text-center text-2xl">Login</span>
                    <div class="relative">
                        <i class="absolute inset-y-0 start-2 flex items-center bx bxs-user"></i>
                        <input type="text" name="username"
                            class="pl-8 bg-buttonHover border border-menu text-textColour placeholder-textAccent text-sm rounded-lg block w-full p-2.5"
                            placeholder="Username" required>
                    </div>
                    <div class="relative">
                        <i class="absolute inset-y-0 start-2 flex items-center bx bxs-lock-open"></i>
                        <input type="password" name="password"
                            class="pl-8 bg-buttonHover border border-menu text-textColour placeholder-textAccent text-sm rounded-lg block w-full p-2.5"
                            placeholder="Password" required>
                    </div>
                        <a href="#nothing" class="text-accentBold hover:text-accentDark">Forgot password?</a>
                        <button type="submit"
                            class="text-textColour w-20 bg-accentBold hover:bg-accentDark rounded-xl text-sm px-5 py-2.5 text-center">Login</button>
                    </div>
                </form>
            </div>
            <div class="pt-4">
                <?php if (isset($error_message)) : ?>
                    <p class="text-center text-error"><?php echo $error_message; ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>

</html>