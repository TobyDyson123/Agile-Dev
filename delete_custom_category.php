<?php
session_start();

if(!isset($_SESSION["userID"])){
    header("location: index.html");
    exit;
}

$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'agile';
$userId = $_SESSION["userID"];

$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categoryId = $conn->real_escape_string($_POST['categoryId']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // First, delete related SpendingGoals records
        $spendingQuery = "DELETE FROM SpendingGoals WHERE customCategoryID = ? AND userID = ?";
        $spendingStmt = $conn->prepare($spendingQuery);
        $spendingStmt->bind_param('ii', $categoryId, $userId);
        $spendingStmt->execute();
        $spendingStmt->close();
        
        // Then, delete related Transaction records
        $transactionQuery = "DELETE FROM Transaction WHERE customCategoryID = ? AND userID = ?";
        $transactionStmt = $conn->prepare($transactionQuery);
        $transactionStmt->bind_param('ii', $categoryId, $userId);
        $transactionStmt->execute();
        $transactionStmt->close();

        // Lastly, delete the CustomCategory record
        $query = "DELETE FROM CustomCategory WHERE customCategoryID = ? AND userID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $categoryId, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // If everything went well, commit the transaction
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Category and related data deleted successfully.']);
        } else {
            // If the category wasn't found, rollback the transaction
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'No category found with the given ID.']);
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // If an error occurs, rollback the transaction and send an error message
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error deleting category: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
?>