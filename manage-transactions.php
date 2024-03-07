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

    $userId = $_SESSION["userID"];

    // Create connection
    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

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

    // SQL query to select all transactions
    $sql = "SELECT t.type, IF(t.categoryID IS NOT NULL, c.title, cc.title) AS category, t.comment, t.amount, t.date FROM Transaction as t LEFT JOIN Category AS c ON c.categoryID = t.categoryID LEFT JOIN CustomCategory AS cc ON cc.customCategoryID = t.customCategoryID WHERE t.userID = ?;";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION["userID"]);
    $stmt->execute();
    $result = $stmt->get_result();

    // Start building the table
    $table = '<table>';
    $table .= '<tr><th>Type</th><th>Category</th><th>Comment</th><th>Amount</th><th>Date</th></tr>';

    // Fetch each row and add it to the table
    while ($row = $result->fetch_assoc()) {
        $table .= '<tr>';
        $table .= '<td>' . htmlspecialchars($row['type']) . '</td>';
        $table .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $table .= '<td>' . htmlspecialchars($row['comment']) . '</td>';
        $table .= '<td>' . htmlspecialchars($row['amount']) . '</td>';
        $table .= '<td>' . htmlspecialchars($row['date']) . '</td>';
        $table .= '</tr>';
    }

    // Finish the table
    $table .= '</table>';

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
            .controls {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }

            .transaction-options {
                margin-left: auto;
            }

            .manage-option-container form {
                width: 100%;
            }

            .manage-option-container form .form-group {
                margin-bottom: 20px;
            }

            .manage-option-container form .form-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .manage-option-container form .form-group input, .manage-option-container form .form-group select {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                border: 1px solid #AAAAAA;
                box-sizing: border-box;
                margin-top: 0;
                font-size: 20px;
                font-family: varela round, sans-serif !important;
            }

            .btn-primary {
                padding: 20px 150px;
                font-size: 20px;
                font-weight: bold;
                width: fit-content;
                display: block;
                margin-left: auto; margin-right: auto;
            }

            #deleteTransactionContainer, #editTransactionContainer {
                display: none;
            }

            .transaction-options {
                position: relative;
                border-radius: 20px;
                width: 450px; /* Width of the entire switch */
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 10px; /* Padding inside the switch */
                background-color: #444; /* Background of the switch */
            }

            .transaction-option {
                width: 33.3%;
                text-align: center;
                cursor: pointer;
                z-index: 1;
                border-radius: 20px;
                /* border: red 1px solid; */
                color: white;
                padding: 5px 0;
                transition: background-color 0.15s ease;
            }

            .transaction-option.active {
                background-color: #fff;
                color: #444;
            }

            .filter-section {
                display: flex;
                align-items: center;
                margin-bottom: 50px; 
            }

            .filter-section label {
                font-weight: bold;
                margin-bottom: 5px;
            }

            .filter-section select {
                margin-right: 20px; 
                padding: 5px;
                width: 100%; 
                font-size: 20px;
            }

            #filterButton {
                padding: 10px 50px;
            }

            .filter-group {
                display: flex;
                flex-direction: column;
                flex: 1;
            }

            .filter-group:not(:last-child) {
                margin-right: 20px;
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
                    <div class="controls">
                        <a href="transactions.php" id="backToTransactions">< Back to Transaction History</a>
                        <!-- Transaction Options -->
                        <div class="transaction-options">
                            <div class="transaction-option active" data-target="addTransactionContainer"><i class="fas fa-plus"></i></div>
                            <div class="transaction-option" data-target="deleteTransactionContainer"><i class="fas fa-minus"></i></div>
                            <div class="transaction-option" data-target="editTransactionContainer"><i class="fas fa-edit"></i></div>
                        </div>
                    </div>
                    <!-- Add Transaction -->
                    <div class="manage-option-container" id="addTransactionContainer">
                        <h2>Add Transaction</h2>
                        <form action="add-transaction.php" method="post" id="addTransactionForm">
                            <div class="form-group">
                                <label>Transaction Type</label>
                                <div class="toggle-buttons">
                                    <button type="button" id="in-button" class="toggle-button active" data-type="in">In</button>
                                    <button type="button" id="out-button" class="toggle-button" data-type="out">Out</button>
                                </div>
                                <!-- Hidden input to store the transaction type -->
                                <input type="hidden" id="transaction-type" name="transactionType" value="in" required>
                            </div>
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <!-- Dynamically populated options -->
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['title']); ?>">
                                            <?php echo htmlspecialchars($category['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" id="amount" name="amount" required>
                            </div>

                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <input type="text" id="comment" name="comment">
                            </div>

                            <button type="submit" class="btn-primary">Add Transaction</button>
                        </form>
                    </div>

                    <!-- Delete Transaction -->
                    <div class="manage-option-container" id="deleteTransactionContainer">
                        <h2>Delete Transaction</h2>
                        <form action="" method="get" class="filter-section">
                            <div class="filter-group">
                                <label for="transactionType">Transaction Type</label>
                                <select id="transactionType">
                                    <option value="all">All</option>
                                    <option value="in">In</option>
                                    <option value="out">Out</option>
                                </select>
                            </div>
                                    
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category">
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
                                <label for="month">Month</label>
                                <select id="month">
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

                            <button type="button" class="btn-primary" id="filterButton">Filter</button>
                        </form>
                        <h3>Transactions</h3>
                        <form action="delete-transaction.php" method="post">
                            <?php echo $table; ?>
                        </form>
                    </div>

                    <!-- Edit Transaction -->
                    <div class="manage-option-container" id="editTransactionContainer">
                        <h2>Edit Transaction</h2>
                        <form action="" method="get" class="filter-section">
                            <div class="filter-group">
                                <label for="transactionType">Transaction Type</label>
                                <select id="transactionType">
                                    <option value="all">All</option>
                                    <option value="in">In</option>
                                    <option value="out">Out</option>
                                </select>
                            </div>
                                    
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category">
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
                                <label for="month">Month</label>
                                <select id="month">
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

                            <button type="button" class="btn-primary" id="filterButton">Filter</button>
                        </form>
                        <h3>Transactions</h3>
                        <form action="edit-transaction.php" method="post">
                        
                        </form>
                    </div>
                </div>
            </div>  
        </div>
        <script>
            // Update toggle buttons and hidden input on click (add transaction)
            var toggleButtons = document.querySelectorAll('.toggle-button');
            var hiddenInput = document.getElementById('transaction-type');

            toggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    toggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                    hiddenInput.value = button.getAttribute('data-type');
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
                const optionIndicator = document.createElement('div');
                document.querySelector('.transaction-options').appendChild(optionIndicator);

                const optionButtons = document.querySelectorAll('.transaction-option');
                const containers = document.querySelectorAll('.manage-option-container');

                optionButtons.forEach((button, index) => {
                    button.addEventListener('click', function() {
                        optionButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');

                        // Show the corresponding container
                        containers.forEach(container => {
                            container.style.display = 'none';
                        });

                        const activeContainer = document.getElementById(this.dataset.target);
                        if (activeContainer) {
                            activeContainer.style.display = 'block';
                        }
                    });
                });

                // Initialize the first option as active
                if (optionButtons.length > 0) {
                    optionButtons[0].click();
                }
            });
        </script>
        <script src="script.js"></script>
    </body>
    </html>
