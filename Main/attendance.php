<?php
session_start();
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit;
} else {
    $_SESSION['currentPage'] = 'dashboard';
}

require 'vendor/autoload.php';

$mongoClient = new MongoDB\Client("mongodb://localhost:27017");
$database = $mongoClient->selectDatabase("CSIT321Development");
$lecturesCollection = $database->selectCollection("lecture");
$studentsCollection = $database->selectCollection("students");
$usersCollection = $database->selectCollection("users");
$subjectsCollection = $database->selectCollection("subjects");
$lecturersCollection = $database->selectCollection("lecturers");

// Fetch userId from session
$userId = $_SESSION['userId'];

// Get lecturerId and assigned subjects from the lecturers collection by comparing userId
$lecturer = $lecturersCollection->findOne(['userId' => $userId]);
if (!$lecturer) {
    echo "Lecturer not found.";
    exit;
}

// Get the lecturer's ID and their assigned subjects
$lecturerId = $lecturer['lecturerId']; // Get lecturer's ID

// Convert BSONArray to PHP array
$assignedSubjects = (array) $lecturer['assigned_subjects'] ?? [];

// Get subjectId from URL parameter, fallback to session if not found
$subjectId = $_GET['subjectId'] ?? $_SESSION['subjectId'] ?? null;
if (!$subjectId) {
    echo "Subject not specified.";
    exit;
}

// Ensure the subjectId is in the lecturer's assigned subjects
if (!in_array($subjectId, $assignedSubjects)) {
    echo "You are not assigned to this subject.";
    exit;
}

// Fetch the subject details from the subjects collection
$subject = $subjectsCollection->findOne(['subjectId' => $subjectId]);

// Initialize total enrolled students count
$totalEnrolled = 0;

// Check if the subject exists and has enrolled students
if ($subject && isset($subject['students'])) {
    $totalEnrolled = count($subject['students']);
}

// Fetch student data for those enrolled in the selected subject
$studentIds = $subject['students'] ?? []; // Default to empty array if no students
$students = $studentsCollection->find(['studentId' => ['$in' => $studentIds]])->toArray();

// Fetch the user document based on userId for sidebar preferences
$user = $usersCollection->findOne(['userId' => $userId]);

// Check if the user has a sidebar preference
$sidebarStatus = 'large'; // Default value
if ($user && isset($user->options['sidebar'])) {
    $sidebar = (string)$user->options['sidebar'];
    if (in_array($sidebar, ['1', '0'])) {
        $sidebarStatus = $sidebar === '1' ? 'small' : 'large';
    }
}

