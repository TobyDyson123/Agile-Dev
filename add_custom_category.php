<?php
session_start();

if (!isset($_SESSION["userID"])) {
    header("location: index.html");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'agile';
    $userId = $_SESSION["userID"];

    $title = $_POST['title'] ?? '';
    $colour = $_POST['colour'] ?? '';

    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO CustomCategory (userID, title, colour) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $title, $colour);
    if ($stmt->execute()) {
        $newCategoryId = $stmt->insert_id; // Get the ID of the newly inserted category
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Category added successfully.', 'newCategory' => ['customCategoryID' => $newCategoryId, 'title' => $title, 'colour' => $colour]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding category.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
