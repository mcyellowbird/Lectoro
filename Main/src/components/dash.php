<?php
require 'vendor/autoload.php';

function getDashboardData($userId) {
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

    $upcomingClasses = [];
    foreach ($subjects as $subject) {
        if (isset($subject['timetable']) && !empty($subject['timetable'])) {
            foreach ($subject['timetable'] as $lecture) {
                $lectureTime = new DateTime($lecture);
                $currentTime = new DateTime();
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
                            <?php if ($index > 0) { ?>,<?php } ?>
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
                    buttonText: {today: 'TODAY'},
                });
                calendar.render();

                // Donut Graph
                var donutSeries = [];
                var donutLabels = [];

                // Calculate average attendance for each subject
                
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
            <div class="flex flex-row">
                <div class="flex flex-wrap gap-16 w-fit h-fit">
                    <!-- Key Data dynamically populated by JavaScript -->
                    <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                        <span class="text-2xl font-bold" id="totalSubjects"><?php echo $data['totalSubjects'] ? $data['totalSubjects']: 'N/A'; ?></span>
                        <span class="text-lg">Total Subjects</span>
                    </div>
                    <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                        <span class="text-2xl font-bold" id="totalStudents"><?php echo $data['totalStudents'] ? $data['totalStudents']: 'N/A'; ?></span>
                        <span class="text-lg">Total Students</span>
                    </div>
                    <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                        <span class="text-2xl font-bold" id="averageAttendance">
                            <?php echo isset($data['averageAttendance']) ? number_format((float)$data['averageAttendance'], 2) . '%' : 'N/A'; ?>
                        </span>
                        <span class="text-lg">Avg Attendance</span>
                    </div>
                    <div class="bg-menu shadow-lg w-40 h-40 rounded-lg flex flex-col justify-center items-center">
                        <span class="text-2xl font-bold" id="nextClass"><?php echo $data['upcomingClasses'] ? $data['upcomingClasses'][0]['date'] : 'N/A'; ?></span>
                        <span class="text-lg">Next Lecture</span>
                    </div>
                </div>
                <!-- Table Container -->
                <?php
                    usort($data['upcomingClasses'], function($a, $b) {
                        return strtotime($a['date']) - strtotime($b['date']);
                    });
                    
                    // Define pagination variables
                    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
                    $records_per_page = 6;
                    $start_from = ($page - 1) * $records_per_page;
                    
                    // Fetch only the required subset of upcomingClasses based on pagination
                    $subset_upcomingClasses = array_slice($data['upcomingClasses'], $start_from, $records_per_page);
                    ?>

                    <div class="bg-menu p-4 w-full h-fit rounded-lg">
                        <div
                            class="text-sm font-medium text-center text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700">
                            <ul class="flex flex-wrap text-lg">
                                <li class="me-2">
                                    <a href="#"
                                        class="inline-block p-4 text-accent border-b-2 border-blue-600 rounded-t-lg active dark:text-accentDark dark:border-blue-500">Upcoming
                                        Lessons</a>
                                </li>
                                <li class="me-2">
                                    <a href="#"
                                        class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300"
                                        aria-current="page">Previous Lessons</a>
                                </li>
                            </ul>
                        </div>
                        <!-- Table as in your HTML with classes dynamically populated -->
                        <table class="mt-4 text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-colapse border-spacing-0">
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
                                Showing <span class="font-semibold text-accent"><?php echo $start_from + 1; ?></span> to
                                <span class="font-semibold text-accent"><?php echo min($start_from + $records_per_page, count($data['upcomingClasses'])); ?></span>
                                of <span class="font-semibold text-accent"><?php echo count($data['upcomingClasses']); ?></span> Entries
                            </span>
                            <!-- Buttons -->
                            <div class="inline-flex mt-2 xs:mt-0">
                                <a <?php echo $page != 1 ? 'href="?page=' . ($page - 1) . '"' : ''; ?> 
                                    class="flex items-center justify-center px-3 h-8 text-sm font-medium border-textAccent bg-menu border rounded-s-lg <?php echo $page == 1 ? 'cursor-not-allowed text-textAccent border-opacity-10' : 'text-textColour hover:bg-accent border-opacity-40'; ?>">
                                    Prev
                                </a>
                                <a href="?page=<?php echo $page + 1; ?>"
                                    class="flex items-center justify-center px-3 h-8 text-sm font-medium text-textColour bg-menu border border-s border-textAccent/40 rounded-e-lg hover:bg-accent <?php echo $start_from + $records_per_page >= count($data['upcomingClasses']) ? 'hidden' : ''; ?>">
                                    Next
                                </a>
                            </div>
                        </div>
                    </div>
            </div>
            <div class="flex flex-row gap-20 justify-between h-[600px] items-end">
                <div>
                    <!-- Bar Graph container -->
                    <div id="barGraphContainer" class="w-[380px] h-fit p-6 bg-menu shadow-lg rounded-lg">
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
                </div>
                <div>
                    <!-- Calendar container -->
                    <div class="w-[1500px] h-[600px] flex flex-col p-4 break-words bg-menu shadow-lg rounded-lg">
                        <div data-toggle="calendar" id="calendar" class="overflow-hidden"></div>
                    </div>
                </div>
            </div>
    <?php
} else {
    echo "User not logged in";
}
?>
