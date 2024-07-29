<?php
require 'vendor/autoload.php';

function getDashboardData($userId)
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturersCollection = $database->selectCollection("lecturers");
    $subjectsCollection = $database->selectCollection("subjects");
    $usersCollection = $database->selectCollection("users");
    $lecturesCollection = $database->selectCollection("lectures");
    // Find lecturer by user_id
    $lecturer = $lecturersCollection->findOne(['user_id' => $userId]);
    if (!$lecturer) {
        return [];
    }

    // Find user details
    $user = $usersCollection->findOne(['user_id' => $userId]);
    if (!$user) {
        return [];
    }

    // Extract relevant data from the lecturer's document
    $assignedSubjectIds = $lecturer['assigned_subjects'];
    $subjects = [];
    $studentsCount = 0;
    $totalAttendanceRate = 0;
    $totalSubjects = 0;
    $lectures = [];
    foreach ($assignedSubjectIds as $subjectId) {
        $subject = $subjectsCollection->findOne(['subject_id' => $subjectId]);
        if ($subject) {
            $subjects[] = $subject;

            // Calculate total attendance and lectures for this subject
            $totalAttendance = 0;
            $totalLectures = 0;
            $lectures = $lecturesCollection->find([
                'lecturer_id' => $lecturer['lecturer_id'],
                'subject_id' => $subjectId
            ]);

            foreach ($lectures as $lecture) {
                $totalLectures++;
                $totalAttendance += count($lecture['attended_students']);
            }

            // Debugging: Print out the calculated values
            // echo "Subject: " . json_encode($subject) . "\n";
            // echo "Total Lectures: " . $totalLectures . "\n";
            // echo "Total Attendance: " . $totalAttendance . "\n";

            // Calculate the attendance rate for this subject
            $subjectAttendanceRate = $totalLectures > 0 ? ($totalAttendance / (count($subject['students']) * $totalLectures)) * 100 : 0;
            $subject['attendance_rate'] = $subjectAttendanceRate;

            // Debugging: Print out the calculated attendance rate
            // echo "Attendance Rate: " . $subjectAttendanceRate . "\n";

            // Increment total attendance rate and subjects count
            $totalAttendanceRate += $subjectAttendanceRate;
            $totalSubjects++;
            $studentsCount += count($subject['students']);
        }
    }
    // Calculate the overall average attendance rate for all subjects
    $overallAverageAttendance = $totalSubjects > 0 ? $totalAttendanceRate / $totalSubjects : 0;

    // Debugging: Print out the overall average attendance rate
    // echo "Overall Average Attendance Rate: " . $overallAverageAttendance . "\n";



    // Calculate average attendance
    $totalAttendance = 0;
    $attendanceRecords = $lecturer['attendance'] ?? [];
    foreach ($attendanceRecords as $record) {
        $totalAttendance += $record['attendance'];
    }
    $averageAttendance = count($attendanceRecords) > 0 ? $totalAttendance / count($attendanceRecords) : 0;

    // Array to store upcoming classes
$upcomingClasses = [];

// Array to store previous classes with attendance
$previousClasses = [];

// Loop through each subject for upcoming classes
foreach ($subjects as $subject) {
    if (isset($subject['timetable']) && !empty($subject['timetable'])) {
        foreach ($subject['timetable'] as $lecture) {
            $lectureTime = new DateTime($lecture);
            $currentTime = new DateTime();

            // Handle upcoming classes
            if ($lectureTime > $currentTime) {
                $upcomingClasses[] = [
                    'subjectName' => $subject['subject_name'],
                    'subjectCode' => $subject['subject_code'],
                    'studentsEnrolled' => count($subject['students']),
                    'duration' => $subject['duration'],
                    'day' => $lectureTime->format('l'),
                    'date' => $lectureTime->format('Y-m-d'),
                    'time' => $lectureTime->format('H:i'),
                ];
            }
        }
    }
}

// Loop through each subject for previous classes using lecturesCollection
foreach ($subjects as $subject) {
    // Find all lectures for the current subject and lecturer
    $lectures = $lecturesCollection->find([
        'lecturer_id' => $lecturer['lecturer_id'],
        'subject_id' => $subject['subject_id'],
    ]);

    foreach ($lectures as $lecture) {
        $lectureTime = new DateTime($lecture['date_time']);
        $currentTime = new DateTime();

        // Handle previous classes
        if ($lectureTime < $currentTime) {
            // Calculate attendance rate for the lecture
            $attendance = count($lecture['attended_students']);

            // Add to previousClasses array
            $previousClasses[] = [
                'subjectName' => $subject['subject_name'],
                'subjectCode' => $subject['subject_code'],
                'studentsEnrolled' => count($subject['students']),
                'duration' => $subject['duration'],
                'day' => $lectureTime->format('l'),
                'date' => $lectureTime->format('Y-m-d'),
                'time' => $lectureTime->format('H:i'),
                'attendance' => round(($attendance / count($subject['students'])) * 100, 2) . '%',
            ];
        }
    }
}

