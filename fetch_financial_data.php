<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION["userID"])) {
    echo json_encode(["error" => "User not logged in"]);
    exit;
}

$userId = $_SESSION["userID"];
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

// Query to fetch total income, total outcome per month
$sql = "SELECT YEAR(date) as year, MONTH(date) as month, 
               SUM(CASE WHEN type = 'in' THEN amount ELSE 0 END) as totalIncome,
               SUM(CASE WHEN type = 'out' THEN amount ELSE 0 END) as totalOutcome
        FROM Transaction
        WHERE userID = ?
        GROUP BY YEAR(date), MONTH(date)
        ORDER BY YEAR(date), MONTH(date);";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()) {
    $netSpend = $row["totalIncome"] - $row["totalOutcome"];
    $data[] = [
        "year" => $row["year"],
        "month" => $row["month"],
        "totalIncome" => $row["totalIncome"],
        "totalOutcome" => $row["totalOutcome"],
        "netSpend" => $netSpend
    ];
}

$stmt->close();
$conn->close();

echo json_encode($data);
?>