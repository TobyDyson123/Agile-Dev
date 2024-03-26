<?php
session_start();

if (!isset($_SESSION["userID"]) || !isset($_POST['monthlyBudget'])) {
    echo "Invalid request";
    exit;
}

$userId = $_SESSION["userID"];
$monthlyBudget = $_POST['monthlyBudget'];

// Create connection
$dbHost = 'localhost';
$dbUsername = 'root'; 
$dbPassword = ''; 
$dbName = 'agile'; 

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "UPDATE BudgetReminder SET monthlyBudget = ? WHERE userID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("di", $monthlyBudget, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Budget updated successfully";
} else {
    echo "No changes made or update failed";
}

$stmt->close();
$conn->close();
?>
