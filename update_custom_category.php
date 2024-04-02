<?php
session_start();

// Basic validation
if (!isset($_SESSION["userID"]) || !isset($_POST['categoryId']) || !isset($_POST['title'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'agile';
$userId = $_SESSION["userID"];
$categoryId = $_POST['categoryId'];
$newTitle = $_POST['title'];

// Database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("UPDATE CustomCategory SET title = ? WHERE customCategoryID = ? AND userID = ?");
$stmt->bind_param("sii", $newTitle, $categoryId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Category updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating category.']);
}

$stmt->close();
$conn->close();
?>
