<?php
# Default SQLite database connection
$conn = null;

try {
    $conn = new PDO("sqlite:citreasury.db");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    $response = [];
    $response["status"] = "error";
    $response["message"] = "Connection Failed";
    $response["details"] = $e->getMessage();
    echo json_encode($response);
    exit();
}
?>