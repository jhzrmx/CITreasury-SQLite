<?php
include 'password_compat.php';

# BEGIN TEST CREDENTIALS
$sid = "22-1342";
$lastname = "RMX";
$firstname = "JHZ";
$mi = "A";
$yearsec = "3A";
$email = strtolower(str_replace(" ", "", $firstname)) . "." . strtolower(str_replace(" ", "", $lastname)) . "@cbsua.edu.ph";
$hash_password = password_hash("cit-" . $sid, PASSWORD_DEFAULT);
# END TEST CREDENTIALS
$sql_student = "INSERT INTO `students`(`student_id`, `last_name`, `first_name`, `middle_initial`, `year_and_section`) VALUES (?, ?, ?, ?, ?)";
$sql_account = "INSERT INTO `accounts`(`email`, `password`, `student_id`, `type`) VALUES (?, ?, ?, 'admin')";

$import_sql = "PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS `students` (
  `student_id` TEXT NOT NULL,
  `last_name` TEXT NOT NULL,
  `first_name` TEXT NOT NULL,
  `middle_initial` TEXT NOT NULL,
  `year_and_section` TEXT NOT NULL,
  PRIMARY KEY (`student_id`)
);

CREATE TABLE IF NOT EXISTS `accounts` (
  `account_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `email` TEXT NOT NULL,
  `password` TEXT NOT NULL,
  `student_id` TEXT NOT NULL,
  `type` TEXT NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `events` (
  `event_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `event_name` TEXT NOT NULL,
  `event_description` TEXT NOT NULL,
  `event_target` TEXT NOT NULL,  -- Replaced 'SET' with TEXT and use delimited values for event targets
  `event_date` DATE NOT NULL,
  `event_fee` INTEGER NOT NULL,
  `sanction_fee` INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS `registrations` (
  `registration_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `event_id` INTEGER NOT NULL,
  `student_id` TEXT NOT NULL,
  `registration_date` DATE NOT NULL,
  `paid_fees` INTEGER NOT NULL,
  `status` TEXT DEFAULT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS `sanctions` (
  `sanction_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `student_id` TEXT NOT NULL,
  `event_id` INTEGER NOT NULL,
  `sanctions_paid` INTEGER NOT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
);
";

try {
  $conn = new PDO('sqlite:citreasury.db');
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $conn->exec($import_sql);
  echo "Database has been initialized.<br>";
	$stmt_student = $conn->prepare($sql_student);
	$stmt_account = $conn->prepare($sql_account);
	$stmt_student->execute([$sid, $lastname, $firstname, $mi, $yearsec]);
  $stmt_account->execute([$email, $hash_password, $sid]);
  echo "Admin account created:<br>Email: ".$email."<br>Password: cit-".$sid;
} catch (PDOException $e) {
	echo "An error occured: ".$e->getMessage();
  echo "<br>Admin account already exists";
}
?>