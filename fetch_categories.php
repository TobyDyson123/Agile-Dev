<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["userID"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$dbHost = 'localhost';
$dbUsername = 'root';
$dbPassword = '';
$dbName = 'agile';

// Create connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check for the selected category in the request
$categoryTitle = $_GET['category'] ?? '';

// SQL to fetch total income and outcome per category for each month
$sql = "
    SELECT 
        MONTH(t.date) as month,
        YEAR(t.date) as year,
        SUM(CASE WHEN t.type = 'in' THEN t.amount ELSE 0 END) as totalIncome,
        SUM(CASE WHEN t.type = 'out' THEN t.amount ELSE 0 END) as totalOutcome
    FROM Transaction t
    LEFT JOIN Category c ON t.categoryID = c.categoryID
    LEFT JOIN CustomCategory cc ON t.customCategoryID = cc.customCategoryID
    WHERE c.title = ? OR cc.title = ?
    GROUP BY YEAR(t.date), MONTH(t.date)
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $categoryTitle, $categoryTitle);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'month' => $row['month'],
        'year' => $row['year'],
        'totalIncome' => $row['totalIncome'],
        'totalOutcome' => $row['totalOutcome'],
    ];
}

$stmt->close();
$conn->close();

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($data);
?>