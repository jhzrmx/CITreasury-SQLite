<?php
session_start();
include '../connection.php';
include '../helperfunctions.php';
include '../components/menu.php';
include '../components/nav.php';
verifyAdminLoggedIn($conn);

// API Endpoint
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    $postData = file_get_contents("php://input");
    $data = json_decode($postData, true);
    $response = [];
    // Initial response
    $response["status"] = "error";
    $response["message"] = "Unknown";
    $response["details"] = "";

    if ($_GET['api'] == 'add') {
        if (isset($data['event_name'], $data['event_desc'], $data['event_target'], $data['event_date'], $data['fee_per_event'], $data['sanction_fee'])) {
            $eventname = ucwords($data['event_name']);
            $eventdesc = trim($data['event_desc']);
            $eventdate = $data['event_date'];
            $eventtarget = isset($data['event_target']) ? implode(',', $data['event_target']) : '';
            $feeperevent = $data['fee_per_event'];
            $sanctionfee = $data['sanction_fee'];
            try {
                $sql_event = "INSERT INTO `events`(`event_name`, `event_description`, `event_target`, `event_date`, `event_fee`, `sanction_fee`) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_event = $conn->prepare($sql_event);
                if ($stmt_event->execute([$eventname, $eventdesc, $eventtarget, $eventdate, $feeperevent, $sanctionfee])) {
                    $response["status"] = "success";
                    $response["message"] = "Event added successfully!";
                } else {
                    $response["message"] = "Failed to add event!";
                }
            } catch (Exception $e) {
                $response["message"] = "Database error";
                $response["details"] = $e->getMessage();
            }
        } else {
            $response["message"] = "Invalid data";
        }
    } elseif ($_GET['api'] == 'edit') {
        if (isset($data['edit_event_id'], $data['edit_event_name'], $data['edit_event_desc'], $data['edit_event_target'], $data['edit_event_date'], $data['edit_fee_per_event'], $data['edit_sanction_fee'])) {
            $eid = trim($data['edit_event_id']);
            $eventname = ucwords(trim($data['edit_event_name']));
            $eventdesc = trim($data['edit_event_desc']);
            $eventdate = trim($data['edit_event_date']);
            $feeperevent = floatval($data['edit_fee_per_event']);
            $sanctionfee = floatval($data['edit_sanction_fee']);
            $eventtarget = (isset($data['edit_event_target']) && !empty($data['edit_event_target'])) ? implode(',', $data['edit_event_target']) : '';
            try {
                $sqlupdate_event = "UPDATE `events` SET `event_name` = ?, `event_description` = ?, `event_target` = ?, `event_date` = ?, `event_fee` = ?, `sanction_fee` = ? WHERE `event_id` = ?";
                $stmt_update_event = $conn->prepare($sqlupdate_event);
                if ($stmt_update_event->execute([$eventname, $eventdesc, $eventtarget, $eventdate, $feeperevent, $sanctionfee, $eid])) {
                    $response["status"] = "success";
                    $response["message"] = "Event updated successfully";
                } else {
                    $response["message"] = "Failed to update event!";
                }
            } catch (Exception $e) {
                $response["message"] = "Database error";
                $response["details"] = $e->getMessage();
            }
        } else {
            $response["message"] = "Invalid data";
        }
    } elseif ($_GET['api'] == 'delete') {
        if (isset($data['event_id'])) {
            try {
                $sql = "DELETE FROM `events` WHERE `event_id`= ?";
                $stmt = $conn->prepare($sql);
                if ($stmt->execute([$data['event_id']])) {
                    $response["status"] = "success";
                    $response["message"] = "Student successfully deleted";
                } else {
                    $response["message"] = "Student deletion failed";
                }
            } catch (Exception $e) {
                $response["message"] = "Database error";
                $response["details"] = $e->getMessage();
            }
        } else {
            $response["message"] = "Invalid data";
        }
    } else {
        $response["message"] = "Unknown get method";
    }
    echo json_encode($response);
    exit();
}

