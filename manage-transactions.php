<?php
    session_start(); // Start the session.

    if(!isset($_SESSION["userID"])){
        header("location: index.html");
        exit;
    }

    $dbHost = 'localhost';
    $dbUsername = 'root';
    $dbPassword = '';
    $dbName = 'agile';
    $userId = $_SESSION["userID"];

    $conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addTransaction'])) {
        // Retrieve form data
        $transactionType = $_POST['transactionType'] ?? '';
        $categoryName = $_POST['category'] ?? '';
        $amount = $_POST['amount'] ?? 0.00;
        $comment = $_POST['comment'] ?? '';
        $date = date('Y-m-d'); 
    
        // Fetch the categoryID from the Category table
        $categorySql = "SELECT categoryID FROM Category WHERE title = ?";
        $categoryId = null;
    
        if ($categoryStmt = $conn->prepare($categorySql)) {
            $categoryStmt->bind_param("s", $categoryName);
            if ($categoryStmt->execute()) {
                $categoryStmt->bind_result($categoryId);
                $categoryStmt->fetch();
                $categoryStmt->close();
            } else {
                echo "<p>Error: Could not execute the query: $categoryStmt->error </p>";
            }
        } else {
            echo "<p>Error: Could not prepare the query: $conn->error </p>";
        }
    
        if ($categoryId) {
            // Prepare an SQL query to insert the transaction
            $insertSql = "INSERT INTO Transaction (userID, categoryID, type, amount, comment, date) VALUES (?, ?, ?, ?, ?, ?)";
    
            if ($stmt = $conn->prepare($insertSql)) {
                // Bind variables to the prepared statement as parameters
                $stmt->bind_param("iissss", $userId, $categoryId, $transactionType, $amount, $comment, $date);
    
                // Attempt to execute the prepared statement
                if ($stmt->execute()) {
                    
                } else {
                    echo "<p>Error: Could not execute the query: $stmt->error </p>";
                }
    
                // Close statement
                $stmt->close();
            } else {
                echo "<p>Error: Could not prepare the query: $conn->error </p>";
            }
        } else {
            echo "<p>Error: Category not found.</p>";
        }
    }

    if (isset($_POST['delete_transactions']) && !empty($_POST['transaction_ids'])) {
        $transactionIds = $_POST['transaction_ids'];
        $placeholders = implode(',', array_fill(0, count($transactionIds), '?'));
        $types = str_repeat('i', count($transactionIds));
        $sql = "DELETE FROM Transaction WHERE transactionID IN ($placeholders) AND userID = ?";
        $stmt = $conn->prepare($sql);
        $params = array_merge($transactionIds, array($userId));
        $stmt->bind_param($types . 'i', ...$params);
        $stmt->execute();
        $stmt->close();
    }

    $sqlCategories = "SELECT title FROM Category UNION ALL SELECT title FROM CustomCategory WHERE userID = ?";
    $stmt = $conn->prepare($sqlCategories);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultCategories = $stmt->get_result();
    $categories = [];
    while($row = $resultCategories->fetch_assoc()) {
        $categories[] = $row;
    }
    $stmt->close();

    // Initialize filter variables with defaults or values from GET request
    $transactionType = isset($_GET['transactionType']) && $_GET['transactionType'] !== 'all' ? $_GET['transactionType'] : null;
    $category = isset($_GET['category']) && $_GET['category'] !== 'All' ? $_GET['category'] : null;
    $month = isset($_GET['month']) && $_GET['month'] !== 'All' ? $_GET['month'] : null;

    // Start building the SQL query
    $sql = "SELECT t.transactionID, t.type, 
            IF(t.categoryID IS NOT NULL, c.title, cc.title) AS category, 
            t.comment, t.amount, 
            DATE_FORMAT(t.date, '%d/%m/%Y') AS formatted_date 
            FROM Transaction t 
            LEFT JOIN Category c ON t.categoryID = c.categoryID 
            LEFT JOIN CustomCategory cc ON t.customCategoryID = cc.customCategoryID 
            WHERE t.userID = ?";

    // Prepare the initial parameters array and types
    $params = [$userId]; // $userId needs to be defined and contain the logged-in user's ID
    $types = 'i'; // 'i' for integer type of userID

    // Apply transaction type filter
    if ($transactionType) {
        $sql .= " AND t.type = ?";
        $params[] = $transactionType;
        $types .= 's'; // 's' for string type of transactionType
    }

    // Apply category filter
    if ($category) {
        $sql .= " AND (c.title = ? OR cc.title = ?)";
        $params[] = $category;
        $params[] = $category;
        $types .= 'ss'; // Adding two 's' types for category
    }

    // Apply month filter
    if ($month) {
        $monthNum = date('n', strtotime("$month 1")); // Converts month name to month number
        $sql .= " AND MONTH(t.date) = ?";
        $params[] = $monthNum;
        $types .= 'i'; // 'i' for integer type of month
    }

    $sql .= " ORDER BY t.date DESC;";

    // Prepare the statement
    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    $bind_names[] = $types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }

    // Call 'bind_param' dynamically
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    // Execute the statement
    $stmt->execute();
    $result = $stmt->get_result();
    $deleteTable = '<table><tr><th style="width: 10%">Select</th><th style="width: 10%">Type</th><th style="width: 25%">Category</th><th style="width: 25%">Comment</th><th style="width: 15%">Amount</th><th style="width: 15%">Date</th></tr>';
    $editTable = '<table><tr><th style="width: 10%">Edit</th><th style="width: 10%">Type</th><th style="width: 25%">Category</th><th style="width: 25%">Comment</th><th style="width: 15%">Amount</th><th style="width: 15%">Date</th></tr>';

    while ($row = $result->fetch_assoc()) {
        // Capitalize the first letter of the type
        $type = ucfirst($row['type']);

        // Format the amount as money (assuming it's in GBP)
        $amount = '£' . number_format($row['amount'], 2);

        // Format the date as DD/MM/YY
        $date = date('d/m/y', strtotime($row['formatted_date']));

        $deleteTable .= '<tr>';
        $deleteTable .= '<td><input type="checkbox" name="transaction_ids[]" value="' . htmlspecialchars($row['transactionID']) . '"></td>';
        $deleteTable .= '<td>' . htmlspecialchars($type) . '</td>';
        $deleteTable .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $deleteTable .= '<td>' . htmlspecialchars($row['comment']) . '</td>';
        $deleteTable .= '<td>' . htmlspecialchars($amount) . '</td>';
        $deleteTable .= '<td>' . htmlspecialchars($date) . '</td>';
        $deleteTable .= '</tr>';

        $editTable .= '<tr>';
        $editTable .= '<td><input type="radio" name="transaction_id" value="' . htmlspecialchars($row['transactionID']) . '"></td>';
        $editTable .= '<td>' . htmlspecialchars($type) . '</td>';
        $editTable .= '<td>' . htmlspecialchars($row['category']) . '</td>';
        $editTable .= '<td>' . htmlspecialchars($row['comment']) . '</td>';
        $editTable .= '<td>' . htmlspecialchars($amount) . '</td>';
        $editTable .= '<td>' . htmlspecialchars($date) . '</td>';
        $editTable .= '</tr>';
    }

    if ($result->num_rows == 0) {
        $noResultsMessage = "<tr><td colspan='6' style='text-align:center;'>No transactions found.</td></tr>";
    } else {
        $noResultsMessage = ""; // No need to display a message if there are results
    }

    $deleteTable .= $noResultsMessage . '</table>';
    $editTable .= $noResultsMessage . '</table>';   

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
                width: 450px; 
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

            table {
                width: 100%;
                border-collapse: collapse;
            }

            table th {
                text-align: left;
                background-color: #D2D2D2;
            }

            table tr:nth-child(odd) {
                background-color: #F2F2F2;
            }

            table tr:nth-child(even) {
                background-color: #fff;
            }

            table th, table td {
                padding: 10px;
            }

            .btn-danger {
                padding: 10px 30px;
                font-size: 16px;
                font-weight: bold;
                /* display: block; */
                background-color: #FF0000;
                border-radius: 20px;
                border: none;
                color: white;
                cursor: pointer;
            }

            .table-actions {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }

            .table-actions input[type="checkbox"] {
                margin-right: 30px;
            }

            .table-actions label {
                margin-right: 10px;
            }

            #resetFiltersButton {
                padding: 10px;
                background-color: rgba(0, 0, 0, 0);
                color: #FF0000;
                margin-left: 10px;
            }
            
            #resetFiltersButtonEdit {
                padding: 10px;
                background-color: rgba(0, 0, 0, 0);
                color: #FF0000;
                margin-left: 10px;
            }

            .hidden {
                display: none;
            }

            #customCategoryColor {
                padding: 0px;
                width: 60px;
                height: 60px;
            }

            #customCategoryExample {
                display: flex;
                align-items: center;
                margin-left: 100px;
            }

            #customCategoryExample i {
                font-size: 20px;
                background-color: aquamarine;
                padding: 10px;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                text-align: center;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            #customCategoryExample span {
                margin-right: 10px;
            }

            .color-picker-container {
                display: flex;
                align-items: center;
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
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="addTransactionForm">
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
                                    <option value="-CREATE">- CREATE CUSTOM CATEGORY -</option>
                                </select>
                            </div>

                            <!-- Custom Category Fields -->
                            <div id="customCategoryFields" class="hidden">
                                <div class="form-group">
                                    <label for="customCategoryTitle">Custom Category Title</label>
                                    <input type="text" id="customCategoryTitle" name="customCategoryTitle">
                                </div>
                                <div class="form-group">
                                    <label for="customCategoryColor">Custom Category Color</label>
                                    <div class="color-picker-container">
                                        <input type="color" id="customCategoryColor" name="customCategoryColor">
                                        <div id="customCategoryExample">
                                            <span>Preview:</span><i class="fas fa-question" id="exampleIcon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="amount">Amount</label>
                                <input type="number" id="amount" name="amount" required>
                            </div>

                            <div class="form-group">
                                <label for="comment">Comment</label>
                                <input type="text" id="comment" name="comment">
                            </div>

                            <button type="submit" name="addTransaction" class="btn-primary">Add Transaction</button>
                        </form>
                    </div>

                    <!-- Delete Transaction -->
                    <div class="manage-option-container" id="deleteTransactionContainer">
                        <h2>Delete Transaction</h2>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="filter-section">
                            <div class="filter-group">
                                <label for="transactionType">Transaction Type</label>
                                <select id="transactionType" name="transactionType">
                                    <option value="all">All</option>
                                    <option value="in">In</option>
                                    <option value="out">Out</option>
                                </select>
                            </div>
                                    
                            <div class="filter-group">
                                <label for="category">Category</label>
                                <select id="category" name="category">
                                    <option>All</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['title']); ?>">
                                            <?php echo htmlspecialchars($category['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label for="month">Month</label>
                                <select id="month" name="month">
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

                            <button type="submit" class="btn-primary" id="filterButton">Filter</button>
                            <button type="submit" class="btn-primary" id="resetFiltersButton">Reset Filters</button>
                        </form>
                        <h3>Transactions</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="table-actions">
                                <label for="select_all_outside">Select All</label><input type="checkbox" id="select_all_outside"></input>
                                <button type="submit" class="btn-danger" name="delete_transactions">Delete Selected</button>
                            </div>
                            <?php echo $deleteTable; ?>
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
                            <button type="submit" class="btn-primary" id="resetFiltersButtonEdit">Reset Filters</button>
                        </form>
                        <h3>Transactions</h3>
                        <form action="edit-transaction.php" method="post">
                            <?php echo $editTable; ?>
                        </form>
                    </div>
                </div>
            </div>  
        </div>
        <script>
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
                const optionButtons = document.querySelectorAll('.transaction-option');
                const containers = document.querySelectorAll('.manage-option-container');
                const LAST_ACTIVE_OPTION = 'lastActiveOption';

                // Function to save the active option to local storage
                function saveActiveOption(option) {
                    localStorage.setItem(LAST_ACTIVE_OPTION, option);
                }

                // Function to get the active option from local storage
                function getSavedActiveOption() {
                    return localStorage.getItem(LAST_ACTIVE_OPTION);
                }

                optionButtons.forEach((button) => {
                    button.addEventListener('click', function() {
                        optionButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        saveActiveOption(this.dataset.target); // Save the current active option

                        containers.forEach(container => {
                            container.style.display = 'none';
                        });

                        const activeContainer = document.getElementById(this.dataset.target);
                        if (activeContainer) {
                            activeContainer.style.display = 'block';
                        }
                    });
                });

                // Load the saved option or default to the first one
                const savedOption = getSavedActiveOption();
                const defaultOption = optionButtons[0].dataset.target;
                const optionToActivate = savedOption || defaultOption;
                const buttonToActivate = Array.from(optionButtons).find(button => button.dataset.target === optionToActivate);
                buttonToActivate.click();

                // Select DOM elements for filters
                const transactionTypeSelect = document.getElementById('transactionType');
                const categorySelect = document.getElementById('category');
                const monthSelect = document.getElementById('month');

                // Save the current state of filters to local storage
                function saveFilterStates() {
                    localStorage.setItem('filterTransactionType', transactionTypeSelect.value);
                    localStorage.setItem('filterCategory', categorySelect.value);
                    localStorage.setItem('filterMonth', monthSelect.value);
                }

                // Load the saved filter states or default to 'All'
                function loadFilterStates() {
                    transactionTypeSelect.value = localStorage.getItem('filterTransactionType') || 'all';
                    categorySelect.value = localStorage.getItem('filterCategory') || 'All';
                    monthSelect.value = localStorage.getItem('filterMonth') || 'All';
                }

                // Reset filters to their default states
                function resetFilters() {
                    localStorage.removeItem('filterTransactionType');
                    localStorage.removeItem('filterCategory');
                    localStorage.removeItem('filterMonth');
                    loadFilterStates(); // Reset select elements to their default values
                }

                // Event listener for the Filter button
                document.getElementById('filterButton').addEventListener('click', function() {
                    saveFilterStates(); // Save state when Filter button is clicked
                });

                // Event listener for the Reset Filters button
                document.getElementById('resetFiltersButton').addEventListener('click', resetFilters);

                loadFilterStates(); // Load filter states when the page loads
            });

            // Select all checkboxes
            document.addEventListener('DOMContentLoaded', function () {
                var selectAllCheckboxOutside = document.getElementById('select_all_outside');
                selectAllCheckboxOutside.addEventListener('change', function () {
                    var checkboxes = document.querySelectorAll('input[type="checkbox"][name="transaction_ids[]"]');
                    checkboxes.forEach((checkbox) => {
                        checkbox.checked = selectAllCheckboxOutside.checked;
                    });
                });
            });

            const categorySelect = document.getElementById('category');
            const customCategoryFields = document.getElementById('customCategoryFields');

            categorySelect.addEventListener('change', () => {
                if (categorySelect.value === '-CREATE') {
                    customCategoryFields.classList.remove('hidden');
                } else {
                    customCategoryFields.classList.add('hidden');
                }
            });

            // Colour picker example update
            const colorPicker = document.getElementById('customCategoryColor');
            const exampleIcon = document.getElementById('exampleIcon');

            colorPicker.addEventListener('input', function() {
                exampleIcon.style.backgroundColor = colorPicker.value;
            });
        </script>
        <script src="script.js"></script>
    </body>
    </html>
