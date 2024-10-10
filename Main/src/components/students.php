<?php
require 'vendor/autoload.php';

function getStudentsAndSubjects($userId)
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturersCollection = $database->selectCollection("lecturers");
    $subjectsCollection = $database->selectCollection("subjects");
    $lecturesCollection = $database->selectCollection("lecture");
    $studentsCollection = $database->selectCollection("students");
    $usersCollection = $database->selectCollection("users");

    // Find user by userId to get user_type
    $user = $usersCollection->findOne(['userId' => $userId]);
    $userType = $user ? $user['role'] : 'Lecturer';
    
    if ($userType === 'Admin') {
        // For admin: Get all students
        $subjectsCursor = $subjectsCollection->find();
    } else {
        // For lecturers: Get only assigned subjects
        $lecturer = $lecturersCollection->findOne(['userId' => $userId]);
        $assignedSubjectIds = $lecturer ? $lecturer['assigned_subjects'] : [];
        $subjectsCursor = $subjectsCollection->find(['subjectId' => ['$in' => $assignedSubjectIds]]);
    }
    
    $studentsData = [];

    foreach ($subjectsCursor as $subject) {
        foreach ($subject['students'] as $studentId) {
            // Get student details
            $student = $studentsCollection->findOne(['studentId' => $studentId]);
            if ($student) {
                // Initialize student data if not already present
                if (!isset($studentsData[$studentId])) {
                    $studentsData[$studentId] = [
                        'studentId' => $student['studentId'],
                        'firstName' => $student['firstName'],
                        'lastName' => $student['lastName'],
                        'imageURL' => $student['imageURL'],
                        'email' => $student['email'],
                        'phone' => $student['phone'],
                        'subjects' => [],
                        'total_lectures' => 0,
                        'total_attended' => 0,
                    ];
                }

                // Add subject to student's list of enrolled subjects
                $studentsData[$studentId]['subjects'][] = $subject['subjectId'];

                // Calculate attendance
                $lectures = $lecturesCollection->find(['subjectId' => $subject['subjectId']]);
                foreach ($lectures as $lecture) {
                    $studentsData[$studentId]['total_lectures']++;
                
                    // Convert BSON array to PHP array
                    $attendedStudents = $lecture['attended_students']->getArrayCopy();
                
                    if (in_array($studentId, $attendedStudents)) {
                        $studentsData[$studentId]['total_attended']++;
                    }
                }
            }
        }
    }

    // Calculate average attendance for each student
    foreach ($studentsData as &$studentData) {
        $totalLectures = $studentData['total_lectures'];
        $totalAttended = $studentData['total_attended'];
        $studentData['average_attendance'] = $totalLectures > 0 ? ($totalAttended / $totalLectures) * 100 : 0;
    }

    return [
        'students' => array_values($studentsData), // Convert associative array to indexed array
        'role' => $userType
    ];
}

