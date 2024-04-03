<?php
session_start();

if (!isset($_SESSION["userID"])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (isset($_GET['transactionId'])) {
    $transactionId = $_GET['transactionId'];
    // Database connection variables
    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'agile';
    
    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    
    // Check connection
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]);
        exit;
    }
    
    $sql = "SELECT t.transactionID, COALESCE(c.title, cc.title) AS category, t.amount, t.comment, t.type FROM Transaction AS t LEFT JOIN Category AS c ON t.categoryID = c.categoryID LEFT JOIN CustomCategory AS cc ON t.customCategoryID = cc.customCategoryID WHERE t.transactionID = ? AND t.userID = ?;";
    $stmt = $conn->prepare($sql);
    $userId = $_SESSION["userID"];
    $stmt->bind_param("ii", $transactionId, $userId);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode($data);
        } else {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error executing query']);
    }
    
    $stmt->close();
    $conn->close();
}
?>
