<?php
require 'vendor/autoload.php';

function getSidebarData($userId) {
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $usersCollection = $database->selectCollection("users");

    // Find user details
    $user = $usersCollection->findOne(['userId' => $userId]);
    if (!$user) {
        return [];
    }

    return [
        'logoSVG' => './src/assets/logo.svg', // Adjust the path to your logo
        'logoPNG' => './src/assets/logo.png', // Adjust the path to your logo
        'user' => [
            'username' => $user['username'],
            'firstName' => $user['first_name'],
            'lastName' => $user['last_name'],
            'email' => $user['email']
        ],
        'role' => $user['role']
    ];
}

function generateSidebar($data, $currentPage) {
    $pages = array(
        'dashboard' => array(
            'text' => 'Dashboard',
            'icon' => 'bx bxs-home',
            'href' => 'dashboard.php'
        ),
        'messages' => array(
            'text' => 'Messages',
            'icon' => 'bx bxs-message',
            'href' => 'messages.php'
        ),
        'subjects' => array(
            'text' => 'Subjects',
            'icon' => 'bx bxs-book-alt',
            'href' => 'subjects.php'
        ),
        'students' => array(
            'text' => 'Students',
            'icon' => 'bx bxs-user',
            'href' => 'students.php'
        ),
        'reports' => array(
            'text' => 'Reports',
            'icon' => 'bx bxs-bar-chart-alt-2',
            'href' => 'reports.php'
        )
    );
    
    if ($data['role'] == "Admin") {
        $lecturers = array(
            'lecturers' => array(
                'text' => 'Lecturers',
                'icon' => 'bx bxs-user-voice',
                'href' => 'lecturers.php'
            )
        );
    
        // Split the array and merge the new element
        $pages = array_slice($pages, 0, 4, true) +
                $lecturers +
                array_slice($pages, 4, null, true);
    }

    $largeButtonsHTML = '<ul class="text-md flex flex-col flex-grow w-240">';
    foreach ($pages as $pageId => $page) {
        $activeClass = ($currentPage === $pageId) ? 'componentButtonActive' : '';
        $largeButtonsHTML .= '
            <li class="my-1">
                <a href="' . $page['href'] . '" class="componentButton ' . $activeClass . '">
                    <i class="' . $page['icon'] . '"></i>
                    <span>' . $page['text'] . '</span>
                </a>
            </li>';
    }
    $largeButtonsHTML .= '</ul>';

    $smallButtonsHTML = '<ul class="text-2xl flex flex-col items-center flex-grow w-20">';
    foreach ($pages as $pageId => $page) {
        $activeClass = ($currentPage === $pageId) ? 'componentButtonSmallActive' : '';
        $smallButtonsHTML .= '
            <li class="my-1">
                <a href="' . $page['href'] . '" class="componentButtonSmall ' . $activeClass . '">
                    <i class="' . $page['icon'] . '"></i>
                </a>
            </li>';
    }
    $smallButtonsHTML .= '</ul>';
    
    return '
        <div id="sidebar-content" class="sidebar z-20 " tabindex="-1">
        <div class="flex items-center flex-col p-4 bg-background h-screen w-280">
            <a href="#" class="flex items-center w-full text-3xl pl-1">
                <i class="pr-4">
                    <object class="h-10 w-10" data="' . $data['logoSVG'] . '" type="image/svg+xml">
                        <img src="' . $data['logoPNG'] . '">
                    </object>
                </i>
                <span>LECTORO</span>
            </a>

            <hr class="my-4 opacity-25 w-full">

            ' . $largeButtonsHTML . '

            <hr class="my-4 opacity-25 w-full">

            <div class="flex text-left w-full">
                <div class="flex-grow">
                    <button data-dropdown-toggle="dropdown-menu" type="button" class="group w-full flex items-center" id="dropdown-button">
                        <i class="bx bxs-user text-2xl pr-1.5"></i>
                        <span id="username">' . $data['user']['username'] . '</span>
                        <i id="dropdown-arrow" class="group-hover:text-accent transition-color ease-out duration-300 bx bx-chevron-down pl-1.5"></i>
                    </button>
                </div>
                <div class="self-center">
                    <button type="button" class="hover:text-accent shadow-lg" id="sidebar-large-button" data-drawer-target="sidebar-content" data-drawer-body-scrolling="true" data-drawer-hide="sidebar-content" data-drawer-backdrop="false" data-drawer-placement="left"><i class="bx bx-left-arrow"></i></button>
                </div>

                <div id="dropdown-menu"
                    class="hidden overflow-hidden z-20 mt-2 w-40 rounded-lg bg-menu shadow-[0_.25rem_.5rem_rgba(0,0,0,0.31)]">
                    <div>
                        <div class="py-3 select-none">
                            <p class="px-4 ">' . $data['user']['firstName'] . ' ' . $data['user']['lastName'] . '</p>
                            <p class="px-4 text-sm text-textAccent">' . $data['user']['email'] . '</p>
                        </div>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Settings</a>
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Profile</a>
                        <hr class="opacity-20">
                        <a href="./src/events/signout.php"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Sign
                            out</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="shadow-divider bg-blackOpaque w-6 h-screen z-10"></div>
    </div>

    <div id="sidebar-content-small" class="sidebar z-10" tabindex="-2">
        <div class="flex items-center flex-col p-4 bg-background h-screen w-20">
            <a href="#" class="text-3xl">
                <i>
                    <object class="h-10 w-10" data="' . $data['logoSVG'] . '" type="image/svg+xml">
                        <img src="' . $data['logoSVG'] . '">
                    </object>
                </i>
            </a>

            <hr class="my-4 opacity-25 w-full">

            ' . $smallButtonsHTML . '

            <hr class="my-4 opacity-25 w-full">

            <div class="flex items-center">
                <div>
                    <button data-dropdown-toggle="dropdown-menu-small" data-dropdown-placement="top-end" type="button" class="group w-full flex items-center"
                        id="dropdown-button-small">
                        <i class="group-hover:text-accent transition-color ease-out duration-300 bx bxs-user text-2xl"></i>
                    </button>
                </div>
                <div class="self-center relative">
                    <button id="sidebar-small-button" class="hover:text-accent shadow-lg" data-drawer-target="sidebar-content-small" data-drawer-body-scrolling="true" data-drawer-show="sidebar-content" data-drawer-backdrop="false" data-drawer-placement="left"><i class="absolute left-[72px] right-0 top-0 bottom-0 bx bx-right-arrow"></i></button>
                </div>
                <div id="dropdown-menu-small"
                    class="hidden overflow-hidden z-20 mt-2 w-40 rounded-lg bg-menu shadow-[0_.25rem_.5rem_rgba(0,0,0,0.31)]">
                    <div>
                        <div class="py-3 select-none">
                            <p class="px-4 ">' . $data['user']['firstName'] . ' ' . $data['user']['lastName'] . '</p>
                            <p class="px-4 text-sm text-textAccent">' . $data['user']['email'] . '</p>
                        </div>
                        <hr class="opacity-20">
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Settings</a>
                        <a href="#"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Profile</a>
                        <hr class="opacity-20">
                        <a href="./src/events/signout.php"
                            class="hover:bg-buttonHover transition ease-out duration-300 block px-4 py-2 text-md">Sign
                            out</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="shadow-divider bg-blackOpaque w-6 h-screen z-10"></div>
    </div>
';}

if (isset($_SESSION['userId'])) {
    $data = getSidebarData($_SESSION['userId']);
    $currentPage = isset($_SESSION['currentPage']) ? $_SESSION['currentPage'] : 'dashboard';
    // Generate Sidebar HTML
    echo generateSidebar($data, $currentPage);
} else {
    echo "User not logged in";
}
?>
