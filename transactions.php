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
    $sql = "SELECT t.*, COALESCE(c.title, cc.title) AS title, COALESCE(c.colour, cc.colour) AS colour, c.icon FROM Transaction AS t LEFT JOIN Category AS c ON t.categoryID = c.categoryID LEFT JOIN CustomCategory AS cc ON t.customCategoryID = cc.customCategoryID WHERE t.userID = ?";

    // Initialize parameters array with the userID
    $params = array($userId);

    if (!empty($transactionType) && in_array($transactionType, ['in', 'out'])) {
        $sql .= " AND t.type = ?";
        $params[] = $transactionType;
    }

    if (!empty($categoryFilter) && $categoryFilter != 'All') {
        $sql .= " AND COALESCE(c.title, cc.title) = ?";
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
        $sql .= " AND MONTH(t.date) = ? AND YEAR(t.date) = ?";
        $params[] = $selectedMonthNum;
        $params[] = $queryYear;
    }

    $sql .= " ORDER BY t.date DESC;";

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
        <meta name="description" content="transactions page">
        <title>Transactions</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome CDN -->
        <style>
            .transactions {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }

            .main-content {
                padding-left: 50px;
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

            #edit-transaction i {
                display: none;
            }

            #filter-overlay {
                display: none; 
                position: fixed; 
                top: 0; 
                left: 0; 
                width: 100%; 
                height: 100%; 
                background: rgba(0,0,0,0.5); 
                z-index: 9999;
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
                font-family: varela round, sans-serif !important;
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

            @media screen and (max-width: 900px) {
                #edit-transaction i {
                    display: block;
                }

                #edit-transaction span {
                    display: none;
                }

                .filter-form {
                    margin-top: 60px;
                }

                #reset-filters {
                    left: -10px;
                    right: unset;
                    top: -50px;
                }

                .filter-container {
                    width: 60%;
                    padding: 40px;
                }
            }

            @media screen and (max-width: 768px) {
                .transaction-container h2 {
                    font-size: 24px;
                }

                #edit-transaction {
                    top: 15px;
                    right: 20px;
                    padding: 10px 30px;
                }

                .transaction-icon {
                    width: 30px; 
                    height: 30px;
                }

                .transaction-icon i {
                    font-size: 35px;
                }

                .transaction-details h3 {
                    font-size: 20px;
                }

                .transaction-details p {
                    font-size: 16px;
                }

                .transaction-icon {
                    margin-right: 30px;
                }

                .transaction-item .amount {
                    right: 10px;
                    font-size: 16px;
                }

                #filter {
                    margin-left: 10px;
                
                }

                .main-content {
                    padding: 20px;
                }
            }
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
                <a href="profile.php"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
                <a href="transactions.php" class="active"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
                <a href="insights.php"><i class="fas fa-chart-bar"></i> <span>Insights</span></a>
                <a href="reminders.php"><i class="fas fa-bell"></i> <span>Reminders</span></a>
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
                    <div class="transaction-container">
                        <a style="text-decoration: none;" href="manage-transactions.php" class="btn-primary "id="edit-transaction"><span>Manage Transactions</span><i class="fas fa-wrench"></i></a>
                        <h2>Transaction History <i id="filter" class="fas fa-sliders-h"></i></h2>
                        <div class="transactions">
                            <?php if (count($transactions) > 0): ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <div class="transaction-item">
                                        <div class="transaction-icon" style="background-color: <?php echo htmlspecialchars($transaction['colour']); ?>">
                                            <i class="<?php echo htmlspecialchars($transaction['icon']) ? htmlspecialchars($transaction['icon']) : 'fas fa-question'; ?>"></i>
                                        </div>
                                        <div class="transaction-details">
                                            <h3><?php echo htmlspecialchars($transaction['title']);?></h3>
                                            <p><?php echo htmlspecialchars($transaction['comment']); ?></p>
                                            <span class="amount" style="color: <?php echo $transaction['type'] == 'in' ? '#00960F' : '#890901'; ?>">
                                                <?php echo $transaction['type'] == 'in' ? '+' : '-'; ?>£<?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: red;">There are no transactions to display. Please consider making a transaction :D</p>
                            <?php endif; ?>
                        </div>
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
                        <div class="toggle-buttons">
                            <button type="button" id="all-button" class="toggle-button active" data-type="all">All</button>
                            <button type="button" id="in-button" class="toggle-button" data-type="in">In</button>
                            <button type="button" id="out-button" class="toggle-button" data-type="out">Out</button>
                        </div>
                        <!-- Hidden input to store the transaction type -->
                        <input type="hidden" id="transaction-type" name="transactionType" value="all">
                    </div>
                    <div class="filter-group">
                        <label for="category">By Category</label>
                        <select id="category-filter" name="category">
                            <option>All</option>
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
                            <option>All</option>
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
                loadFilterState();
            }

            // Function to close the filter overlay
            function closeFilter() {
                document.getElementById('filter-overlay').style.display = 'none';
            }

            // Save the current state of the filter to local storage
            function saveFilterState() {
                const transactionType = document.getElementById('transaction-type').value;
                const category = document.getElementById('category-filter').value;
                const month = document.getElementById('month-filter').value;
                
                localStorage.setItem('transactionType', transactionType);
                localStorage.setItem('category', category);
                localStorage.setItem('month', month);
            }

            // Load the filter state from local storage
            function loadFilterState() {
                const savedTransactionType = localStorage.getItem('transactionType');
                const savedCategory = localStorage.getItem('category');
                const savedMonth = localStorage.getItem('month');

                if (savedTransactionType) {
                    document.getElementById('transaction-type').value = savedTransactionType;
                    document.querySelectorAll('.toggle-button').forEach(button => {
                        if (button.getAttribute('data-type') === savedTransactionType) {
                            button.classList.add('active');
                        } else {
                            button.classList.remove('active');
                        }
                    });
                }

                if (savedCategory) {
                    document.getElementById('category-filter').value = savedCategory;
                }

                if (savedMonth) {
                    document.getElementById('month-filter').value = savedMonth;
                }
            }

            document.getElementById('filter').addEventListener('click', openFilter);
            document.getElementById('close-filter').addEventListener('click', closeFilter);

            // Update toggle buttons and hidden input on click
            var toggleButtons = document.querySelectorAll('.toggle-button');
            var hiddenInput = document.getElementById('transaction-type');

            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    toggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                    hiddenInput.value = button.getAttribute('data-type');
                    saveFilterState();
                });
            });

            // Reset button functionality
            document.getElementById('reset-filters').addEventListener('click', function() {
                document.querySelector('.filter-form').reset();
                toggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                document.getElementById('all-button').classList.add('active');
                hiddenInput.value = 'all';
                localStorage.removeItem('transactionType');
                localStorage.removeItem('category');
                localStorage.removeItem('month');
                window.location.href = window.location.href.split('?')[0];
            });

            // Add event listener for changes in the category and month filters
            document.getElementById('category-filter').addEventListener('change', saveFilterState);
            document.getElementById('month-filter').addEventListener('change', saveFilterState);

            // Load the filter state when the page loads
            window.onload = loadFilterState;
        </script>
        <script src="script.js"></script>
    </body>
    </html>
