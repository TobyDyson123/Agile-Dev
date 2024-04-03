<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["userID"])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

header('Content-Type: application/json');

// Database connection parameters
$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'agile';
$userId = $_SESSION["userID"];

// Create database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';

// Updated SQL query to include category handling
$sql = "SELECT t.transactionID, t.type, 
            IF(t.categoryID IS NOT NULL, c.title, cc.title) AS category, 
            t.comment, t.amount, 
            DATE_FORMAT(t.date, '%d/%m/%Y') AS formatted_date 
            FROM Transaction t 
            LEFT JOIN Category c ON t.categoryID = c.categoryID 
            LEFT JOIN CustomCategory cc ON t.customCategoryID = cc.customCategoryID 
            WHERE t.userID = ?";

$params = [$userId]; // Initial parameters array
$types = 'i'; // Initial parameters type

if ($type !== '' && $type !== 'all') {
    $sql .= " AND t.type = ?";
    $params[] = $type;
    $types .= "s";
}

if ($category !== '' && $category !== 'All') {
    $sql .= " AND (c.title = ? OR cc.title = ?)";
    $params[] = $category;
    $params[] = $category;
    $types .= 'ss'; // Adding two 's' types for category comparison
}

if ($month !== '' && $month !== 'All') {
    $monthNum = date('n', strtotime("$month 1"));
    $sql .= " AND MONTH(t.date) = ?";
    $params[] = $monthNum;
    $types .= "i";
}

$sql .= " ORDER BY t.date DESC;";

$stmt = $conn->prepare($sql);

// Dynamically bind parameters
$bind_names[] = $types;
for ($i = 0; $i < count($params); $i++) {
    $bind_name = 'bind' . $i;
    $$bind_name = $params[$i];
    $bind_names[] = &$$bind_name;
}

call_user_func_array(array($stmt, 'bind_param'), $bind_names);

$stmt->execute();
$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode($transactions);

$stmt->close();
$conn->close();
?>
