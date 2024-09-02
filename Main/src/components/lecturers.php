<?php
require 'vendor/autoload.php';

function getSubjectsAndLecturers($userId)
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturersCollection = $database->selectCollection("lecturers");
    $subjectCollection = $database->selectCollection("subjects");
    $usersCollection = $database->selectCollection("users");

    // Find user by user_id to get user_type
    $user = $usersCollection->findOne(['user_id' => $userId]);
    $userType = $user ? $user['user_type'] : 'Lecturer';
    
    if ($userType === 'Admin') {
        // For admin: Get all subjects
        $lecturerCursor = $lecturersCollection->find();
    }
    
    $lecturers = [];
    
    foreach ($lecturerCursor as $lecturer) {
        // Calculate average attendance
        $lectures = $lecturesCollection->find(['subject_id' => $lecturer['subject_id']]);
        $totalLectures = 0;
        $totalAttended = 0;
        $studentsCount = count($subject['students']) ?: 1; // Avoid division by zero

        foreach ($lectures as $lecture) {
            $totalLectures++;
            $totalAttended += count($lecture['attended_students']);
        }
        
        $averageAttendance = $totalLectures > 0 
            ? ($totalAttended / ($totalLectures * $studentsCount)) * 100 // Percentage
            : 0;

        // Get assigned lecturers
        $assignedLecturers = $lecturersCollection->find(['assigned_subjects' => $subject['subject_id']]);
        $lecturerIds = [];
        foreach ($assignedLecturers as $lecturer) {
            $lecturerIds[] = $lecturer['user_id']; // Changed from username to user_id
        }
        
        $subject['average_attendance'] = $averageAttendance;
        $subject['lecturers'] = implode(', ', $lecturerIds);
        $subjects[] = $subject;
    }

    return [
        'subjects' => $subjects,
        'user_type' => $userType
    ];
}