// Function to create a new lecture
function startLecture($lecturesCollection, $subjectId, $lecturerId)
{
    // Find the latest lecture for the subject
    $latestLecture = $lecturesCollection->findOne(
        ['subjectId' => $subjectId],
        ['sort' => ['lectureId' => -1]]
    );

    // Generate the next lectureId
    if ($latestLecture) {
        $lastLectureId = $latestLecture['lectureId'];
        $lectureNumber = intval(substr($lastLectureId, strrpos($lastLectureId, 'L') + 1)) + 1;
    } else {
        $lectureNumber = 1;
    }
    $lectureId = sprintf('%s_L%03d', $subjectId, $lectureNumber);

    // Get the current date and time in the required format
    $dateTime = gmdate('Y-m-d\TH:i:s\Z');

    // Create the new lecture document
    $newLecture = [
        'attended_students' => [], // Empty array for attended students
        'lectureId' => $lectureId,
        'subjectId' => $subjectId,
        'dateTime' => $dateTime,
        'lecturerId' => $lecturerId
    ];

    // Insert the new lecture into the collection
    $lecturesCollection->insertOne($newLecture);

    // Return the created lectureId
    return $lectureId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['startLecture'])) {
    // Start a new lecture
    $createdLectureId = startLecture($lecturesCollection, $subjectId, $lecturerId);
    echo "New lecture created: $createdLectureId";
}
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Assistant:wght@200..800&family=Quicksand:wght@300..700&display=swap"
        rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/flowbite@2.5.1/dist/flowbite.min.css" rel="stylesheet" />
    <link href="./dist/output.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="./tailwind.config.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar/index.global.min.js'></script>
    <script>
        var totalAttending = 0;
        var totalEnrolled = 0;
        var attendanceRate = 0.00;
        var lectureId;
        var live = false;
        var attending = [];

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
            // Sidebar toggles (omitted for brevity)

            // Attendance click event
            $('body').on('click', '.attendance-toggle', function () {
                if (live) {
                    // Get the student ID from the parent <tr> element
                    const studentId = $(this).parent().attr('data-student-id');
                    console.log(studentId);

                    // Find the icon element within the row to check if the student is currently attending
                    const iconElement = $(this).find('i');
                    const isAttending = iconElement.hasClass('bx-check-circle');

                    // Call the updateTable function to handle attendance update
                    updateTable(studentId, isAttending);
                }
            });

            window.updateTable = function (studentId, isAttending) {
                const table = $('table');
                
                // Find the row with the matching data-student-id attribute
                const row = table.find('tr[data-student-id="' + studentId + '"]');
                
                // Get the time and format it
                const timeRegistered = new Date().toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                
                // Update the time in the row's timeColumn
                row.find('.timeColumn').text(timeRegistered);

                // Find the icon element within the row
                const iconElement = row.find('i');

                // Toggle attendance state based on isAttending
                if (isAttending) {
                    iconElement.removeClass('bx-check-circle text-successBold').addClass('bx-x-circle text-errorBold');
                    if (getAttending(studentId)){
                        attending = attending.filter(id => id !== studentId);
                        // Update total attendance and attendance rate
                        updateAttendance(studentId, !isAttending); // Send the updated attendance status
                        const attend = updateAttendanceStats(isAttending ? -1 : 1);
                        updateGraph(attend[0], attend[1]);
                    }
                } else {
                    iconElement.removeClass('bx-x-circle text-errorBold').addClass('bx-check-circle text-successBold');
                    if (!getAttending(studentId)){
                        attending.push(studentId);

                        // Update total attendance and attendance rate
                        updateAttendance(studentId, !isAttending); // Send the updated attendance status
                        const attend = updateAttendanceStats(isAttending ? -1 : 1);
                        updateGraph(attend[0], attend[1]);
                    }
                }
            };

            window.getAttending = function (studentId) {
                return attending.includes(studentId);
            }

            function updateAttendanceStats(change) {
                const totalAttending = parseInt($('#totalAttending').text());
                const totalEnrolled = parseInt($('#totalEnrolled').text());
                const newTotalAttending = totalAttending + change;

                $('#totalAttending').text(newTotalAttending);

                const attendanceRate = ((newTotalAttending / totalEnrolled) * 100).toFixed(2);
                $('#averageAttendance').text(attendanceRate + '%');

                return [newTotalAttending, <?php echo count($subject["students"])?> - newTotalAttending];
            }

            function updateAttendance(studentId, isAttending) {
                $.ajax({
                    url: './src/events/lectures/updateAttendance.php',
                    method: 'POST',
                    data: { 
                        lectureId: lectureId,
                        studentId: studentId, 
                        isAttending: isAttending ? '1' : '0' 
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            console.log(`Attendance updated for student ID: ${studentId}`);
                        } else {
                            console.error('Error updating attendance:', data.error);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Request failed:', textStatus, errorThrown);
                    }
                });
            }

            // Webcam
            let stream = null;
            let live = false;
            let intervalId = null;
            const videoElement = document.querySelector('video');

            const startButton = document.getElementById('startButton');
            async function startWebcam() {

                if (!videoElement) {
                    console.error('Video element not found');
                    return;
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    console.error('getUserMedia is not supported by this browser.');
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    videoElement.srcObject = stream;
                    live = true;
                    $('#liveStatus').text("Live");
                    $('#liveStatus').removeClass('text-errorBold').addClass('text-successBold');
                    $('#liveIcon').removeClass('bx-stop-circle').addClass('bx-check-circle text-successBold');

                    startLecture();
                    // 
                    intervalId = setInterval(() => {
                        if (live) {
                            sendImage();
                        }
                    }, 200);

                } catch (error) {
                    console.error('Error accessing webcam:', error.message, error.name, error.stack);
                }
            }

            function stopWebcam() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    videoElement.srcObject = null;
                    live = false;
                    $('#liveStatus').text("Stopped");
                    $('#liveStatus').removeClass('text-successBold').addClass('text-errorBold');
                    $('#liveIcon').removeClass('bx-check-circle text-successBold').addClass('bx-stop-circle');
                    clearInterval(intervalId);
                }
            }

            function startLecture() {
                const subjectId = '<?php echo $_GET['subjectId'];?>';

                $.ajax({
                    url: './src/events/lectures/createLecture.php',
                    method: 'POST',
                    data: { subjectId: subjectId },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            console.log(`New lecture created: ${data.lectureId}`);
                            lectureId = data.lectureId;
                        } else {
                            console.error('Error starting lecture:', data.error);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log(subjectId);
                        console.error('Request failed:', textStatus, errorThrown);
                    }
                });
            }

            startButton.addEventListener('click', function() {
                if (live) {
                    stopWebcam();
                }
                else{
                    startWebcam();
                }
            });

            // CHANGE THIS FUNCTION
            function sendImage() {
                console.log("Sent");
            }

            function createGraph(attending, absent) {
                // Donut Graph
                var donutSeries = [attending, absent];
                var donutLabels = ["Attending", "Absent"];

                var chart = {
                    series: donutSeries,
                    chart: {
                        height: 500,
                        width: "100%",
                        type: "donut",
                    },
                    stroke: {
                        colors: ["transparent"],
                        lineCap: "d",
                    },
                    grid: {
                        padding: {
                            top: -2,
                        },
                    },
                    labels: donutLabels,
                    legend: {
                        position: "bottom",
                        fontFamily: "Quicksand",
                        offsetY: -230
                    },
                    toolbar: {
                        show: true,
                        offsetX: 0,
                        offsetY: 0,
                        tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        customIcons: []
                        },
                        export: {
                        svg: {
                            filename: undefined,
                        },
                        png: {
                            filename: undefined,
                        }
                        },
                        autoSelected: 'zoom' 
                    },
                };

                // Render the chart
                var bChart = new ApexCharts(document.querySelector("#bar-chart"), chart);
                bChart.render();
            }

            createGraph(0, <?php echo count($subject['students'])?>);

            function updateGraph(attending, absent) {
                $('#bar-chart').empty();

                createGraph(attending, absent);
            }
            // function capturePhoto() {
            //     canvasElement.width = videoElement.videoWidth;
            //     canvasElement.height = videoElement.videoHeight;
            //     canvasElement.getContext('2d').drawImage(videoElement, 0, 0);
            //     const photoDataUrl = canvasElement.toDataURL('image/jpeg');
            //     photoElement.src = photoDataUrl;
            //     photoElement.style.display = 'block';
            // }

            // captureButton.addEventListener('click', capturePhoto);
        });
    </script>
