<?php
session_start(); // Start the session.

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["userID"])) {
    echo "Not logged in";
    exit;
}

if (isset($_POST['categoryTitle']) && isset($_POST['isOn'])) {
    $categoryTitle = $_POST['categoryTitle'];
    $isOn = $_POST['isOn'];
    $userId = $_SESSION["userID"];

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

    // First, determine if this is a custom or regular category
    $stmt = $conn->prepare("SELECT categoryID FROM Category WHERE title = ?");
    $stmt->bind_param("s", $categoryTitle);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // It's a regular category
        $category = $result->fetch_assoc();
        $categoryId = $category['categoryID'];
        $updateQuery = "UPDATE SpendingGoals SET isOn = ? WHERE userID = ? AND categoryID = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iii", $isOn, $userId, $categoryId);
    } else {
        // It's a custom category, get its ID
        $stmt = $conn->prepare("SELECT customCategoryID FROM CustomCategory WHERE title = ? AND userID = ?");
        $stmt->bind_param("si", $categoryTitle, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            // Custom category exists
            $customCategory = $result->fetch_assoc();
            $customCategoryId = $customCategory['customCategoryID'];
            $updateQuery = "UPDATE SpendingGoals SET isOn = ? WHERE userID = ? AND customCategoryID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("iii", $isOn, $userId, $customCategoryId);
        } else {
            echo "Category not found";
            exit;
        }
    }

    // Execute the update
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Update successful";
    } else {
        echo "No changes made or update failed";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request";
}
?>
