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
            $collectamount = $data['collect_amount'];
            try {
                $sqladd_collect = "INSERT INTO `sanctions` (`student_id`, `event_id`, `sanctions_paid`) VALUES (?, ?, ?)";
                $stmt_add_collect = $conn->prepare($sqladd_collect);
                if ($stmt_add_collect->execute([$sid, $eventid, $collectamount])) {
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
    } else {
        $response["message"] = "Unknown get method";
    }
    echo json_encode($response);
    exit();
}

// Default View
$html = new HTML("CITreasury - Sanctions");
$html->addLink('stylesheet', '../inter-variable.css');
$html->addLink('icon', '../img/nobgcitsclogo.png');
$html->addScript("../js/tailwind3.4.15.js");
$html->addScript("../js/tailwind.config.js");
$html->addScript("../js/sweetalert.min.js");
$html->addScript("../js/jquery-3.7.1.min.js");
$html->addScript("../js/predefined-script.js");
$html->addScript("../js/defer-script.js", true);
$html->addScript("sanctions.js", true);
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
        <div id="menu-items-mobile" class="fixed block md:hidden h-fit top-16 w-full p-4 bg-custom-purplo opacity-95">
            <?php menuContent(); ?>
        </div>
        <div class="w-full bg-red-50 px-6 min-h-screen">
            <div class="mt-24 flex flex-col lg:flex-row justify-between">
                <h1 class="text-3xl text-custom-purplo font-bold mb-3">Manage Sanctions</h1>
                <!-- Search Bar -->
                <div class="flex flex-row w-56 p-1 mb-3 border-2 border-custom-purple  focus:border-custom-purplo rounded-lg bg-white">
                    <svg id="mdi-magnify" class="h-6 w-6 mr-1 fill-custom-purple" viewBox="0 0 24 24"><path d="M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" /></svg>
                    <form method="GET">
                        <input type="text" id="unregistered-search" name="search" placeholder="Search..." class="w-full focus:outline-none">
                  </form>
                </div>
            </div>
            <?php
            $results_per_page = 10; // Number of results per page
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Current page number
            $offset = ($page - 1) * $results_per_page; // Offset for SQL query
            ?>
            <div class="mt-1 mb-5 overflow-x-auto rounded-lg shadow-lg">
                <div class="overflow-x-auto rounded-lg border border-black">
                    <!-- Table of Unregistered Students -->
                    <table class="w-full px-1 text-center">
                        <?php
                        $sql_unregisteredpast = "
                            SELECT 
                                `students`.*,
                                `events`.*,
                                `events`.`event_fee` + `events`.`sanction_fee` AS `total_fee`,
                                (`events`.`event_fee` + `events`.`sanction_fee` - COALESCE(`sanctions`.`total_sanctions_paid`, 0)) AS `balance`
                            FROM `students`
                            CROSS JOIN `events`
                            LEFT JOIN `registrations` ON `students`.`student_id` = `registrations`.`student_id` AND `events`.`event_id` = `registrations`.`event_id`
                            LEFT JOIN 
                                (
                                    SELECT 
                                        `student_id`, 
                                        `event_id`, 
                                        SUM(`sanctions_paid`) AS `total_sanctions_paid`
                                    FROM 
                                        `sanctions`
                                    GROUP BY 
                                        `student_id`, `event_id`
                                ) AS `sanctions` 
                            ON `students`.`student_id` = `sanctions`.`student_id` AND `events`.`event_id` = `sanctions`.`event_id`
                            WHERE `registrations`.`student_id` IS NULL AND `students`.`student_id` != '".$_SESSION['cit-student-id']."' AND `events`.`event_date` < DATE('now') AND (
                                    `events`.`event_target` LIKE '%' || SUBSTR(`students`.`year_and_section`, 1, 1) || '%' OR `events`.`event_target` LIKE SUBSTR(`students`.`year_and_section`, 1, 1) || ',%' OR `events`.`event_target` LIKE '%, ' || SUBSTR(`students`.`year_and_section`, 1, 1) || '%')";
                        if (isset($_GET['search'])) {
                            $search = '%' . $_GET['search'] . '%';
                            $sql_unregisteredpast .= " AND (`students`.`student_id` LIKE ? OR `students`.`last_name` LIKE ? OR `students`.`first_name` LIKE ? OR `students`.`year_and_section` LIKE ? OR `events`.`event_name` LIKE ?)";
                            ?>
                            <script>
                                $("#unregistered-search").val("<?php echo htmlspecialchars($_GET['search']); ?>");
                            </script>
                            <?php
                        }
                        $sql_unregisteredpast .= " ORDER BY `balance` DESC LIMIT ? OFFSET ?";
                        try {
                            $stmt_unregisteredpast = $conn->prepare($sql_unregisteredpast);
                            if (isset($search)) {
                                $stmt_unregisteredpast->bindParam(1, $search, PDO::PARAM_STR);
                                $stmt_unregisteredpast->bindParam(2, $search, PDO::PARAM_STR);
                                $stmt_unregisteredpast->bindParam(3, $search, PDO::PARAM_STR);
                                $stmt_unregisteredpast->bindParam(4, $search, PDO::PARAM_STR);
                                $stmt_unregisteredpast->bindParam(5, $search, PDO::PARAM_STR);
                                $stmt_unregisteredpast->bindParam(6, $results_per_page, PDO::PARAM_INT);
                                $stmt_unregisteredpast->bindParam(7, $offset, PDO::PARAM_INT);
                            } else {
                                $stmt_unregisteredpast->bindParam(1, $results_per_page, PDO::PARAM_INT);
                                $stmt_unregisteredpast->bindParam(2, $offset, PDO::PARAM_INT);
                            }
                            $stmt_unregisteredpast->execute();
                            $result_unregisteredpast = $stmt_unregisteredpast->fetchAll(PDO::FETCH_ASSOC);
                            if (count($result_unregisteredpast) > 0) {
                                ?>
                                <thead class="text-white uppercase bg-custom-purplo ">
                                    <tr>
                                        <th scope="col" class="p-2 border-r border-black">Student ID</th>
                                        <th scope="col" class="p-2 border-r border-black">Student Name</th>
                                        <th scope="col" class="p-2 border-r border-black">Year & Section</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Name</th>
                                        <th scope="col" class="p-2 border-r border-black">Event Date</th>
                                        <th scope="col" class="p-2 border-r border-black">Total Fees (₱)</th>
                                        <th scope="col" class="p-2 border-r border-black">Balance (₱)</th>
                                        <th scope="col" class="p-2">Actions</th>
                                    </tr>
                                </thead>
                                <?php
                                foreach ($result_unregisteredpast as $row) {
                                    $sid = $row['student_id'];
                                    $lastname = $row['last_name'];
                                    $firstname = $row['first_name'];
                                    $mi = !empty($row['middle_initial']) ? $row['middle_initial'] . '.' : "";
                                    $yearsec = $row['year_and_section'];
                                    $eventid = $row['event_id'];
                                    $eventname = $row['event_name'];
                                    $eventdate = $row['event_date'];
                                    $totalfee = $row['total_fee'];
                                    $balance = $row['balance'];
                                    ?>
                                    <tr class="border-t border-black">
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $sid; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $lastname . ', ' . $firstname . ' ' . $mi; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $yearsec; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventname; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $eventdate; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $totalfee; ?></td>
                                        <td class="px-2 border-r border-black bg-purple-100"><?php echo $balance; ?></td>
                                        <td class="max-w-56 bg-purple-100">
                                            <button class="px-3 py-2 my-1 mx-1 bg-green-500 text-white text-sm font-semibold rounded-lg focus:outline-none disabled:bg-gray-400 shadow hover:bg-green-400" onclick='collect(this, "<?php echo $eventid; ?>")' <?php if ($balance <= 0) echo 'disabled'; ?>>
                                                <svg id="mdi-wallet-plus" class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M3 0V3H0V5H3V8H5V5H8V3H5V0H3M9 3V6H6V9H3V19C3 20.1 3.89 21 5 21H19C20.11 21 21 20.11 21 19V18H12C10.9 18 10 17.11 10 16V8C10 6.9 10.89 6 12 6H21V5C21 3.9 20.11 3 19 3H9M12 8V16H22V8H12M16 10.5C16.83 10.5 17.5 11.17 17.5 12C17.5 12.83 16.83 13.5 16 13.5C15.17 13.5 14.5 12.83 14.5 12C14.5 11.17 15.17 10.5 16 10.5Z" /></svg>
                                            </button> <!-- Disable button if balance is zero -->
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else { // If query returned no results, display this
                                ?><h3 class="p-4">No unregistered students found.</h3><?php
                            }
                        } catch (PDOException $e) {
                            $result_unregisteredpast = [];
                            ?><h3 class="p-4">Page not found</h3><br><?php echo $e->getMessage();
                        }
                        ?>
                    </table>
                </div>
            </div>
            <!-- Pagination controls -->
            <div class="pagination my-2">
                <?php
                # Get the total number of rows for pagination
                $sql_count = "
                    SELECT COUNT(*)
                    FROM `students`
                    CROSS JOIN `events`
                    LEFT JOIN `registrations` 
                    ON `students`.`student_id` = `registrations`.`student_id` 
                       AND `events`.`event_id` = `registrations`.`event_id`
                    WHERE `registrations`.`student_id` IS NULL 
                      AND `events`.`event_date` < DATE('now')
                      AND ',' || `events`.`event_target` || ',' LIKE '%,' || SUBSTR(`students`.`year_and_section`, 1, 1) || ',%' ";
                if (isset($_GET['search'])) {
                    $sql_count .= " AND (`students`.`student_id` LIKE ? OR `students`.`last_name` LIKE ? OR `students`.`first_name` LIKE ? OR `students`.`year_and_section` LIKE ? OR `events`.`event_name` LIKE ?)";
                }
                $stmt_total = $conn->prepare($sql_count);
                if (isset($_GET['search'])) {
                    $stmt_total->execute([$search, $search, $search, $search, $search]);
                } else {
                    $stmt_total->execute();
                }
                $total_records = $stmt_total->fetchColumn();
                $stmt_total = null;
                if (count($result_unregisteredpast) > 0) {
                    ?>
                     <div id="has-result" class="w-full mb-2">
                        <p>Showing <?php echo $results_per_page; ?> entries per page</p>
                        <p>Results: <?php echo $total_records ?> row(s)</p>
                    </div>
                    <?php
                    # Calculate total pages
                    $total_pages = ceil($total_records / $results_per_page);
                    # Display pagination buttons
                    for ($i = 1; $i <= $total_pages; $i++) {
                        ?><a href='sanctions.php?<?php echo (isset($search)) ? "search=".htmlspecialchars($_GET['search'])."&" : ""; ?>page=<?php echo $i; ?>'><button class="px-3 py-2 my-1 mr-1 <?php echo $page == $i ? 'bg-purple-600' : 'bg-custom-purplo'; ?> text-white text-sm font-semibold rounded-lg focus:outline-none shadow hover:bg-custom-purple"><?php echo $i; ?></button></a>
                            <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
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
                <h3 class="text-2xl font-semibold text-custom-purple mb-3">Collect Sanction Fees</h3>
                <form id="collect-sanction-form">
                    <label class="ml-1 text-sm">Student ID:</label>
                    <input type="text" id="collect-student-id" name="collect-student-id" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" maxlength="7" readonly>
                    <label class="ml-1 text-sm">Student Name:</label>
                    <input type="text" id="collect-student-name" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <input type="hidden" id="collect-event-id" name="collect-event-id">
                    <label class="ml-1 text-sm">Event Name:</label>
                    <input type="text" id="collect-event-name" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Total Fee (₱):</label>
                    <input type="number" id="collect-total-fee" name="collect-total-fee" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Balance (₱):</label>
                    <input type="number" id="collect-balance" name="collect-balance" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" readonly>
                    <label class="ml-1 text-sm">Collected Amount (₱):</label>
                    <input type="number" id="collect-amount" name="collect-amount" class="w-full px-2 py-1 border-2 border-custom-purple rounded-lg mb-1 focus:outline-none focus:border-purple-500 bg-purple-100" required>
                    <div class="flex items-center justify-center m-4">
                        <button type="submit" class="px-3 py-2 bg-custom-purple rounded-lg focus:outline-none focus:border-purple-500 text-base text-white font-bold disabled:bg-gray-400 hover:bg-custom-purplo" id="collect-this-fee" name="collect-this-fee" disabled>Collect Sanctions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php
$conn = null;
$html->endBody();
?>