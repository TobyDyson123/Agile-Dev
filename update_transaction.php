<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["userID"])) {
    echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
    exit;
}

$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'agile';
$userId = $_SESSION["userID"];

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$transactionId = isset($_POST['transactionId']) ? $conn->real_escape_string($_POST['transactionId']) : '';
$type = isset($_POST['type']) ? $conn->real_escape_string($_POST['type']) : '';
$categoryName = isset($_POST['category']) ? $conn->real_escape_string($_POST['category']) : '';
$amount = isset($_POST['amount']) ? $conn->real_escape_string($_POST['amount']) : '';
$comment = isset($_POST['comment']) ? $conn->real_escape_string($_POST['comment']) : '';

// Determine if the category is a predefined category or a custom category
$categorySql = "SELECT categoryID FROM Category WHERE title = ? UNION SELECT customCategoryID FROM CustomCategory WHERE title = ? AND userID = ?";
$categoryID = null;
$customCategoryID = null;

if ($categoryStmt = $conn->prepare($categorySql)) {
    $categoryStmt->bind_param("ssi", $categoryName, $categoryName, $userId);
    if ($categoryStmt->execute()) {
        $result = $categoryStmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (isset($row['categoryID'])) {
                $categoryID = $row['categoryID'];
            } else if (isset($row['customCategoryID'])) {
                $customCategoryID = $row['customCategoryID'];
            }
        }
        $categoryStmt->close();
    }
}

if ($categoryID || $customCategoryID) {
    // Prepare SQL statement to update the transaction
    $sql = "UPDATE Transaction SET type = ?, amount = ?, comment = ?, categoryID = ?, customCategoryID = ? WHERE transactionID = ? AND userID = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('sdsiiii', $type, $amount, $comment, $categoryID, $customCategoryID, $transactionId, $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Transaction updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update transaction: ' . $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Category not found.']);
}

$conn->close();
?>
