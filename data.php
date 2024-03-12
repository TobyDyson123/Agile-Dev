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

$query = "SELECT 
            COALESCE(c.title, cc.title) AS title, 
            SUM(t.amount) AS total_expenditure, 
            COALESCE(c.colour, cc.colour) AS colour
          FROM Transaction t
          LEFT JOIN Category c ON t.categoryID = c.categoryID
          LEFT JOIN CustomCategory cc ON t.customCategoryID = cc.customCategoryID
          WHERE t.type = 'out'
          GROUP BY title, colour";

$result = $conn->query($query);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
?>

