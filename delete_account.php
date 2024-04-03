<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'User is not logged in.']);
    exit;
}

// Database credentials
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = ''; 
$dbName = 'agile'; 

// Create connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection to database failed: ' . $conn->connect_error]);
    exit;
}

$userID = $_SESSION['userID'];

// Begin transaction
$conn->begin_transaction();

try {
    // Delete related records from dependency tables first
    $tables = ['TransactionNotes', 'BudgetReminder', 'SpendingGoals', 'Transaction', 'CustomCategory'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE userID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $stmt->close();
    }

    // Now, delete the user account
    $stmt = $conn->prepare("DELETE FROM User WHERE userID = ?");
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $stmt->close();

    // If everything is fine, commit the transaction
    $conn->commit();
    
    // Destroy the session
    session_destroy();

    // Return success message
    echo json_encode(['success' => true, 'message' => 'Account and all related data deleted successfully.']);

} catch (Exception $e) {
    // An error occurred, roll back the transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting account data.']);
}

$conn->close();
?>