// Default View
$html = new HTML("CITreasury - Events");
$html->addLink('stylesheet', '../inter-variable.css');
$html->addLink('icon', '../img/nobgcitsclogo.png');
$html->addScript("../js/tailwind3.4.15.js");
$html->addScript("../js/tailwind.config.js");
$html->addScript("../js/sweetalert.min.js");
$html->addScript("../js/jquery-3.7.1.min.js");
$html->addScript("../js/predefined-script.js");
$html->addScript("../js/defer-script.js", true);
$html->addScript("eventslist.js", true);
$html->startBody();
?>
    <!-- Top Navigation Bar -->
    <?php nav(); ?>
    <!-- Body -->
    <div class="flex flex-col md:flex-row bg-custom-purplo min-h-screen">
        <!-- Side Bar Menu Items -->
        <div class="mt-18 md:mt-20 mx-2">
            <div id="menu-items" class="hidden md:inline-block w-60 h-full">
                <?php menuContent(); ?>
            </div>
        </div>
        <!-- Harmonica Menu Items for mobile, hidden in medium to larger screens -->
        <div id="menu-items-mobile" class="fixed block md:hidden h-fit top-16 w-full p-4 bg-custom-purplo opacity-95">
            <?php menuContent(); ?>
        </div>
        <div class="w-full bg-red-50 px-6 min-h-screen">
            <div class="fixed bottom-10 right-6">
                <button id="add-event" class="focus:outline-none" title="Add New Event">
                    <svg id="mdi-plus-circle" class="w-16 h-16 fill-green-500 bg-white hover:fill-green-600 rounded-full shadow-md shadow-gray-500" viewBox="2 2 20 20"><path d="M17,13H13V17H11V13H7V11H11V7H13V11H17M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" /></svg>
                </button>
            </div>
            <div class="mt-24 flex flex-col lg:flex-row justify-between">
                <h1 class="text-3xl text-custom-purplo font-bold mb-3">Manage Events</h1>
                <!-- Search Bar -->
                <div class="flex flex-row w-56 p-1 mb-3 border-2 border-custom-purple  focus:border-custom-purplo rounded-lg bg-white">
                    <svg id="mdi-calendar-search" class="h-6 w-6 mr-1 fill-custom-purple" viewBox="0 0 24 24"><path d="M15.5,12C18,12 20,14 20,16.5C20,17.38 19.75,18.21 19.31,18.9L22.39,22L21,23.39L17.88,20.32C17.19,20.75 16.37,21 15.5,21C13,21 11,19 11,16.5C11,14 13,12 15.5,12M15.5,14A2.5,2.5 0 0,0 13,16.5A2.5,2.5 0 0,0 15.5,19A2.5,2.5 0 0,0 18,16.5A2.5,2.5 0 0,0 15.5,14M19,8H5V19H9.5C9.81,19.75 10.26,20.42 10.81,21H5C3.89,21 3,20.1 3,19V5C3,3.89 3.89,3 5,3H6V1H8V3H16V1H18V3H19A2,2 0 0,1 21,5V13.03C20.5,12.22 19.8,11.54 19,11V8Z" /></svg>
                    <form method="GET">
                        <input type="text" id="event-search" name="search" placeholder="Search event..." class="w-full focus:outline-none">
                  </form>
                </div>
            </div>
            <script>
                const deleteIds = []; // Declare array to store event-id
            </script>
            <div class="mt-1 mb-5 overflow-x-auto rounded-lg shadow-lg">
                <div class="overflow-x-auto rounded-lg border border-black">
                    <!-- Table of Events -->
                    <table class="w-full px-1 text-center">
                        <?php
                        // Pagination variables
                        $results_per_page = 10;
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        $offset = ($page - 1) * $results_per_page;
                        // SQL query for displaying all events
                        $sql = "SELECT * FROM `events`";
                        if (isset($_GET['search'])) {
                            $search = '%' . $_GET['search'] . '%';
                            $sql .= " WHERE (`event_id` LIKE ? OR `event_name` LIKE ? OR `event_description` LIKE ? OR `event_date` LIKE ?)";
                            ?>
                            <script>
                                $("#event-search").val("<?php echo htmlspecialchars($_GET['search']); ?>");
                            </script>
                            <?php
                        }
                        // Add limit and offset for pagination
                        $sql .= " LIMIT ? OFFSET ?";
                        try {
                            $stmt = $conn->prepare($sql);
                            if (isset($search)) {
                                $stmt->bindParam(1, $search, PDO::PARAM_STR);
                                $stmt->bindParam(2, $search, PDO::PARAM_STR);
                                $stmt->bindParam(3, $search, PDO::PARAM_STR);
                                $stmt->bindParam(4, $search, PDO::PARAM_STR);
                                $stmt->bindParam(5, $results_per_page, PDO::PARAM_INT);
                                $stmt->bindParam(6, $offset, PDO::PARAM_INT);
                            } else {
                                $stmt->bindParam(1, $results_per_page, PDO::PARAM_INT);
                                $stmt->bindParam(2, $offset, PDO::PARAM_INT);
                            }
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (count($result) > 0) {
                                ?>
                                <thead class="text-white uppercase bg-custom-purplo ">
                                    <tr>
                                        <th scope="col" class="p-2 border-r border-black">Event ID</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Name</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Description</th>
                                        <th scope="col" class="p-2 border-r border-black">Target Year</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Date</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Fee (₱)</th>
                                        <th scope="col" class="p-2 border-r border-black">Sanction Fee (₱)</th>
                                        <th scope="col" class="p-2">Actions</th>
                                    </tr>
                                </thead>
                                <?php
                                // Loop through the results
                                foreach ($result as $row) {
                                    $eid = $row['event_id'];
                                    $eventname = $row['event_name'];
                                    $eventdesc = $row['event_description'];
                                    $eventtarget = $row['event_target'];
                                    $eventdate = $row['event_date'];
                                    $feeperevent = $row['event_fee'];
                                    $sanctionfee = $row['sanction_fee'];
                                    ?>
                                    <tr class="border-t border-black">
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eid; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventname; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventdesc; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventtarget; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventdate; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $feeperevent; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $sanctionfee; ?></td>
                                        <td class="px-1 bg-purple-100">
                                            <a href="eventsregistration.php?event-id=<?php echo $eid ?>"><button class="px-3 py-2 my-1 mx-1 bg-blue-500 text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-blue-400">
                                                <svg id="mdi-eye" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z" /></svg>
                                            </button></a>
                                            <button class="px-3 py-2 my-1 mx-1 bg-yellow-500 text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-yellow-400" onclick="editRow(this)">
                                                <svg id="mdi-pencil" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z" /></svg>
                                            </button>
                                            <button name="<?php echo htmlspecialchars($eid); ?>" class="px-3 py-2 m-1 bg-red-600 text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-red-500 delete-event">
                                                <svg id="mdi-delete" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <script>
                                        deleteIds.push("<?php echo $eid; ?>"); // Store event-ids in array for each query is executed
                                    </script>
                                    <?php
                                }
                            } else {
                                ?><h3 class="p-4">No events found.</h3><?php
                            }
                        } catch (PDOException $e) {
                            $result = [];
                            ?><h3 class="p-4">Page not found.</h3><?php
                        }
                        ?>
                    </table>
                </div>
            </div>
            <!-- Pagination controls -->
            <div class="pagination my-2">
                <?php
                # Get the total number of rows for pagination
                $sql_total = "SELECT COUNT(*) FROM `events`";
                if (isset($_GET['search'])) {
                    $sql_total .= " WHERE (`event_id` LIKE ? OR `event_name` LIKE ? OR `event_description` LIKE ? OR `event_date` LIKE ?)";
                    $stmt_total = $conn->prepare($sql_total);
                    $stmt_total->execute([$search, $search, $search, $search]);
                } else {
                    $stmt_total = $conn->prepare($sql_total);
                    $stmt_total->execute();
                }
                $total_records = $stmt_total->fetchColumn();
                $stmt_total = null;
                if (count($result) > 0) {
                    ?>
                     <div id="has-result" class="w-full mb-2">
                        <p>Showing <?php echo $results_per_page; ?> entries per page</p>
                        <p>Results: <?php echo $total_records; ?> row(s)</p>
                    </div>
                    <?php
                    # Calculate total pages
                    $total_pages = ceil($total_records / $results_per_page);
                    # Display pagination buttons
                    for ($i = 1; $i <= $total_pages; $i++) {
                        ?><a href='eventslist.php?page=<?php echo $i; ?>'><button class="px-3 py-2 my-1 mr-1 <?php echo $page == $i ? 'bg-purple-600' : 'bg-custom-purplo'; ?> text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-custom-purple"><?php echo $i; ?></button></a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
    <!-- Darken Background for Modal, hidden by default -->
    <div id="popup-bg" class="fixed top-0 w-full min-h-screen bg-black opacity-50 hidden"></div>
    <!-- Popup Modal for Adding Events, hidden by default -->
    <div id="popup-item" class="fixed top-0 w-full min-h-screen hidden">
        <div class="w-full min-h-screen flex items-center justify-center">
            <div class="m-5 w-full py-3 px-5 sm:w-1/2 lg:w-1/3 xl:1/4 rounded bg-white h-fit shadow-lg shadow-black">
                <div class="w-full flex justify-end">
                    <button class="focus:outline-none" id="close-popup">
                        <svg id="mdi-close-box-outline" class="mt-2 w-6 h-6 hover:fill-red-500" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M17,8.4L13.4,12L17,15.6L15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4Z" /></svg>
                    </button>
                </div>
                <h3 class="text-2xl font-semibold text-custom-purple mb-2">Add Event</h3>
                <form id="add-event-form">
                    <label class="ml-1 text-sm">Event Name:</label>
                    <input type="text" name="event-name" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required>
                    <label class="ml-1 text-sm">Event Description:</label>
                    <textarea name="event-desc" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required></textarea>
                    <label class="ml-1 text-sm">Target Year Levels:</label>
                    <div class="mb-2 flex flex-row justify-between bg-purple-100 border-2 border-custom-purple rounded-lg px-4 py-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="event-target[]" value="1" class="mr-2" checked> 1st year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="event-target[]" value="2" class="mr-2" checked> 2nd year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="event-target[]" value="3" class="mr-2" checked> 3rd year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="event-target[]" value="4" class="mr-2" checked> 4th year
                        </label>
                    </div>
                    <label class="ml-1 text-sm">Event Date:</label>
                    <input type="date" id="event-date" name="event-date" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required>
                    <label class="ml-1 text-sm">Event Fee (₱):</label>
                    <input type="number" id="fee-per-event" name="fee-per-event" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" min="0" required>
                    <label class="ml-1 text-sm">Sanction Fee (₱):</label>
                    <input type="number" id="sanction-fee" name="sanction-fee" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" min="0" required>
                    <div class="flex items-center justify-center m-2">
                        <button type="submit" class="px-3 py-2 bg-custom-purple rounded-lg focus:outline-none focus:border-purple-500 text-base text-white font-bold hover:bg-custom-purplo" name="add-new-event">Add Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Popup Modal for Editing Events, hidden by default -->
    <div id="edit-popup-item" class="fixed top-0 w-full min-h-screen hidden">
        <div class="w-full min-h-screen flex items-center justify-center">
            <div class="m-5 w-full py-3 px-5 sm:w-1/2 lg:w-1/3 xl:1/4 rounded bg-white h-fit shadow-lg shadow-black">
                <div class="w-full flex justify-end">
                    <button class="focus:outline-none" id="edit-close-popup">
                        <svg id="mdi-close-box-outline" class="mt-2 w-6 h-6 hover:fill-red-500" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M17,8.4L13.4,12L17,15.6L15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4Z" /></svg>
                    </button>
                </div>
                <h3 class="text-2xl font-semibold text-custom-purple mb-2">Edit Event</h3>
                <form id="edit-event-form">
                    <!--label class="ml-1 text-sm">Event ID:</label-->
                    <input type="hidden" id="edit-event-id" name="edit-event-id">
                    <label class="ml-1 text-sm">Event Name:</label>
                    <input type="text" id="edit-event-name" name="edit-event-name" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required>
                    <label class="ml-1 text-sm">Event Description:</label>
                    <textarea id="edit-event-desc" name="edit-event-desc" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required></textarea>
                    <label class="ml-1 text-sm">Target Year Levels:</label>
                    <div class="mb-2 flex flex-row justify-between bg-purple-100 border-2 border-custom-purple rounded-lg px-4 py-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="edit-event-target[]" value="1" class="mr-2"> 1st year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="edit-event-target[]" value="2" class="mr-2"> 2nd year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="edit-event-target[]" value="3" class="mr-2"> 3rd year
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="edit-event-target[]" value="4" class="mr-2"> 4th year
                        </label>
                    </div>
                    <label class="ml-1 text-sm">Event Date:</label>
                    <input type="date" id="edit-event-date" name="edit-event-date" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 hover:cursor-pointer bg-purple-100" required>
                    <div id="tooltip-content-date" class="absolute whitespace-normal break-words rounded-lg bg-red-500 py-1.5 px-3 font-sans text-xs font-normal text-white shadow shadow-black focus:outline-none">
                        Be careful when changing event dates, as this changes the <br>total fee to be paid with respect to the current date.
                    </div>
                    <label class="ml-1 text-sm">Event Fee (₱):</label>
                    <input type="number" id="edit-fee-per-event" name="edit-fee-per-event" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" min="0" required>
                    <label class="ml-1 text-sm">Sanction Fee (₱):</label>
                    <input type="number" id="edit-sanction-fee" name="edit-sanction-fee" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" min="0" required>
                    <div class="flex items-center justify-center m-2">
                        <button type="submit" class="px-3 py-2 bg-custom-purple rounded-lg focus:outline-none focus:border-purple-500 text-base text-white font-bold hover:bg-custom-purplo" name="update-this-event">Update Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
$conn = null;
$html->endBody();
?>