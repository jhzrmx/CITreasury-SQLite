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

    if ($_GET['api'] == 'collect') {
        if (isset($data['collect_student_id'], $data['collect_event_id'], $data['collect_amount'])) {
            $sid = $data['collect_student_id'];
            $eventid = $data['collect_event_id'];
            $collectamount = floatval($data['collect_amount']);
            try {
                $sqlupdate_collect = "UPDATE `registrations` SET `paid_fees` = (`paid_fees` + ?) WHERE `event_id` = ? AND `student_id` = ? ";
                $stmt_update_collect = $conn->prepare($sqlupdate_collect);
                $stmt_update_collect->bindParam(1, $collectamount, PDO::PARAM_INT);
                $stmt_update_collect->bindParam(2, $eventid, PDO::PARAM_INT);
                $stmt_update_collect->bindParam(3, $sid, PDO::PARAM_STR);
                if ($stmt_update_collect->execute()) {
                    $response["status"] = "success";
                    $response["message"] = "Sanction fees collected!";
                } else {
                    $response["message"] = "Failed to collect fee!";
                }
            } catch (Exception $e) {
                $response["message"] = "Database error";
                $response["details"] = $e->getMessage();
            }
        } else {
            $response["message"] = "Invalid data";
        }
    } elseif ($_GET['api'] == 'register') {
        if (isset($data['register_student_id'], $data['register_event_id'], $data['register_advance_fee'])) {
            $sid = $data['register_student_id'];
            $eventid = $data['register_event_id'];
            $advancefee = $data['register_advance_fee'];
            
            try {
                # Step 1: Get the student's year level
                $query_year_level = "SELECT `year_and_section` FROM `students` WHERE `student_id` = ?";
                $stmt_year_level = $conn->prepare($query_year_level);
                $stmt_year_level->execute([$sid]);
                $year_and_section = $stmt_year_level->fetch(PDO::FETCH_ASSOC)['year_and_section'];
                $stmt_year_level = null;

                # Step 2: Extract the year level (e.g., '2C' -> '2')
                $year_level = substr($year_and_section, 0, 1);
                
                # Step 3: Get the event's target (e.g., '1,2,3,4')
                $query_event_target = "SELECT `event_target` FROM `events` WHERE `event_id` = ?";
                $stmt_event_target = $conn->prepare($query_event_target);
                $stmt_event_target->execute([$eventid]);
                $event_target = $stmt_event_target->fetch(PDO::FETCH_ASSOC)['event_target'];
                $stmt_event_target = null;

                # Step 4: Check if the year level is in the event target
                $allowed_years = explode(',', $event_target);  // Split the event target into an array
                if (!in_array($year_level, $allowed_years)) {
                    $response["message"] = "Student is not allowed to register for this event!";
                } else {
                    # Step 5: Check if student is already registered in the event
                    $sql_verify_register = "SELECT * FROM `registrations` WHERE `event_id` = ? AND `student_id` = ? LIMIT 5";
                    $stmt_verify_register = $conn->prepare($sql_verify_register);
                    $stmt_verify_register->bindParam(1, $eventid, PDO::PARAM_INT);
                    $stmt_verify_register->bindParam(2, $sid, PDO::PARAM_STR);

                    if ($stmt_verify_register->execute() && $stmt_verify_register->rowCount() > 0) {
                        $response["message"] = "You can't register a student twice in this event!";
                    } elseif ($stmt_verify_register->rowCount() == 0) {
                        # Step 6: Register the student
                        $sql_register = "INSERT INTO `registrations`(`event_id`, `student_id`, `registration_date`, `paid_fees`) VALUES (?, ?, CURRENT_TIMESTAMP, ?)";
                        $stmt_register = $conn->prepare($sql_register);
                        $stmt_register->bindParam(1, $eventid, PDO::PARAM_INT);
                        $stmt_register->bindParam(2, $sid, PDO::PARAM_STR);
                        $stmt_register->bindParam(3, $advancefee, PDO::PARAM_INT);

                        if ($stmt_register->execute()) {
                            $response["status"] = "success";
                            $response["message"] = "Student registered successfully!";
                        } else {
                            $response["message"] = "Registration failed!";
                        }
                    }
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
$html->addScript("eventsregistration.js", true);
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
            <?php
            # If event id is found in URL query
            if (isset($_GET['event-id'])) {
            ?>
            <div class="mt-24 flex flex-col lg:flex-row justify-between">
                <?php
                $sql_title = "SELECT * FROM `events` WHERE `event_id` = ?";
                $stmt_title = $conn->prepare($sql_title);
                $stmt_title->bindParam(1, $_GET['event-id'], PDO::PARAM_INT);
                $stmt_title->execute();
                $dateofcurrenteventinget = "";
                if ($row_title = $stmt_title->fetch(PDO::FETCH_ASSOC)) {
                    $dateofcurrenteventinget = $row_title['event_date'];
                    $currenteventfee = $row_title['event_fee'];
                    # Set event name in title using event-id in URL query
                    ?><h1 id="event-title" class="text-3xl text-custom-purplo font-bold mb-3"><?php echo $row_title['event_name']; ?></h1><?php
                }
                $sql_update_status = "UPDATE `registrations`
                    SET `status` = 'FULLY_PAID_BEFORE_EVENT'
                    WHERE `event_id` = ?
                      AND EXISTS (
                        SELECT 1
                        FROM `events`
                        WHERE `events`.`event_id` = `registrations`.`event_id`
                          AND `events`.`event_date` >= DATE('now')
                          AND `events`.`event_fee` = `registrations`.`paid_fees`
                      ); ";
                $stmt_update_status = $conn->prepare($sql_update_status);
                $stmt_update_status->bindParam(1, $_GET['event-id'], PDO::PARAM_INT);
                $stmt_update_status->execute();
                ?>
                <!-- Search Bar -->
                <div class="flex flex-row w-56 p-1 mb-3 border-2 border-custom-purple  focus:border-custom-purplo rounded-lg bg-white">
                    <svg id="mdi-account-search" class="h-6 w-6 mr-1 fill-custom-purple" viewBox="0 0 24 24"><path d="M15.5,12C18,12 20,14 20,16.5C20,17.38 19.75,18.21 19.31,18.9L22.39,22L21,23.39L17.88,20.32C17.19,20.75 16.37,21 15.5,21C13,21 11,19 11,16.5C11,14 13,12 15.5,12M15.5,14A2.5,2.5 0 0,0 13,16.5A2.5,2.5 0 0,0 15.5,19A2.5,2.5 0 0,0 18,16.5A2.5,2.5 0 0,0 15.5,14M10,4A4,4 0 0,1 14,8C14,8.91 13.69,9.75 13.18,10.43C12.32,10.75 11.55,11.26 10.91,11.9L10,12A4,4 0 0,1 6,8A4,4 0 0,1 10,4M2,20V18C2,15.88 5.31,14.14 9.5,14C9.18,14.78 9,15.62 9,16.5C9,17.79 9.38,19 10,20H2Z" /></svg>
                    <form method="GET">
                        <input type="hidden" name="event-id" value="<?php echo $_GET['event-id']; ?>">
                        <input type="text" id="registered-search" name="search" placeholder="Search registered..." class="w-full focus:outline-none">
                    </form>
                </div>
            </div>
            <div class="fixed bottom-10 right-6">
                <?php
                # If the event has passed, disable the registration button, otherwise enable it
                $isEventPast = strtotime($dateofcurrenteventinget) < strtotime(date("Y-m-d"));
                $buttonClasses = "focus:outline-none rounded-full shadow-md shadow-gray-500";
                $svgClasses = "w-16 h-16 bg-white rounded-full " . ($isEventPast ? "fill-gray-400" : "fill-green-500 hover:fill-green-600");
                ?>
                <button id="register-a-student" class="<?php echo $buttonClasses; ?>" <?php if (!$isEventPast) echo "title='Register a Student'"; ?> <?php if ($isEventPast) echo "disabled"; ?>>
                    <svg id="mdi-plus-circle" class="<?php echo $svgClasses; ?>" viewBox="2 2 20 20">
                        <path d="M17,13H13V17H11V13H7V11H11V7H13V11H17M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                    </svg>
                </button>
            </div>
            <div class="mt-1 mb-5 overflow-x-auto rounded-lg shadow-lg">
                <div class="overflow-x-auto rounded-lg border border-black">
                    <!-- Table of Registered Students -->
                    <table class="w-full px-1 text-center">
                        <?php
                        $results_per_page = 10;
                        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                        $offset = ($page - 1) * $results_per_page;
                        $eventId = $_GET['event-id'];
                        # Query for displaying registered students in a particular event
                        # If the event was happened and the student has still balance, the total fees to paid would be the event fee + sanction fee
                        # Else if the event wasnt happened yet or the event fee was fully paid after registration, the total fees would be the event fee alone
                        # Or else, sanction fee was also applied
                        # The balance would also be affected
                        $sql = "SELECT  `students`.*, `registrations`.`registration_date`, 
                                    CASE 
                                        WHEN `events`.`event_date` < DATE('now') AND `events`.`event_fee` > `registrations`.`paid_fees` 
                                        THEN `events`.`event_fee` + `events`.`sanction_fee` 
                                        WHEN `events`.`event_date` >= DATE('now') OR `registrations`.`status` = 'FULLY_PAID_BEFORE_EVENT' 
                                        THEN `events`.`event_fee` 
                                        ELSE `events`.`event_fee` + `events`.`sanction_fee` 
                                    END AS `total_fee`, 
                                    CASE 
                                        WHEN `events`.`event_date` < DATE('now') AND `events`.`event_fee` > `registrations`.`paid_fees` 
                                        THEN (`events`.`event_fee` + `events`.`sanction_fee`) - `registrations`.`paid_fees` 
                                        WHEN `events`.`event_date` >= DATE('now') OR `registrations`.`status` = 'FULLY_PAID_BEFORE_EVENT' 
                                        THEN `events`.`event_fee` - `registrations`.`paid_fees`
                                        ELSE (`events`.`event_fee` + `events`.`sanction_fee`) - `registrations`.`paid_fees` 
                                    END AS `balance`
                                FROM `students` 
                                JOIN `registrations` ON `students`.`student_id` = `registrations`.`student_id` 
                                JOIN `events` ON `events`.`event_id` = `registrations`.`event_id` 
                                WHERE `registrations`.`event_id` = ? 
                                AND ',' || `events`.`event_target` || ',' LIKE '%,' || SUBSTR(`students`.`year_and_section`, 1, 1) || ',%' ";
                        if (isset($_GET['search'])) {
                            $search = '%' . $_GET['search'] . '%';
                            $sql .= " AND (`students`.`student_id` LIKE ? OR `students`.`last_name` LIKE ? OR `students`.`first_name` LIKE ? OR `students`.`year_and_section` LIKE ? OR `registrations`.`registration_date` LIKE ?)";
                            ?>
                            <script>
                                $("#registered-search").val("<?php echo htmlspecialchars($_GET['search']); ?>");
                            </script>
                            <?php
                        }
                        $sql .= " ORDER BY `balance` DESC LIMIT ? OFFSET ?";
                        try {
                            $stmt = $conn->prepare($sql);
                            if (isset($search)) {
                                $stmt->bindParam(1, $eventId, PDO::PARAM_INT);
                                $stmt->bindParam(2, $search, PDO::PARAM_STR);
                                $stmt->bindParam(3, $search, PDO::PARAM_STR);
                                $stmt->bindParam(4, $search, PDO::PARAM_STR);
                                $stmt->bindParam(5, $search, PDO::PARAM_STR);
                                $stmt->bindParam(6, $search, PDO::PARAM_STR);
                                $stmt->bindParam(7, $results_per_page, PDO::PARAM_INT);
                                $stmt->bindParam(8, $offset, PDO::PARAM_INT);
                            } else {
                                $stmt->bindParam(1, $eventId, PDO::PARAM_INT);
                                $stmt->bindParam(2, $results_per_page, PDO::PARAM_INT);
                                $stmt->bindParam(3, $offset, PDO::PARAM_INT);
                            }
                            $stmt->execute();
                            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (count($result) > 0) {
                                ?>
                                <thead class="text-white uppercase bg-custom-purplo ">
                                    <tr>
                                        <th scope="col" class="p-2 border-r border-black">Student ID</th>
                                        <th scope="col" class="p-2 border-r border-black">Student Name</th>
                                        <th scope="col" class="p-2 border-r border-black">Year & Section</th>
                                        <th scope="col" class="p-2 border-r border-black">Registration Date</th>
                                        <th scope="col" class="p-2 border-r border-black">Total Fees (₱)</th>
                                        <th scope="col" class="p-2 border-r border-black">Balance (₱)</th>
                                        <th scope="col" class="p-2">Actions</th>
                                    </tr>
                                </thead>
                                <?php
                                foreach ($result as $row) {
                                    $sid = $row['student_id'];
                                    $lastname = $row['last_name'];
                                    $firstname = $row['first_name'];
                                    $mi = !empty($row['middle_initial']) ? $row['middle_initial'] . '.' : "";
                                    $yearsec = $row['year_and_section'];
                                    $regdate = $row['registration_date'];
                                    $totalfee = $row['total_fee'];
                                    $balance = $row['balance'];
                                    ?>
                                    <tr class="border-t border-black">
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $sid; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $lastname . ', ' . $firstname . ' ' . $mi; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $yearsec; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $regdate; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $totalfee; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $balance; ?></td>
                                        <td class="max-w-56 bg-purple-100">
                                            <button class="px-3 py-2 my-1 mx-1 bg-green-500 text-white text-sm font-semibold rounded-lg focus:outline-none disabled:bg-gray-400 shadow hover:bg-green-400" onclick='collect(this)' <?php if ($balance <= 0) echo 'disabled'; ?>>
                                                <svg id="mdi-wallet-plus" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M3 0V3H0V5H3V8H5V5H8V3H5V0H3M9 3V6H6V9H3V19C3 20.1 3.89 21 5 21H19C20.11 21 21 20.11 21 19V18H12C10.9 18 10 17.11 10 16V8C10 6.9 10.89 6 12 6H21V5C21 3.9 20.11 3 19 3H9M12 8V16H22V8H12M16 10.5C16.83 10.5 17.5 11.17 17.5 12C17.5 12.83 16.83 13.5 16 13.5C15.17 13.5 14.5 12.83 14.5 12C14.5 11.17 15.17 10.5 16 10.5Z" /></svg>
                                            </button> <!-- Disable button if balance is zero -->
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else { // If query returned no results, display this
                                ?><h3 class="p-4">No registrations found.</h3><?php
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
                $sql_total = "SELECT COUNT(*) 
                    FROM `students` 
                    JOIN `registrations` ON `students`.`student_id` = `registrations`.`student_id` 
                    JOIN `events` ON `events`.`event_id` = `registrations`.`event_id` 
                    WHERE `registrations`.`event_id` = ? 
                    AND ',' || `events`.`event_target` || ',' LIKE '%,' || SUBSTR(`students`.`year_and_section`, 1, 1) || ',%' ";
                if (isset($_GET['search'])) {
                    $sql_total .= " AND (`students`.`student_id` LIKE ? 
                        OR `students`.`last_name` LIKE ? 
                        OR `students`.`first_name` LIKE ? 
                        OR `students`.`year_and_section` LIKE ? 
                        OR `registrations`.`registration_date` LIKE ?)";
                    $stmt_total = $conn->prepare($sql_total);
                    $stmt_total->execute([$search, $search, $search, $search, $search]);
                } else {
                    $stmt_total = $conn->prepare($sql_total);
                    $stmt_total->execute([$_GET['event-id']]);
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
                        ?><a href='eventsregistration.php?event-id=<?php echo $_GET['event-id']?>&<?php echo (isset($search)) ? "search=".htmlspecialchars($_GET['search'])."&" : ""; ?>page=<?php echo $i; ?>'><button class="px-3 py-2 my-1 mr-1 <?php echo $page == $i ? 'bg-purple-600' : 'bg-custom-purplo'; ?> text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-custom-purple"><?php echo $i; ?></button></a>
                        <?php
                    }
                }
                ?>
            </div>
            <?php
            } else {
            # If event id is not found in URL query, default landing page of eventregistrations.php
            ?>
            <div class="mt-24 flex flex-col lg:flex-row justify-between">
                <h1 id="event-title" class="text-3xl text-custom-purplo font-bold mb-5">Manage Registrations</h1>
            </div>
            <div class="container mx-auto mb-6">
                <div class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <?php
                    $colors = ['red', 'green', 'blue', 'yellow', 'purple', 'pink'];
                    $sql_event = "SELECT `events`.*, COALESCE(`registration_counts`.`registration_count`, 0) AS `registration_count` FROM `events` LEFT JOIN (SELECT `event_id`, COUNT(*) AS `registration_count` FROM `registrations` GROUP BY `event_id`) `registration_counts` ON `events`.`event_id` = `registration_counts`.`event_id`";
                    $stmt_event = $conn->prepare($sql_event);
                    $stmt_event->execute();
                    $result_event = $stmt_event->fetchAll(PDO::FETCH_ASSOC);
                    if (count($result_event) > 0) {
                        $i = 0;
                        foreach ($result_event as $row_event) {
                            $randomColor = $colors[$i];
                            ?>
                            <!-- Card of Events -->
                            <div class="w-full flex flex-col justify-between bg-<?php echo $randomColor; ?>-600 rounded shadow-lg">
                                <div class="w-full px-3 pt-3 flex flex-row justify-between items-center">
                                    <h3 class="text-2xl text-white font-semibold">
                                        <?php echo $row_event['event_name']; ?>
                                    </h3>
                                    <h2 class="text-4xl font-bold text-white">
                                        ₱ <?php echo $row_event['event_fee']; ?>
                                    </h2>
                                </div>
                                <div class="w-full px-3 py-2 text-white">
                                    <p class="my-1 text-sm font-bold">
                                        Total Registered: <?php echo $row_event['registration_count']; ?>
                                    </p>
                                    <p class="my-1 text-xs">
                                        Year Levels Required: <?php echo $row_event['event_target']; ?>
                                    </p>
                                    <p class="my-1 text-xs">
                                        Date: <?php echo $row_event['event_date']; ?>
                                    </p>
                                </div>
                                <div class="w-full px-3 py-2 bg-<?php echo $randomColor; ?>-700 rounded-b">
                                    <a href="eventsregistration.php?event-id=<?php echo $row_event['event_id']; ?>" class="text-xs font-bold text-white hover:underline">View Registrations</a> <!-- Add URL query with event-id -->
                                </div>
                            </div>
                            <?php
                            $i++;
                            if ($i == sizeof($colors)) {
                                $i=0;
                            }
                        }
                    } else {
                        ?><p>No events found.</p><?php
                    }
                    ?>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
    <?php
    if (isset($_GET['event-id'])) {
    ?>
    <!-- Darken Background for Modal, hidden by default -->
    <div id="collect-popup-bg" class="fixed top-0 w-full min-h-screen bg-black opacity-50 hidden"></div>
    <!-- Popup Modal For Collecting Fees, hidden by default -->
    <div id="collect-popup-item" class="fixed top-0 w-full min-h-screen hidden">
        <div class="w-full min-h-screen flex items-center justify-center">
            <div class="m-5 w-full py-3 px-5 sm:w-1/2 lg:w-1/3 xl:1/4 rounded bg-white h-fit shadow-lg shadow-black">
                <div class="w-full flex justify-end">
                    <button class="focus:outline-none" id="collect-close-popup">
                        <svg id="mdi-close-box-outline" class="mt-2 w-6 h-6 hover:fill-red-500" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M17,8.4L13.4,12L17,15.6L15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4Z" /></svg>
                    </button>
                </div>
                <h3 class="text-2xl font-semibold text-custom-purple mb-3">Collect Fee</h3>
                <form id="collect-fee-form">
                    <input type="hidden" id="collect-event-id" name="collect-event-id" value="<?php echo $_GET['event-id']; ?>">
                    <label class="ml-1 text-sm">Student ID:</label>
                    <input type="text" id="collect-student-id" name="collect-student-id" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" maxlength="7" readonly>
                    <label class="ml-1 text-sm">Student Name:</label>
                    <input type="text" id="collect-student-name" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Total Fee (₱):</label>
                    <input type="number" id="collect-total-fee" name="collect-total-fee" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Balance (₱):</label>
                    <input type="number" id="collect-balance" name="collect-balance" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Collected Amount (₱):</label>
                    <input type="number" id="collect-amount" name="collect-amount" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required>
                    <div class="flex items-center justify-center m-4">
                        <button type="submit" class="px-3 py-2 bg-custom-purple rounded-lg focus:outline-none focus:border-purple-500 text-base text-white font-bold disabled:bg-gray-400 hover:bg-custom-purplo" id="collect-this-fee" name="collect-this-fee" disabled>Collect Fee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Darken Background for Modal, hidden by default -->
    <div id="register-popup-bg" class="fixed top-0 w-full min-h-screen bg-black opacity-50 hidden"></div>
    <!-- Popup Modal For Registration, hidden by default  -->
    <div id="register-popup-item" class="fixed top-0 w-full min-h-screen hidden">
        <div class="w-full min-h-screen flex items-center justify-center">
            <div class="m-5 w-full py-3 px-5 sm:w-1/2 lg:w-1/3 xl:1/4 rounded bg-white h-fit shadow-lg shadow-black">
                <div class="w-full flex justify-end">
                    <button class="focus:outline-none" id="register-close-popup">
                        <svg id="mdi-close-box-outline" class="mt-2 w-6 h-6 hover:fill-red-500" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M17,8.4L13.4,12L17,15.6L15.6,17L12,13.4L8.4,17L7,15.6L10.6,12L7,8.4L8.4,7L12,10.6L15.6,7L17,8.4Z" /></svg>
                    </button>
                </div>
                <h3 class="text-2xl font-semibold text-custom-purple mb-3">Register a Student</h3>
                <form id="register-student-form">
                    <input type="hidden" id="register-event-id" name="register-event-id" value="<?php echo $_GET['event-id']; ?>">
                    <label class="ml-1 text-sm">Student ID:</label>
                    <input type="text" id="register-student-id" name="register-student-id" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" maxlength="7" required>
                    <label class="ml-1 text-sm">Advance Fee (₱):</label>
                    <input type="number" id="register-advance-fee" name="register-advance-fee" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required value="0" min="0" max="<?php echo isset($totalfee) ? $totalfee : $currenteventfee ; ?>">
                    <div class="flex items-center justify-center m-4">
                        <button type="submit" class="px-3 py-2 bg-custom-purple rounded-lg focus:outline-none focus:border-purple-500 text-base text-white font-bold hover:bg-custom-purplo" name="register-this-student">Register Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    }
    ?>
<?php
$conn = null;
$html->endBody();
?>