$data = getStudentsAndSubjects($_SESSION['userId']);
$students = $data['students'];
$userType = $data['role'];
?>
<?php if (isset($_SESSION['userId'])): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchBar = $('#searchBar');
                const loadingElement = $('#loading');
                const studentsTable = $('table');
                let allStudents = <?php echo json_encode($students); ?>;
                console.log(allStudents);

                function updatestudentsTable(students) {
                    studentsTable.find('tr:gt(0)').remove(); // Remove all rows except the header

                    if (students.length === 0) {
                        studentsTable.append('<tr><td colspan="<?php echo $userType === 'Admin' ? '5' : '4'; ?>" class="text-center">No subjects found</td></tr>');
                        return;
                    }

                    students.slice(0, 10).forEach(student => { // Show a maximum of 10 students
                        const actionContainer = $(`<div class="absolute pr-2 right-0 [&:not(:hover)]:hidden group-hover:inline w-full h-full"></div>`);
                        const actions = $(`<div class="flex flex-row justify-center items-center float-end h-full"></div>`);
                        
                        if ('<?php echo $userType; ?>' === 'Admin') {
                            actions.append(`<a class="cursor-pointer"><i class="bx bxs-edit-alt text-accentDark hover:text-accent text-2xl"></i></a>`);
                        }
                        if ('<?php echo $userType; ?>' != 'Admin') {
                            actions.append(`<a class="cursor-pointer"><i class="bx bxs-bullseye text-accentDark hover:text-accent text-2xl"></i></a>`);
                        }

                        actions.append(`<a class="cursor-pointer"><i class="bx bxs-report text-accentDark hover:text-accent text-2xl"></i></a>`);
                        actionContainer.append(actions);

                        const row = $('<tr class="group relative"></tr>')
                            .append(`<td>${student.firstName} ${student.lastName}</td>`)
                            .append(`<td>${student.studentId}</td>`)
                            .append(`<td>${student.email}</td>`)
                            .append(`<td>${student.subjects}</td>`)
                            .append(`<td>${student.average_attendance !== undefined ? number_format(student.average_attendance, 2) : 'N/A'}%</td>`);
                        
                        row.append(actionContainer);
                        studentsTable.append(row);
                    });

                    loadingElement.hide(); // Hide loading spinner after students are updated
                }

                function number_format(number, decimals) {
                    return number.toFixed(decimals);
                }

                searchBar.on('input', function() {
                    const query = $(this).val().toLowerCase();
                    let filteredStudents = allStudents;

                    if (query.length > 0) {
                        filteredStudents = allStudents.filter(student => 
                            student.first_name.toLowerCase().includes(query) ||
                            student.last_name.toLowerCase().includes(query) ||
                            student.student_id.toLowerCase().includes(query)
                        );
                    }

                    updatestudentsTable(filteredStudents);
                });

                loadingElement.show(); // Show loading spinner initially

                updatestudentsTable(allStudents); // Initial load

                $("#generateReport").click(function() {
                    var table = $("#table").get(0); // Get the table element

                    // External CSS link
                    var cssLink = '<link rel="stylesheet" href="./dist/output.css">';

                    var htmlContent = `
                        <html>
                            <head>
                                <title>Report</title>
                                ${cssLink}
                            </head>
                            <body>
                                ${table.outerHTML}
                            </body>
                        </html>
                    `;

                    var opt = {
                        margin:       .5,
                        filename:     'report.pdf',
                        image:        { type: 'jpeg', quality: 1 },
                        html2canvas:  { dpi: 192, scale: 3, letterRendering: true },
                        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                    };

                    // Generate the PDF
                    html2pdf().set(opt).from(htmlContent).output('bloburl').then(function(bloburl) {
                        window.open(bloburl, '_blank'); // Open the PDF in a new tab
                    });
                });
                
                $("#addStudent").click(function() {
                    $("#addStudentModal").removeClass("hidden");
                });

                // Close the modal
                $("#closeAddStudentModal").click(function() {
                    $("#addStudentModal").addClass("hidden");
                });

                // Handle form submission
                $("#addStudentForm").submit(function(event) {
                    event.preventDefault(); // Prevent the default form submission
                    
                    $.ajax({
                        url: 'http://localhost:8081/student/save', // URL of your server-side script
                        type: 'POST',
                        data: JSON.stringify({
                            firstName: $("#firstName").val(),
                            lastName: $("#lastName").val(),
                            studentId: $('#studentId').val(),
                            email: $("#email").val(),
                            phone: $("#phone").val(),
                            subjects: $('#subjects').val(),
                            imageURL: " "
                        }),
                        contentType: "application/json; charset=utf-8",
                        success: function(response) {
                            console.log(response);
                            // Handle the response from the server
                            $("#addStudentModal").addClass("hidden");
                        },
                        error: function(xhr, status, error) {
                            console.error("Error:", error);
                        }
                    });
                });


                // Form
                $.ajax({
                    url: 'http://localhost:8081/users/all/Lecturer', // Endpoint to get lecturers
                    method: 'GET',
                    success: function(data) {
                        try {
                            let lecturers = data;
                            if (lecturers.error) {
                                console.error(lecturers.error);
                                alert('Error fetching lecturers: ' + lecturers.error);
                                return;
                            }
                            let lecturerSelect = $('#lecturer');
                            lecturerSelect.empty();
                            lecturers.forEach(function(lecturer) {
                                lecturerSelect.append(`<option value="${lecturer.userId}">${lecturer.fullName}</option>`);
                            });
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error:', textStatus, errorThrown);
                    }
                });

                $.ajax({
                    url: 'http://localhost:8081/student/allNames', // Endpoint to get students
                    method: 'GET',
                    success: function(data) {
                        let students = data;
                        let studentElement = $('#students');
                        studentElement.find('option').remove();
                        students.forEach(student => {
                            studentElement.append(`<option value="${student.studentId}">${student.fullName} - ${student.studentId}</option>`);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });

                $.ajax({
                    url: 'http://localhost:8081/subject/all', // Endpoint to get subjects
                    method: 'GET',
                    success: function(data) {
                        let subjects = data;
                        let subjectElement = $('#subjects');
                        subjectElement.find('option').remove();
                        subjects.forEach(subject => {
                            subjectElement.append(`<option value="${subject.subjectId}">${subject.subjectName} - ${subject.subjectId}</option>`);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error);
                    }
                });

                $("#fileUpload").change(function(event) {
                    var file = event.target.files[0];
                    if (file && file.type === "application/json") {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var contents = e.target.result;
                            try {
                                var json = JSON.parse(contents);
                                // Process the JSON data here
                            } catch (error) {
                                console.error("Invalid JSON file");
                            }
                        };
                        reader.readAsText(file);
                    } else {
                        console.error("Please upload a valid JSON file");
                    }
                });

            });
        </script>
        <div id="loading" role="status" class="absolute flex left-50p top-50p">
            <svg aria-hidden="true" class="w-14 h-14 animate-spin text-textColour fill-accent" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"></path>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"></path>
            </svg>
            <span class="sr-only">Loading...</span>
        </div>

        <div id="content">
            <div id="addStudentModal" class="flex justify-center items-center z-50 fixed top-0 left-0 w-full h-full bg-black/40 hidden">
                <div class="relative bg-menu text-textColour rounded-lg shadow-lg max-w-3xl mx-auto mt-20 self-center justify-self-center">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b border-accentBold rounded-t">
                        <h3 class="text-lg font-semibold text-textColour">
                            Create New Student
                        </h3>
                        <button type="button" id="closeAddStudentModal" class="bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" id="closeAddStudentModal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <form id="addStudentForm" class="self-center p-4 md:p-5">
                        <div class="grid gap-4 mb-4 grid-cols-2">
                            <!-- First Name and Last Name -->
                            <div class="col-span-2 sm:col-span-1">
                                <label for="firstName" class="mb-2 text-sm font-medium text-textColour">First Name</label>
                                <input type="text" name="firstName" id="firstName" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type first name" required>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="lastName" class="mb-2 text-sm font-medium text-textColour">Last Name</label>
                                <input type="text" name="lastName" id="lastName" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type last name" required>
                            </div>

                            <!-- Student ID -->
                            <div class="col-span-2">
                                <label for="studentId" class="mb-2 text-sm font-medium text-textColour">Student ID</label>
                                <input type="text" name="studentId" id="studentId" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type student ID" required>
                            </div>

                            <!-- Email and Phone -->
                            <div class="col-span-2 sm:col-span-1">
                                <label for="email" class="mb-2 text-sm font-medium text-textColour">Email</label>
                                <input type="email" name="email" id="email" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type email" required>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="phone" class="mb-2 text-sm font-medium text-textColour">Phone</label>
                                <input type="tel" name="phone" id="phone" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type phone number" required>
                            </div>

                            <!-- Select List of Subjects -->
                            <div class="col-span-2">
                                <label for="subjects" class="block mb-2 text-sm font-medium text-textColour">Select List of Subjects</label>
                                <select id="subjects" name="subjects[]" multiple class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="text-white inline-flex items-center bg-accentBold focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Create Student
                        </button>
                    </form>
                </div>
            </div>
            <div class="flex flex-col items-center">
                <!-- Students Table -->
                <span class="text-4xl text-center mb-8">Students</span>
                <div class="flex flex-col bg-menu p-4 w-70p rounded-lg h-auto">
                    <div class="flex">
                        <div class="searchBar w-70p">
                            <i class="searchIcon bx bx-search"></i>
                            <input type="text" id="searchBar" placeholder="Search for students..." class="searchInput">
                        </div>
                        <?php if ($userType === 'Admin'): ?>
                            <a href="#" id="addStudent" class="bg-accentDark ml-auto mr-4 transition ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Add Student</a>
                        <?php endif; ?>
                        <a href="#" id="generateReport" class="bg-accentDark transition <?php echo $userType !== 'Admin' ? 'ml-auto' : ''; ?> ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Generate Report</a>
                    </div>
                    <table id="table" class="bg-menu text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_td:first-of-type]:pl-2 [&_tr_th:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-collapse border-spacing-0">
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Student Email</th>
                            <th>Enrolled Subjects</th>
                            <th>Average Attendance (%)</th>
                        </tr>
                        <!-- Rows will be dynamically inserted here -->
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>