// Debugging: Print counts to check number of classes processed
// echo "Upcoming classes count: " . count($upcomingClasses) . "\n";
// echo "Previous classes count: " . count($previousClasses) . "\n";


    
    // Ensure the classes are sorted by date and time
    usort($upcomingClasses, function($a, $b) {
        return strtotime($a['date'] . ' ' . $a['time']) - strtotime($b['date'] . ' ' . $b['time']);
    });
    
    usort($previousClasses, function($a, $b) {
        return strtotime($b['date'] . ' ' . $b['time']) - strtotime($a['date'] . ' ' . $a['time']);
    });    

    // Find the next lecture
    $nextLecture = null;
    foreach ($upcomingClasses as $class) {
        if (!$nextLecture || strtotime($class['date'] . ' ' . $class['time']) < strtotime($nextLecture['date'] . ' ' . $nextLecture['time'])) {
            $nextLecture = $class;
        }
    }

    return [
        'lectures' => $lectures,
        'subjects' => $subjects,
        'totalSubjects' => $totalSubjects,
        'totalStudents' => $studentsCount,
        'averageAttendance' => $overallAverageAttendance,
        'nextLecture' => $nextLecture,
        'upcomingClasses' => $upcomingClasses,
        'previousClasses' => $previousClasses,
    ];
}

