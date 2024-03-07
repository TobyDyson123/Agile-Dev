<?php
    session_start(); // Start the session.

    // Check if the user is logged in, if not then redirect to login page
    if(!isset($_SESSION["userID"])){
        header("location: index.html");
        exit;
    }

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

    // Get transactions for the logged in user
    $userId = $_SESSION["userID"];
    $transactionType = isset($_GET['transactionType']) ? $_GET['transactionType'] : '';
    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
    $monthFilter = isset($_GET['month']) ? $_GET['month'] : '';

    // Start building the SQL query
    $sql = "SELECT Transaction.*, Category.title, Category.colour, Category.icon
            FROM Transaction 
            LEFT JOIN Category ON Transaction.categoryID = Category.categoryID
            WHERE Transaction.userID = ?";

    // Initialize parameters array with the userID
    $params = array($userId);

    if (!empty($transactionType) && in_array($transactionType, ['in', 'out'])) {
        $sql .= " AND Transaction.type = ?";
        $params[] = $transactionType;
    }

    if (!empty($categoryFilter) && $categoryFilter != 'All') {
        $sql .= " AND Category.title = ?";
        $params[] = $categoryFilter;
    }

    // Define the current year and month
    $currentYear = date('Y');
    $currentMonth = date('n'); // Numeric representation of the current month (1-12)

    if (!empty($monthFilter) && $monthFilter != 'All') {
        $selectedMonthNum = date('n', strtotime($monthFilter . " 1")); // Numeric representation of the selected month

        // Determine the year for the query
        $queryYear = $selectedMonthNum <= $currentMonth ? $currentYear : $currentYear - 1;

        // Append the month and year condition to the SQL query
        $sql .= " AND MONTH(Transaction.date) = ? AND YEAR(Transaction.date) = ?";
        $params[] = $selectedMonthNum;
        $params[] = $queryYear;
    }

    $stmt = $conn->prepare($sql);
    $types = str_repeat("s", count($params)); // s for string types
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    $stmt->close();

    // Prepare the SQL query to fetch categories and custom categories
    $sqlCategories = "
        SELECT title FROM Category
        UNION ALL
        SELECT title FROM CustomCategory WHERE userID = ?
    ";
    $stmt = $conn->prepare($sqlCategories);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultCategories = $stmt->get_result();

    $categories = [];
    while($row = $resultCategories->fetch_assoc()) {
        $categories[] = $row;
    }

    $stmt->close();
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Transactions</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome CDN -->
        <style>
            
        </style>
    </head>
    <body>
        <button class="hamburger-menu">
            <i class="fas fa-bars"></i>
        </button>

        <div class="sidebar">
            <button class="close-sidebar">
                <i class="fas fa-times"></i>
            </button>
            <div class="sidebar-content">
                <a href="#"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
                <a href="#" class="active"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
                <a href="#"><i class="fas fa-chart-bar"></i> <span>Insights</span></a>
                <a href="#"><i class="fas fa-bell"></i> <span>Reminders</span></a>
            </div>
            <div class="logout-section">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>

        <div class="content">
            <div class="title">
                <h1>Transactions</h1>
            </div>
            <div class="content-container">
                <div class="main-content">
                    <p>beans</p>
                </div>
            </div>  
        </div>        
        <script src="script.js"></script>
    </body>
    </html>
