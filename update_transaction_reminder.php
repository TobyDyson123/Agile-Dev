<?php
session_start(); // Start the session.

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["userID"])){
    echo "Not logged in";
    exit;
}

if(isset($_POST['transactionIsOn'])) {
    $dbHost = 'localhost'; 
    $dbUsername = 'root'; 
    $dbPassword = ''; 
    $dbName = 'agile'; 
    $userId = $_SESSION["userID"];
    $isOn = $_POST['transactionIsOn']; // The value sent from the AJAX request

    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare the SQL query to update the transaction reminder setting
    $sqlUpdate = "UPDATE TransactionNotes SET isOn = ? WHERE userID = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("ii", $isOn, $userId);
    $stmt->execute();

    if($stmt->affected_rows > 0) {
        echo "Update successful";
    } else {
        echo "Update failed";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "No data provided";
}
?>