$data = getSubjectsAndLecturers($_SESSION['user_id']);
$subjects = $data['subjects'];
$userType = $data['user_type'];
?>
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchBar = $('#searchBar');
                const loadingElement = $('#loading');
                const subjectsTable = $('table');
                let allSubjects = <?php echo json_encode($subjects); ?>;

                function updateSubjectsTable(subjects) {
                    subjectsTable.find('tr:gt(0)').remove(); // Remove all rows except the header

                    if (subjects.length === 0) {
                        subjectsTable.append('<tr><td colspan="<?php echo $userType === 'Admin' ? '5' : '4'; ?>" class="text-center">No subjects found</td></tr>');
                        return;
                    }

                    subjects.slice(0, 10).forEach(subject => { // Show a maximum of 10 subjects
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
                            .append(`<td>${subject.subject_name}</td>`)
                            .append(`<td>${subject.subject_code}</td>`)
                            .append(`<td>${subject.students ? subject.students.length : 0}</td>`)
                            .append(`<td>${subject.average_attendance !== undefined ? number_format(subject.average_attendance, 2) : 'N/A'}</td>`);
                        
                        if ('<?php echo $userType; ?>' === 'Admin') {
                            row.append(`<td>${subject.lecturers || 'N/A'}</td>`);
                        }
                        
                        row.append(actionContainer);
                        subjectsTable.append(row);
                    });

                    loadingElement.hide(); // Hide loading spinner after subjects are updated
                }

                function number_format(number, decimals) {
                    return number.toFixed(decimals);
                }

                searchBar.on('input', function() {
                    const query = $(this).val().toLowerCase();
                    let filteredSubjects = allSubjects;

                    if (query.length > 0) {
                        filteredSubjects = allSubjects.filter(subject => 
                            subject.subject_name.toLowerCase().includes(query) ||
                            subject.subject_code.toLowerCase().includes(query)
                        );
                    }

                    updateSubjectsTable(filteredSubjects);
                });

                loadingElement.show(); // Show loading spinner initially

                updateSubjectsTable(allSubjects); // Initial load

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
                                ${table.outerHTML} <!-- Use outerHTML to include the table's outer tags -->
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
                
                $("#addSubject").click(function() {
                    $("#addSubjectModal").removeClass("hidden");
                });

                // Close the modal
                $("#closeAddSubjectModal").click(function() {
                    $("#addSubjectModal").addClass("hidden");
                });

                // Handle form submission
                $("#addSubjectForm").submit(function(event) {
                    event.preventDefault(); // Prevent the default form submission
                    
                    $.ajax({
                        url: './src/events/addSubject.php', // URL of your server-side script
                        type: 'POST',
                        data: {
                            subjectName: $("#subjectName").val(),
                            subjectCode: $("#subjectCode").val(),
                            lecturerIds: $('#lecturer').val(),
                            studentIds: $("#students").val()
                        },
                        success: function(response) {
                            // Handle the response from the server
                            $("#addSubjectModal").addClass("hidden");
                        },
                        error: function(xhr, status, error) {
                            console.error("Error:", error);
                        }
                    });
                });


                // Form
                $.ajax({
                    url: './src/events/getLecturers.php', // Endpoint to get lecturers
                    method: 'GET',
                    success: function(data) {
                        try {
                            let lecturers = JSON.parse(data);
                            if (lecturers.error) {
                                console.error(lecturers.error);
                                alert('Error fetching lecturers: ' + lecturers.error);
                                return;
                            }
                            let lecturerSelect = $('#lecturer');
                            lecturerSelect.empty();
                            lecturers.forEach(function(lecturer) {
                                lecturerSelect.append(`<option value="${lecturer.id}">${lecturer.name}</option>`);
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
                    url: './src/events/getStudents.php', // Endpoint to get students
                    method: 'GET',
                    success: function(data) {
                        let students = JSON.parse(data);
                        let studentElement = $('#students');
                        studentElement.find('option').remove();
                        students.forEach(student => {
                            studentElement.append(`<option value="${student.id}">${student.name} - ${student.id}</option>`);
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
            <div id="addSubjectModal" class="flex justify-center items-center z-50 fixed top-0 left-0 w-full h-full bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-black to-transparent hidden">
                <div class="relative bg-menu text-textColour rounded-lg shadow-lg max-w-3xl mx-auto mt-20 self-center justify-self-center">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b border-accentBold rounded-t">
                        <h3 class="text-lg font-semibold text-textColour">
                            Create New Subject
                        </h3>
                        <button type="button" id="closeAddSubjectModal" class="bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" id="closeAddSubjectModal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <form id="addSubjectForm" class="self-center p-4 md:p-5">
                        <div class="grid gap-4 mb-4 grid-cols-2">
                            <div class="col-span-2">
                                <div class="grid grid-cols-2 gap-x-4">
                                    <label for="subjectName" class="mb-2 text-sm font-medium text-textColour dark:text-white">Subject Name</label>
                                    <label for="subjectCode" class="mb-2 text-sm font-medium text-textColour dark:text-white">Subject Code</label>
                                    <input type="text" name="subjectName" id="subjectName" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type subject name" required>
                                    <input type="text" name="subjectCode" id="subjectCode" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Type subject code" required>
                                </div>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="lecturer" class="block mb-2 text-sm font-medium text-textColour">Assigned Lecturer(s)</label>
                                <select id="lecturer" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option value="DD">Dr. Fenghui Ren +2</option>
                                    <option value="TV">Dr. Partha Roy</option>
                                    <option value="PC">Dr. John Lee</option>
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-1">
                                <label for="term" class="block mb-2 text-sm font-medium text-textColour">Available</label>
                                <select id="term" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                    <option selected>Spring</option>
                                    <option>Autumn</option>
                                    <option>Summer</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label for="fileUpload" class="items-center flex mb-2 text-sm font-medium text-textColour">Student List<i class="pl-1 bx bx-info-circle"></i></label>
                                <div class="flex items-center justify-center w-full">
                                    <!-- <label for="fileUpload" class="flex flex-col items-center justify-center w-full h-20 border-2 border-gray-500 border-dashed rounded-lg cursor-pointer bg-menu">
                                        <div class="flex flex-col items-center justify-center ">
                                            <p class="mb-2 text-sm text-textAccent dark:text-gray-400"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                            <p class="text-xs text-textAccent dark:text-gray-400">TXT, CSV, JSON, JSN</p>
                                        </div>
                                        <input id="fileUpload" type="file" class="hidden" accept=".json">
                                    </label> -->
                                    <select id="students" multiple class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                                        <option value="DD">Student</option>
                                        <option value="TV">Dr. Partha Roy</option>
                                        <option value="PC">Dr. John Lee</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="text-white inline-flex items-center bg-accentBold focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Create Subject
                        </button>
                    </form>
                </div>
            </div>
            <div class="flex flex-col items-center">
                <!-- Subjects Table -->
                <span class="text-4xl text-center mb-8">Lecturers</span>
                <div class="flex flex-col bg-menu p-4 w-70p rounded-lg h-auto">
                    <div class="flex">
                        <div class="searchBar w-70p">
                            <i class="searchIcon bx bx-search"></i>
                            <input type="text" id="searchBar" placeholder="Search for subjects..." class="searchInput">
                        </div>
                        <?php if ($userType === 'Admin'): ?>
                            <a href="#" id="addSubject" class="bg-accentDark ml-auto mr-4 transition ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Add Subject</a>
                        <?php endif; ?>
                        <a href="#" id="generateReport" class="bg-accentDark transition <?php echo $userType !== 'Admin' ? 'ml-auto' : ''; ?> ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Generate Report</a>
                    </div>
                    <table id="table" class="bg-menu text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_td:first-of-type]:pl-2 [&_tr_th:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-collapse border-spacing-0">
                        <tr>
                            <th>Lecturer Name</th>
                            <th>Lecturer ID</th>
                            <th>Subjects</th>
                            <th>Average Attendance (%)</th>
                        </tr>
                        <!-- Rows will be dynamically inserted here -->
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
