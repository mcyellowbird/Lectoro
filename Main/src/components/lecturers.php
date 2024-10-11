<?php
require 'vendor/autoload.php';

function getLecturers($userId)
{
    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
    $database = $mongoClient->selectDatabase("CSIT321Development");
    $lecturersCollection = $database->selectCollection("lecturers");
    $subjectsCollection = $database->selectCollection("subjects");
    $usersCollection = $database->selectCollection("users");

    // Fetch lecturers based on role
    $lecturersCursor = $lecturersCollection->find();

    $lecturers = [];

    foreach ($lecturersCursor as $lecturer) {
        // Get assigned subjects for each lecturer
        $assignedSubjectIds = $lecturer['assigned_subjects'] ?? [];

        // Retrieve the subject names for these assigned subject IDs
        $assignedSubjects = [];
        if (!empty($assignedSubjectIds)) {
            $subjects = $subjectsCollection->find(['subjectId' => ['$in' => $assignedSubjectIds]]);
            foreach ($subjects as $subject) {
                $assignedSubjects[] = $subject['subjectName'];
            }
        }

        // Get user details for the lecturer (first name, last name, username, etc.)
        $user = $usersCollection->findOne(['userId' => $lecturer['userId']]);

        // Prepare lecturer data
        $lecturers[] = [
            'userId' => $lecturer['userId'] ?? 'N/A',
            'first_name' => $user['first_name'] ?? 'N/A',
            'last_name' => $user['last_name'] ?? 'N/A',
            'email' => $user['email'] ?? 'N/A',
            'phone' => $user['phone'] ?? 'N/A',
            'username' => $user['username'] ?? 'N/A',
            'assignedSubjects' => implode(', ', $assignedSubjects) // Comma-separated list of assigned subject names
        ];
    }

    return [
        'lecturers' => $lecturers,
    ];
}

