<?php
$dbHost = 'localhost'; // or your database host
$dbUsername = 'root'; // or your database username
$dbPassword = ''; // or your database password
$dbName = 'agile'; // your database name

// Create connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve date range from the request
$startDate = isset($_GET['start']) ? $_GET['start'] : '1970-01-01';
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d'); // Default to current date if not provided

$query = "SELECT 
            COALESCE(c.title, cc.title) AS title, 
            SUM(t.amount) AS total_expenditure, 
            COALESCE(c.colour, cc.colour) AS colour
          FROM Transaction t
          LEFT JOIN Category c ON t.categoryID = c.categoryID
          LEFT JOIN CustomCategory cc ON t.customCategoryID = cc.customCategoryID
          WHERE t.type = 'out'
          AND t.date >= ? AND t.date <= ?
          GROUP BY title, colour";

// Prepare the statement to avoid SQL injection
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
?>

