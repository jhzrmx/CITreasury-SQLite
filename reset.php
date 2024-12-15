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

$import_sql = "
-- Dropping the tables if they already exist
DROP TABLE IF EXISTS `sanctions`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `students`;

-- Recreating the tables

CREATE TABLE `students` (
  `student_id` TEXT NOT NULL,
  `last_name` TEXT NOT NULL,
  `first_name` TEXT NOT NULL,
  `middle_initial` TEXT NOT NULL,
  `year_and_section` TEXT NOT NULL,
  PRIMARY KEY (`student_id`)
);

CREATE TABLE `accounts` (
  `account_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `email` TEXT NOT NULL,
  `password` TEXT NOT NULL,
  `student_id` TEXT NOT NULL,
  `type` TEXT NOT NULL,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
);

CREATE TABLE `events` (
  `event_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `event_name` TEXT NOT NULL,
  `event_description` TEXT NOT NULL,
  `event_target` TEXT NOT NULL,  -- Use TEXT to store a comma-separated list of event targets
  `event_date` DATE NOT NULL,
  `event_fee` INTEGER NOT NULL,
  `sanction_fee` INTEGER NOT NULL
);

CREATE TABLE `registrations` (
  `registration_id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  `event_id` INTEGER NOT NULL,
  `student_id` TEXT NOT NULL,
  `registration_date` DATE NOT NULL,
  `paid_fees` INTEGER NOT NULL,
  `status` TEXT DEFAULT NULL,
  FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE
);

CREATE TABLE `sanctions` (
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
  echo "Database tables have been created.<br><br>";
  $stmt_student = $conn->prepare($sql_student);
  $stmt_student->execute([$sid, $lastname, $firstname, $mi, $yearsec]);
  $stmt_account = $conn->prepare($sql_account);
  $stmt_account->execute([$email, $hash_password, $sid]);
  echo "Admin account created:<br>Email: " . $email . "<br>Password: cit-" . $sid;
} catch (PDOException $e) {
  echo "An error occurred: " . $e->getMessage();
}
?>