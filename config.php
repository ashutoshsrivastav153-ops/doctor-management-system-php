<?php
// config.php - Database Connection
$servername = "localhost";
$username   = "root";
$password   = "Ashu2004";
$dbname     = "doctor_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;color:red;padding:20px;'>
        ❌ Connection Failed: " . $conn->connect_error . "
        <br><small>Check your MySQL server is running and credentials are correct.</small>
    </div>");
}

$conn->set_charset("utf8mb4");
?>