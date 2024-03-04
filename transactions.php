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
    $sql = "SELECT Transaction.*, Category.title, Category.colour, Category.icon
            FROM Transaction 
            LEFT JOIN Category ON Transaction.categoryID = Category.categoryID
            WHERE Transaction.userID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
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
        <title>Login</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome CDN -->
        <style>
            .history-container {
                width: 90%;
                padding: 30px;
                margin: 30px auto;
                border-radius: 25px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                background-color: white;
                position: relative;
            }

            .transactions {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .transaction-item {
                /* background-color: #BDCAF2; */
                position: relative;
                display: flex;
                align-items: center;
            }

            .transaction-item h3 {
                margin: 10px 0;
                font-size: 24px;
            }

            .transaction-item p {
                margin: 0;
                font-size: 18px;
            }

            .transaction-item .amount {
                position: absolute;
                right: 50px;
                bottom: 50%;
                transform: translateY(50%);
                font-weight: bold;
            }

            .transaction-icon {
                background-color: blue;
                padding: 20px;
                border-radius: 50%;
                width: 50px; 
                height: 50px;
                display: flex;
                justify-content: center;
                align-items: center;
                margin-right: 50px;
            }

            .transaction-icon i {
                color: white;
                font-size: 50px;
            }

            #filter {
                margin-left: 20px;
                cursor: pointer;
                color: #555555;
            }

            #edit-transaction {
                position: absolute;
                top: 30px;
                right: 60px;
                cursor: pointer;
                width: auto;
                padding: 15px 40px;
                font-size: 20px;
                font-weight: bold;
            }

            #filter-overlay {
                display: none; 
                position: fixed; 
                top: 0; 
                left: 0; 
                width: 100%; 
                height: 100%; 
                background: rgba(0,0,0,0.5); 
                z-index: 1000;
            }

            .filter-container {
                background: #fff; 
                width: 50%; 
                padding: 60px; 
                position: absolute; 
                top: 50%; 
                left: 50%; 
                transform: translate(-50%, -50%); 
                border-radius: 30px; 
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            }

            .filter-container h2 {
                text-align: center;
            }

            #close-filter {
                cursor: pointer; 
                position: absolute; 
                top: 10px; 
                left: 20px;
                color: #FF0000;
                font-size: 70px;
                font-weight: bold; 
            }

            .filter-form {
                margin-top: 20px;
            }

            .filter-group {
                margin-bottom: 20px;
            }

            .filter-group label {
                display: block;
                margin-bottom: 10px;
                font-weight: bold;
                position: relative;
            }

            #category-filter, #month-filter {
                width: 100%;
                padding: 10px;
                border-radius: 5px;
                border: 1px solid #ccc;
                font-size: 20px;
            }

            #apply-filter {
                font-size: 20px;
                font-weight: bold;
            }

            #reset-filters {
                padding: 10px;
                background-color: rgba(0, 0, 0, 0);
                color: #FF0000;
                border: none;
                cursor: pointer;
                font-size: 20px;
                position: absolute;
                right: 0;
                top: -10px;
                font-weight: bold;
                font-family: valera round, sans-serif;
            }

            .switch {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 34px;
            }

            .switch input { 
                opacity: 0;
                width: 0;
                height: 0;
            }

            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #00FF49;
                -webkit-transition: .4s;
                transition: .4s;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 26px;
                width: 26px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                -webkit-transition: .4s;
                transition: .4s;
            }

            input:checked + .slider {
                background-color: #FF0000;
            }

            input:focus + .slider {
                box-shadow: 0 0 1px #2196F3;
            }

            input:checked + .slider:before {
                -webkit-transform: translateX(26px);
                -ms-transform: translateX(26px);
                transform: translateX(26px);
            }

            .slider.round {
                border-radius: 34px;
            }

            .slider.round:before {
                border-radius: 50%;
            }

            .toggle-container {
                display: flex;
                align-items: center;
            }
            
            .toggle-label {
                margin-bottom: 10px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>

        <div class="sidebar">
            <div class="sidebar-content">
                <a href="#"><i class="fas fa-user-circle"></i> Profile</a>
                <a href="#" class="active"><i class="fas fa-exchange-alt"></i> Transactions</a>
                <a href="#"><i class="fas fa-chart-bar"></i> Insights</a>
                <a href="#"><i class="fas fa-bell"></i> Reminders</a>
            </div>
            <div class="logout-section">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="content">
            <div class="title">
                <h1>Transactions</h1>
            </div>
            <div class="content-container">
                <div class="history-container">
                    <button class="btn-primary "id="edit-transaction">Edit Transaction</button>
                    <h2>Transaction History <i id="filter" class="fas fa-sliders-h"></i></h2>
                    <div class="transactions">
                        <?php foreach($transactions as $transaction): ?>
                            <div class="transaction-item">
                                <div class="transaction-icon" style="background-color: <?php echo htmlspecialchars($transaction['colour']); ?>">
                                    <i class="<?php echo htmlspecialchars($transaction['icon']); ?>"></i>
                                </div>
                                <div class="transaction-details">
                                    <h3><?php echo htmlspecialchars($transaction['title']); ?></h3>
                                    <p><?php echo htmlspecialchars($transaction['comment']); ?></p>
                                    <span class="amount" style="color: <?php echo $transaction['type'] == 'in' ? '#00960F' : '#890901'; ?>">
                                        <?php echo $transaction['type'] == 'in' ? '+' : '-'; ?>£<?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?>
                                    </span>

                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>  
        </div>
        
        <!-- Filter Overlay -->
        <div id="filter-overlay">
            <div class="filter-container">
                <span id="close-filter">&times;</span>
                <h2>Filters</h2>
                <form action="transactions.php" method="get" class="filter-form">
                    <div class="filter-group">
                        <label>By Transaction Direction <button id="reset-filters" type="button">Reset Filters</button></label>
                        <div class="toggle-container">
                            <span class="toggle-label" style="margin-right: 10px;">In</span>
                            <label class="switch" for="type">
                                <input type="toggle" name="type">
                                <span class="slider round"></span>
                            </label>
                            <span class="toggle-label" style="margin-left: 10px;">Out</span>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="category">By Category</label>
                        <select id="category-filter" name="category">
                            <!-- Dynamically populated options -->
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['title']); ?>">
                                    <?php echo htmlspecialchars($category['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="month">By Month</label>
                        <select name="month" id="month-filter">
                            <option>January</option>
                            <option>February</option>
                            <option>March</option>
                            <option>April</option>
                            <option>May</option>
                            <option>June</option>
                            <option>July</option>
                            <option>August</option>
                            <option>September</option>
                            <option>October</option>
                            <option>November</option>
                            <option>December</option>
                        </select>
                    </div>
                    <button type="submit" id="apply-filter" class="btn-primary" type="button">Apply Filters</button>
                </form>
            </div>
        </div>

        <script>
            // Function to open the filter overlay
            function openFilter() {
                document.getElementById('filter-overlay').style.display = 'block';
            }

            // Function to close the filter overlay
            function closeFilter() {
                document.getElementById('filter-overlay').style.display = 'none';
            }

            // Event listener for the filter icon
            document.getElementById('filter').addEventListener('click', openFilter);

            // Event listener for the close button
            document.getElementById('close-filter').addEventListener('click', closeFilter);
        </script>


    </body>
    </html>
