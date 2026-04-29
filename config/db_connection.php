<?php
$servername = "localhost";
$username = "root";
$password = "";

$dbname = "library_db";

// Avoid mysqli throwing uncaught exceptions so we can show a friendly setup message.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database on first run if it does not exist yet.
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Database setup error: " . $conn->error);
}

if (!$conn->select_db($dbname)) {
    die("Cannot select database '$dbname'. Please check your MySQL configuration.");
}

$conn->set_charset("utf8mb4");