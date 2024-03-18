<?php
// Start the session
session_start();

$dbHost = 'localhost'; // or your database host
$dbUsername = 'root'; // or your database username
$dbPassword = ''; // or your database password
$dbName = 'agile'; // your database name

// Create connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["userID"])){
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$userId = $_SESSION["userID"]; // Assign the user ID from session

// Prepare the SQL query to fetch categories and custom categories
$sqlCategories = "
    SELECT title FROM Category
    UNION ALL
    SELECT title FROM CustomCategory WHERE userID = ?
";
$stmt = $conn->prepare($sqlCategories);
$stmt->bind_param("i", $userId);
$stmt->execute();
$resultCategories = $stmt->get_result();

$categories = [];
while($row = $resultCategories->fetch_assoc()) {
    $categories[] = $row['title']; // Changed to store only the title
}

$stmt->close();
$conn->close();

// Return the categories as JSON
header('Content-Type: application/json');
echo json_encode($categories);
?>