</head>

<body class="bg-background p-0 m-0">
    <div id="sidebar"><?php include './src/components/sidebar.php'; ?></div>

    <div id="main-content" class="p-20 flex flex-col h-screen justify-between">
        <div class="grid grid-cols-[auto,1fr] auto-rows-min gap-y-20 gap-x-44 w-full h-full">
            <!-- Statistics -->
            <div class="grid grid-cols-2 gap-16 w-fit h-fit">
                <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                    <span id="totalEnrolled" class="text-2xl font-bold"><?php echo $totalEnrolled; ?></span>
                    <span class="text-lg">Total Enrolled</span>
                </div>
                <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                    <span id="totalAttending" class="text-2xl font-bold">0</span>
                    <span class="text-lg">Total Attending</span>
                </div>
                <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                    <span id="averageAttendance" class="text-2xl font-bold">0%</span>
                    <span class="text-lg">Attendance Rate</span>
                </div>
                <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                    <i id="liveIcon" class="bx bx-stop-circle text-5xl text-errorBold"></i>
                    <span id="liveStatus" class="text-lg text-errorBold">Stopped</span>
                </div>
            </div>

            <!-- Dynamic Students Table -->
            <div class="bg-menu p-4 w-full h-fit rounded-lg max-h-[384px] overflow-y-scroll">
                <table class="w-full rounded-lg">
                    <tr>
                        <th>Student Name</th>
                        <th>Student Number</th>
                        <th>Time Registered</th>
                        <th>Attendance</th>
                    </tr>
                    <?php foreach ($students as $student): ?>
                    <tr data-student-id="<?php echo $student['studentId']; ?>">
                        <td><?php echo $student['firstName']; ?></td>
                        <td><?php echo $student['studentId']; ?></td>
                        <td class="timeColumn"><?php echo isset($student['time_registered']) ? $student['time_registered'] : 'N/A'; ?></td>
                        <td class="attendance-toggle">
                            <i class="ml-6 bx <?php echo isset($student['attendance']) && $student['attendance'] === true ? 'bx-check-circle text-successBold' : 'bx-x-circle text-errorBold'; ?> text-xl"></i>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="flex flex-row gap-[132px] justify-between">
            <div class="flex flex-col gap-28">
                <a href="#" id="startButton" class="text-textColour componentButton bg-successBold">
                    <i class='bx bx-x-circle'></i>
                    <span>Start Taking Attendance</span>
                </a>
                <div id="barGraphContainer" class="p-6 bg-menu shadow-lg rounded-lg">
                    <div class="flex justify-between">
                        <div class="flex flex-col flex-grow">
                            <div class="flex items-center gap-2 text-textColour">
                                <span class="text-lg font-bold"><?php echo $_GET['subjectId']; ?></span>
                                <span class="text-sm text-textAccent"><?php echo date('m/d/Y', time()); ?></span>
                            </div>
                        </div>
                        <a href="#nothing" class="text-lg">
                            <i class="text-textColour bx bx-dots-horizontal-rounded"></i>
                        </a>
                    </div>

                    <div id="bar-chart" class="py-4"></div>

                    <div class="flex items-center pt-4 border-t border-gray-500">
                        <a href="#nothing" class="text-textAccent flex flex-grow items-center gap-2">
                            <span>Lecture</span>
                        </a>
                    </div>
                </div>
            </div>
                <div class="w-full h-[600px] gap-10 flex justify-center p-4 bg-menu shadow-lg rounded-lg">
                    <div class="relative h-full overflow-hidden"><span class="text-base font-bold"></span>
                        <video autoplay="true" id="video" class="h-[95%] border-4 border-accentBold rounded-lg shadow-lg"></video>
                        <canvas id="canvas" style="display: none;"></canvas>
                    </div>
                </div>
        </div>
    </div>
</body>

<script src="./node_modules/flowbite/dist/flowbite.min.js"></script>

</html>