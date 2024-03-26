<?php
session_start();

// Check if the user is logged in and the required POST data is available
if (!isset($_SESSION["userID"]) || !isset($_POST['categoryTitle']) || !isset($_POST['spendingAmount'])) {
    echo "Invalid request";
    exit;
}

$userId = $_SESSION["userID"];
$categoryTitle = $_POST['categoryTitle'];
$spendingAmount = $_POST['spendingAmount'];

// Database connection parameters
$dbHost = 'localhost'; 
$dbUsername = 'root'; 
$dbPassword = '';
$dbName = 'agile'; 

// Create database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Attempt to update a regular category first
$stmt = $conn->prepare("SELECT categoryID FROM Category WHERE title = ?");
$stmt->bind_param("s", $categoryTitle);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // It's a regular category
    $category = $result->fetch_assoc();
    $categoryId = $category['categoryID'];

    $updateQuery = "UPDATE SpendingGoals SET goalAmount = ? WHERE userID = ? AND categoryID = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("dii", $spendingAmount, $userId, $categoryId);
} else {
    // If not a regular category, check if it's a custom category
    $stmt = $conn->prepare("SELECT customCategoryID FROM CustomCategory WHERE title = ? AND userID = ?");
    $stmt->bind_param("si", $categoryTitle, $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // It's a custom category
        $customCategory = $result->fetch_assoc();
        $customCategoryId = $customCategory['customCategoryID'];

        $updateQuery = "UPDATE SpendingGoals SET goalAmount = ? WHERE userID = ? AND customCategoryID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("dii", $spendingAmount, $userId, $customCategoryId);
    } else {
        echo "Category not found";
        $conn->close();
        exit;
    }
}

// Execute the update
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Spending goal updated successfully";
} else {
    echo "No changes made or update failed";
}

$stmt->close();
$conn->close();
?>
