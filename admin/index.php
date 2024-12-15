<?php
session_start();
include '../connection.php';
include '../helperfunctions.php';
include '../components/menu.php';
include '../components/nav.php';
verifyAdminLoggedIn($conn);

$html = new HTML("CITreasury - Dashboard");
$html->addLink('stylesheet', '../inter-variable.css');
$html->addLink('icon', '../img/nobgcitsclogo.png');
$html->addScript("../js/tailwind3.4.15.js");
$html->addScript("../js/tailwind.config.js");
$html->addScript("../js/sweetalert.min.js");
$html->addScript("../js/jquery-3.7.1.min.js");
$html->addScript("../js/apexcharts.js");
$html->addScript("../js/predefined-script.js");
$html->addScript("../js/defer-script.js", true);
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
            <div class="mt-24">
                <h1 class="text-3xl text-custom-purplo font-bold mb-5">Dashboard</h1>
                <?php
                # Create a function to display first data in sql query
                function getQueryString($conn, $sql, $target) {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        if ($row[$target] === NULL) {
                            return "0";
                        }
                        return $row[$target];
                    }
                    return "--";
                }
                $totalfirstyear = getQueryString($conn, "SELECT COUNT(*) FROM `students` WHERE `year_and_section` LIKE '1%'", "COUNT(*)");
                $totalsecondyear = getQueryString($conn, "SELECT COUNT(*) FROM `students` WHERE `year_and_section` LIKE '2%'", "COUNT(*)");
                $totalthirdyear = getQueryString($conn, "SELECT COUNT(*) FROM `students` WHERE `year_and_section` LIKE '3%'", "COUNT(*)");
                $totalfourthyear = getQueryString($conn, "SELECT COUNT(*) FROM `students` WHERE `year_and_section` LIKE '4%'", "COUNT(*)");
                ?>
                <div class="flex lg:flex-row flex-col">
                    <div class="w-full bg-green-600 rounded shadow-lg mr-4 mb-4">
                        <div class="w-full flex flex-row justify-between items-center">
                            <h3 class="mx-3 my-5 text-white">Total Students</h3>
                            <h2 class="mx-3 my-5 text-4xl font-bold text-white"><?php echo getQueryString($conn, "SELECT COUNT(*) FROM `students`", "COUNT(*)"); ?></h2>
                        </div>
                        <div class="w-full px-3 py-2 bg-green-700 rounded-b">
                            <p class="text-xs font-bold text-white">Total Number of Students in CIT</p>
                        </div>
                    </div>
                    <div class="w-full bg-yellow-600 rounded shadow-lg mr-4 mb-4">
                        <div class="w-full flex flex-row justify-between items-center">
                            <h3 class="mx-3 my-5 text-white">Total Fees</h3>
                            <h2 class="mx-3 my-5 text-4xl font-bold text-white">₱ <?php echo getQueryString($conn, "SELECT SUM(total_paid) AS total_amount_paid FROM (SELECT SUM(`paid_fees`) AS total_paid FROM `registrations` UNION ALL SELECT SUM(`sanctions_paid`) AS total_paid FROM `sanctions`) AS combined_payments; ", "total_amount_paid"); ?></h2>
                        </div>
                        <div class="w-full px-3 py-2 bg-yellow-700 rounded-b">
                            <p class="text-xs font-bold text-white">Total Amount Collected </p>
                        </div>
                    </div>
                    <div class="w-full bg-red-600 rounded shadow-lg mr-4 mb-4">
                        <div class="w-full flex flex-row justify-between items-center">
                            <h3 class="mx-3 my-5 text-white">Number of Events</h3>
                            <h2 class="mx-3 my-5 text-4xl font-bold text-white"><?php echo getQueryString($conn, "SELECT COUNT(`event_id`) FROM `events`", "COUNT(`event_id`)"); ?></h2>
                        </div>
                        <div class="w-full px-3 py-2 bg-red-700 rounded-b">
                            <p class="text-xs font-bold text-white">Total Number of Events</p>
                        </div>
                    </div>
                </div>
                <div class="py-6" id="donut-chart"></div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        const getChartOptions = () => {
          return {
            series: [<?php echo $totalfirstyear; ?>, <?php echo $totalsecondyear; ?>, <?php echo $totalthirdyear; ?>, <?php echo $totalfourthyear; ?>], // Display all students
            colors: ["#16a34a", "#ca8a04", "#dc2626", "#16BDCA"],
            chart: {
              height: 320,
              width: "100%",
              type: "donut",
            },
            stroke: {
              colors: ["transparent"],
              lineCap: "",
            },
            plotOptions: {
              pie: {
                donut: {
                  labels: {
                    show: true,
                    name: {
                      show: true,
                      offsetY: 20,
                    },
                    total: {
                      showAlways: true,
                      show: true,
                      label: "Total Students",
                      formatter: function (w) {
                        const sum = w.globals.seriesTotals.reduce((a, b) => {
                          return a + b
                        }, 0)
                        return sum // If decimal, use this: sum.toFixed(1)
                      },
                    },
                    value: {
                      show: true,
                      offsetY: -20,
                      formatter: function (value) {
                        return value
                      },
                    },
                  },
                  size: "80%",
                },
              },
            },
            labels: ["1st Year", "2nd Year", "3rd Year", "4th Year"],
            dataLabels: {
              enabled: false,
            },
            legend: {
              position: "bottom",
              fontFamily: "Inter, sans-serif",
            },
            yaxis: {
              labels: {
                formatter: function (value) {
                  return value
                },
              },
            },
            xaxis: {
              labels: {
                formatter: function (value) {
                  return value
                },
              },
            },
          }
        }
        if (document.getElementById("donut-chart") && typeof ApexCharts !== 'undefined') {
          const chart = new ApexCharts(document.getElementById("donut-chart"), getChartOptions());
          chart.render();
        }
    </script>
<?php
$conn = null;
$html->endBody();
?>