// Usage
$data = getLecturers($_SESSION['userId']);
$lecturers = $data['lecturers'];
?>
    <?php if (isset($_SESSION['userId'])): ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchBar = $('#searchBar');
                const loadingElement = $('#loading');
                const lecturersTable = $('table');
                let allLecturers = <?php echo json_encode($lecturers); ?>;

                function updatelecturersTable(users) {
                    lecturersTable.find('tr:gt(0)').remove(); // Remove all rows except the header

                    if (users.length === 0) {
                        lecturersTable.append('<tr><td colspan="6" class="text-center">No users found</td></tr>');
                        return;
                    }

                    users.slice(0, 10).forEach(user => { // Show a maximum of 10 users
                        const actionContainer = $(`<div class="absolute pr-2 right-0 [&:not(:hover)]:hidden group-hover:inline w-full h-full"></div>`);
                        const actions = $(`<div class="flex flex-row justify-center items-center float-end h-full"></div>`);
                        
                        // Add a report icon for all users
                        actions.append(`<a class="cursor-pointer"><i class="bx bxs-report text-accentDark hover:text-accent text-2xl"></i></a>`);
                        
                        actionContainer.append(actions);

                        // Create table row with user details
                        const row = $('<tr class="group relative"></tr>')
                            .append(`<td>${user.userId || 'N/A'}</td>`)
                            .append(`<td>${user.first_name || 'N/A'}</td>`)
                            .append(`<td>${user.last_name || 'N/A'}</td>`)
                            .append(`<td>${user.email || 'N/A'}</td>`)
                            .append(`<td>${user.phone || 'N/A'}</td>`)
                            .append(`<td>${user.username || 'N/A'}</td>`)
                            .append(`<td>${user.assignedSubjects ? user.assignedSubjects : 'N/A'}</td>`);

                        // Add the action container to the row
                        row.append(actionContainer);
                        
                        // Append the row to the table
                        lecturersTable.append(row);
                    });

                    loadingElement.hide(); // Hide loading spinner after users are updated
                }


                function number_format(number, decimals) {
                    return number.toFixed(decimals);
                }

                searchBar.on('input', function() {
                    const query = $(this).val().toLowerCase();
                    let filteredLecturers = allLecturers;

                    if (query.length > 0) {
                        filteredLecturers = allLecturers.filter(lec => {
                            // Ensure that the fields are defined before checking them
                            return (lec.username && lec.username.toLowerCase().includes(query)) ||
                                (lec.userId && lec.userId.toLowerCase().includes(query)) ||
                                (lec.email && lec.email.toLowerCase().includes(query)) ||
                                (lec.first_name && lec.first_name.toLowerCase().includes(query)) ||
                                (lec.last_name && lec.last_name.toLowerCase().includes(query)) ||
                                (lec.phone && lec.phone.toLowerCase().includes(query));
                        });
                    }

                    updatelecturersTable(filteredLecturers);
                });


                loadingElement.show(); // Show loading spinner initially

                updatelecturersTable(allLecturers); // Initial load

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
                    $("#addUserModal").removeClass("hidden");
                });

                // Close the modal
                $("#closeAddUserModal").click(function() {
                    $("#addUserModal").addClass("hidden");
                });

                // Handle form submission
                $("#addUserForm").submit(function(event) {
                    event.preventDefault(); // Prevent the default form submission

                    $.ajax({
                        url: 'http://localhost:8081/register', // Updated URL of your server-side script for adding a user
                        type: 'POST',
                        contentType: "application/json; charset=utf-8",
                        data: JSON.stringify({
                            first_name: $("#first_name").val(), // Get first_name input value
                            last_name: $("#last_name").val(),   // Get last_name input value
                            phone: $("#phone").val(),         // Get phone input value
                            password: $("#password").val(),   // Get password input value
                            role: "Lecturer"
                            
                        }),
                        success: function(response) {
                            // Handle the response from the server
                            $.ajax({
                                url: 'http://localhost:8081/lecturer/save', // Post lecturer
                                type: 'POST',
                                contentType: "application/json; charset=utf-8",
                                data: JSON.stringify({
                                    assigned_subjects: $('#subjects').val(),  // Get assigned_subjects (multiple values)
                                    userId: response.userId,
                                }),
                                success: function(response) {
                                    // Handle the response from the server
                                    $("#addUserModal").addClass("hidden"); // Close the modal upon success
                                },
                                error: function(xhr, status, error) {
                                    console.error("Error:", error);
                                }
                            })
                            $("#addUserModal").addClass("hidden"); // Close the modal upon success
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
            <div id="addUserModal" class="flex justify-center items-center z-50 fixed top-0 left-0 w-full h-full bg-black/40 hidden">
            <div class="relative bg-menu text-textColour rounded-lg shadow-lg max-w-3xl mx-auto mt-20 self-center justify-self-center">
                <!-- Modal header -->
                <div class="flex items-center justify-between p-4 md:p-5 border-b border-accentBold rounded-t">
                    <h3 class="text-lg font-semibold text-textColour">
                        Add New User
                    </h3>
                    <button type="button" id="closeAddUserModal" class="bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                        </svg>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <!-- Modal body -->
                <form id="addUserForm" class="self-center p-4 md:p-5">
                    <div class="grid gap-4 mb-4 grid-cols-2">
                        <!-- First Name -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="first_name" class="block mb-2 text-sm font-medium text-textColour">First Name</label>
                            <input type="text" name="first_name" id="first_name" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Enter first name" required>
                        </div>
                        <!-- Last Name -->
                        <div class="col-span-2 sm:col-span-1">
                            <label for="last_name" class="block mb-2 text-sm font-medium text-textColour">Last Name</label>
                            <input type="text" name="last_name" id="last_name" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Enter last name" required>
                        </div>
                        <!-- Phone -->
                        <div class="col-span-2">
                            <label for="phone" class="block mb-2 text-sm font-medium text-textColour">Phone</label>
                            <input type="text" name="phone" id="phone" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Enter phone number" required>
                        </div>
                        <!-- Password -->
                        <div class="col-span-2">
                            <label for="password" class="block mb-2 text-sm font-medium text-textColour">Password</label>
                            <input type="password" name="password" id="password" class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5" placeholder="Enter password" required>
                        </div>
                        <!-- Assigned Subjects -->
                        <div class="col-span-2">
                            <label for="subjects" class="block mb-2 text-sm font-medium text-textColour">Assigned Subjects</label>
                            <select id="subjects" multiple class="bg-buttonHover border border-gray-500 text-textColour placeholder-textAccent text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5">
                                <option value="subject1">Math</option>
                                <option value="subject2">Science</option>
                                <option value="subject3">History</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="text-white inline-flex items-center bg-accentBold focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <svg class="me-1 -ms-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Add User
                    </button>
                </form>
            </div>

            </div>
            <div class="flex flex-col items-center">
                <!-- Lecturers Table -->
                <span class="text-4xl text-center mb-8">Lecturers</span>
                <div class="flex flex-col bg-menu p-4 w-70p rounded-lg h-auto">
        <div class="flex">
            <div class="searchBar w-70p">
                <i class="searchIcon bx bx-search"></i>
                <input type="text" id="searchBar" placeholder="Search for lecturers..." class="searchInput">
            </div>
            <a href="#" id="addSubject" class="bg-accentDark ml-auto mr-4 transition ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Add User</a>
            <a href="#" id="generateReport" class="bg-accentDark transition ease-out duration-300 text-center self-center block px-4 py-2 text-md rounded-xl hover:bg-accentBold">Generate Report</a>
        </div>
        <table id="table" class="bg-menu text-left [&_tr]:border-b-2 [&_tr]:border-accent [&_tr:not(:first-of-type)]:border-opacity-10 [&_tr_th]:py-2 [&_tr_td:not(first-of-type)]:pl-4 [&_tr_td:last-of-type]:pr-3 [&_tr_th:not(first-of-type)]:pl-4 [&_tr_td]:pb-2 [&_tr_td]:pt-2 [&_tr_td:first-of-type]:pl-2 [&_tr_th:first-of-type]:pl-2 border-2 overflow-hidden w-full rounded-lg border-collapse border-spacing-0">
            <tr>
                <th>User ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Username</th>
                <th>Assigned Subjects</th>
            </tr>
            <!-- Rows will be dynamically inserted here -->
        </table>
    </div>

            </div>
        </div>
    <?php endif; ?>