if (isset($_SESSION['user_id'])) {
    $data = getDashboardData($_SESSION['user_id']);
    ?>
    <script>
        $(document).ready(function () {
            // Additional scripts for FullCalendar and ApexCharts
            const calendarEl = document.getElementById('calendar')
            <?php if (!empty($data['upcomingClasses'])) { ?>
                const calEvents = [
                    <?php foreach ($data['upcomingClasses'] as $index => $class) { ?>
                                        <?php if ($index > 0) { ?>, <?php } ?>
                                        {
                            title: '<?php echo $class['subjectCode']; ?>',
                            start: '<?php echo $class['date'] . "T" . $class['time']; ?>'
                        }
                                <?php } ?>
                ];
            <?php } else { ?>
                const calEvents = [];
            <?php } ?>

            const calendar = new FullCalendar.Calendar(calendarEl, {
                timeZone: 'UTC',
                initialView: 'multiMonthYear',
                events: calEvents,
                selectable: true,
                eventColor: '#007bff',
                buttonText: { today: 'TODAY' },
            });
            calendar.render();

            // Donut Graph
            var donutSeries = [];
            var donutLabels = [];

            <?php foreach ($data['subjects'] as $subject) { ?>
                donutSeries.push(<?php echo $subject['attendance_rate']; ?>);
                donutLabels.push('<?php echo $subject['subject_code']; ?>');
            <?php } ?>

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
                },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return value + "%";
                        },
                    },
                },
                xaxis: {
                    labels: {
                        formatter: function (value) {
                            return value + "%";
                        },
                    },
                },
            };

            // Render the chart
            var bChart = new ApexCharts(document.querySelector("#bar-chart"), chart);
            bChart.render();
        });
    </script>
    <div class="grid grid-cols-[auto,1fr] auto-rows-min gap-y-20 gap-x-44 w-full h-full">
        <!-- Statistics -->
        <div class="grid grid-cols-2 gap-16 w-fit h-fit">
            <!-- Key Data dynamically populated by JavaScript -->
            <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                <span class="text-2xl font-bold"
                    id="totalSubjects"><?php echo $data['totalSubjects'] ? $data['totalSubjects'] : 'N/A'; ?></span>
                <span class="text-lg">Total Subjects</span>
            </div>
            <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                <span class="text-2xl font-bold"
                    id="totalStudents"><?php echo $data['totalStudents'] ? $data['totalStudents'] : 'N/A'; ?></span>
                <span class="text-lg">Total Students</span>
            </div>
            <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                <span class="text-2xl font-bold" id="averageAttendance">
                    <?php echo isset($data['averageAttendance']) ? number_format((float) $data['averageAttendance'], 2) . '%' : 'N/A'; ?>
                </span>
                <span class="text-lg">Avg Attendance</span>
            </div>
            <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                <span class="text-2xl font-bold"
                    id="nextClass"><?php echo $data['upcomingClasses'] ? $data['upcomingClasses'][0]['date'] : 'N/A'; ?></span>
                <span class="text-lg">Next Lecture</span>
            </div>
        </div>
        <?php

        // Define pagination variables
        $upage = isset($_GET['upage']) && is_numeric($_GET['upage']) ? (int) $_GET['upage'] : 1;
        $ppage = isset($_GET['ppage']) && is_numeric($_GET['ppage']) ? (int) $_GET['ppage'] : 1;
        $records_per_page = 6;
        $ustart_from = ($upage - 1) * $records_per_page;
        $pstart_from = ($ppage - 1) * $records_per_page;
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'upcoming';

        // Fetch only the required subset of upcomingClasses based on pagination
        $subset_upcomingClasses = array_slice($data['upcomingClasses'], $ustart_from, $records_per_page);
        $subset_previousClasses = array_slice($data['previousClasses'], $pstart_from, $records_per_page);
        ?>

        <!-- Lessons Table -->
        <div class="flex flex-col bg-menu p-4 w-auto rounded-lg h-tableWithTabs">
            <div class="text-sm font-medium text-center border-b border-textAccent text-textAccent">
                <ul class="flex flex-wrap text-lg" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
                    <li class="me-2" role="presentation">
                        <button href="#" class="inline-block p-4 border-b-2 rounded-t-lg <?php echo $tab === 'upcoming' ? 'text-accentDark border-accent' : 'border-transparent hover:border-textAccent hover:text-textAccent'; ?>"
                            id="upcomingLessons-tab" data-tabs-target="#upcomingLessons" type="button" role="tab" aria-controls="upcoming" aria-selected="<?php echo $tab === 'upcoming' ? 'true' : 'false'; ?>">Upcoming Lessons</button>
                    </li>
                    <li class="me-2" role="presentation">
                        <button class="inline-block p-4 border-b-2 rounded-t-lg <?php echo $tab === 'previous' ? 'text-accentDark border-accent' : 'border-transparent hover:border-textAccent hover:text-textAccent'; ?>"
                            id="previousLessons-tab" data-tabs-target="#previousLessons" type="button" role="tab" aria-controls="previous" aria-selected="<?php echo $tab === 'previous' ? 'true' : 'false'; ?>">Previous Lessons</button>
                    </li>
                </ul>
            </div>

            <div class="flex-grow" id="default-tab-content">
                <!-- Upcoming -->
                <div class="justify-between flex flex-col items-center h-full <?php echo $tab === 'upcoming' ? '' : 'hidden'; ?>" id="upcomingLessons" role="tabpanel" aria-labelledby="upcomingLessons-tab">
                    <table class="mt-4 text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-collapse border-spacing-0">
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Students Enrolled</th>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                        </tr>
                        <?php foreach ($subset_upcomingClasses as $class) { ?>
                            <tr>
                                <td><?php echo $class['subjectName']; ?></td>
                                <td><?php echo $class['subjectCode']; ?></td>
                                <td><?php echo $class['studentsEnrolled']; ?></td>
                                <td><?php echo $class['day']; ?></td>
                                <td><?php echo $class['date']; ?></td>
                                <td><?php echo $class['time']; ?></td>
                                <td><?php
                                    $duration = $class['duration'];
                                    if ($duration >= 60) {
                                        $hours = floor($duration / 60);
                                        echo $hours . " " . ($hours == 1 ? "hour" : "hours");
                                    } else {
                                        echo $duration . " minute" . ($duration == 1 ? "" : "s");
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex flex-col items-center">
                        <!-- Help text -->
                        <span class="text-sm text-textColour">
                            Showing <span class="font-semibold text-accent"><?php echo $ustart_from + 1; ?></span> to
                            <span class="font-semibold text-accent"><?php echo min($ustart_from + $records_per_page, count($data['upcomingClasses'])); ?></span>
                            of <span class="font-semibold text-accent"><?php echo count($data['upcomingClasses']); ?></span>
                            Entries
                        </span>
                        <!-- Buttons -->
                        <div class="inline-flex mt-2 xs:mt-0">
                            <a <?php echo $upage != 1 ? 'href="?tab=upcoming&upage=' . ($upage - 1) . '"' : ''; ?>
                                class="flex items-center justify-center px-3 h-8 text-sm font-medium border-textAccent bg-menu border rounded-s-lg <?php echo $upage == 1 ? 'cursor-not-allowed text-textAccent border-opacity-10' : 'text-textColour hover:bg-accent border-opacity-40'; ?>">
                                Prev
                            </a>
                            <a href="?tab=upcoming&upage=<?php echo $upage + 1; ?>"
                                class="flex items-center justify-center px-3 h-8 text-sm font-medium text-textColour bg-menu border border-s border-textAccent/40 rounded-e-lg hover:bg-accent <?php echo $ustart_from + $records_per_page >= count($data['upcomingClasses']) ? 'hidden' : ''; ?>">
                                Next
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Previous -->
                <div class="justify-between flex flex-col items-center h-full <?php echo $tab === 'previous' ? '' : 'hidden'; ?>" id="previousLessons" role="tabpanel" aria-labelledby="previousLessons-tab">
                    <table class="mt-4 text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-collapse border-spacing-0">
                        <tr>
                            <th>Subject Name</th>
                            <th>Subject Code</th>
                            <th>Students Enrolled</th>
                            <th>Day</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Duration</th>
                            <th>Attendance</th>
                        </tr>
                        <?php foreach ($subset_previousClasses as $class) { ?>
                            <tr>
                                <td><?php echo $class['subjectName']; ?></td>
                                <td><?php echo $class['subjectCode']; ?></td>
                                <td><?php echo $class['studentsEnrolled']; ?></td>
                                <td><?php echo $class['day']; ?></td>
                                <td><?php echo $class['date']; ?></td>
                                <td><?php echo $class['time']; ?></td>
                                <td><?php
                                    $duration = $class['duration'];
                                    if ($duration >= 60) {
                                        $hours = floor($duration / 60);
                                        echo $hours . " " . ($hours == 1 ? "hour" : "hours");
                                    } else {
                                        echo $duration . " minute" . ($duration == 1 ? "" : "s");
                                    }
                                    ?>
                                </td>
                                <td><?php echo $class['attendance']; ?></td>
                            </tr>
                        <?php } ?>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4 flex flex-col items-center">
                        <!-- Help text -->
                        <span class="text-sm text-textColour">
                            Showing <span class="font-semibold text-accent"><?php echo $pstart_from + 1; ?></span> to
                            <span class="font-semibold text-accent"><?php echo min($pstart_from + $records_per_page, count($data['previousClasses'])); ?></span>
                            of <span class="font-semibold text-accent"><?php echo count($data['previousClasses']); ?></span>
                            Entries
                        </span>
                        <!-- Buttons -->
                        <div class="inline-flex mt-2 xs:mt-0">
                            <a <?php echo $ppage != 1 ? 'href="?tab=previous&ppage=' . ($ppage - 1) . '"' : ''; ?>
                                class="flex items-center justify-center px-3 h-8 text-sm font-medium border-textAccent bg-menu border rounded-s-lg <?php echo $ppage == 1 ? 'cursor-not-allowed text-textAccent border-opacity-10' : 'text-textColour hover:bg-accent border-opacity-40'; ?>">
                                Prev
                            </a>
                            <a href="?tab=previous&ppage=<?php echo $ppage + 1; ?>"
                                class="flex items-center justify-center px-3 h-8 text-sm font-medium text-textColour bg-menu border border-s border-textAccent/40 rounded-e-lg hover:bg-accent <?php echo $pstart_from + $records_per_page >= count($data['previousClasses']) ? 'hidden' : ''; ?>">
                                Next
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Graph -->
        <div id="barGraphContainer" class="self-end w-graph h-fit p-6 bg-menu shadow-lg rounded-lg">
            <div class="flex justify-between">
                <div class="flex flex-col flex-grow">
                    <div class="flex items-center gap-2 text-textColour">
                        <span class="text-lg font-bold">Average Attendance</span>
                        <a href="#nothing">
                            <i class="bx bx-info-circle"></i>
                        </a>
                    </div>
                </div>
                <a href="#nothing" class="text-lg">
                    <i class="text-textColour bx bx-dots-horizontal-rounded"></i>
                </a>
            </div>

            <div id="bar-chart" class="py-4"></div>

            <div class="flex items-center pt-4 border-t border-gray-500">
                <a href="#nothing" class="text-textAccent flex flex-grow items-center gap-2">
                    <span>Last 90 days</span>
                    <i class="bx bx-chevron-down"></i>
                </a>
                <a href="#nothing" class="text-accentBold flex items-center gap-2">
                    <span>REPORT</span>
                    <i class="bx bx-chevron-right"></i>
                </a>
            </div>
        </div>

        <!-- Calendar container -->
        <div class="w-full h-[36rem] flex flex-col justify-self-end p-4 break-words bg-menu shadow-lg rounded-lg">
            <div data-toggle="calendar" id="calendar" class="overflow-hidden"></div>
        </div>
    </div>
    <?php
} else {
    echo "User not logged in";
}
?>