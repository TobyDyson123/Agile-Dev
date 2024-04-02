<?php
    session_start(); // Start the session.

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

        // Sanitize user input
        $newEmail = $conn->real_escape_string($_POST['newEmail']);
        $userId = $_SESSION['userID'];

        // Update the database
        $query = "UPDATE User SET email = ? WHERE userID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $newEmail, $userId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Email updated successfully.']);
        } else {
            echo 'Error updating email.';
        }
        $stmt->close();
        $conn->close();
    } else {
        echo 'Invalid request method.';
    }
?>