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

    // Fetch transaction notes setting
    $sqlTransactionNotes = "SELECT isOn FROM TransactionNotes WHERE userID = ?";
    $stmt = $conn->prepare($sqlTransactionNotes);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultTransactionNotes = $stmt->get_result();

    $transactionIsOn = 'off';
    if($row = $resultTransactionNotes->fetch_assoc()) {
        $transactionIsOn = $row['isOn'] ? 'on' : 'off';
    }

    $stmt->close();

    // Fetch budget notes setting
    $sqlTransactionNotes = "SELECT isOn FROM BudgetReminder WHERE userID = ?";
    $stmt = $conn->prepare($sqlTransactionNotes);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $resultTransactionNotes = $stmt->get_result();

    $budgetIsOn = 'off';
    if($row = $resultTransactionNotes->fetch_assoc()) {
        $budgetIsOn = $row['isOn'] ? 'on' : 'off';
    }

    $stmt->close();

    // Fetch spending goals setting for categories and custom categories
    $sqlSpendingGoals = "
        SELECT c.title, IFNULL(s.isOn, 0) as isOn
        FROM Category c
        LEFT JOIN SpendingGoals s ON c.categoryID = s.categoryID AND s.userID = ?
        UNION ALL
        SELECT cc.title, IFNULL(s.isOn, 0) as isOn
        FROM CustomCategory cc
        LEFT JOIN SpendingGoals s ON cc.customCategoryID = s.customCategoryID AND s.userID = ?
        ";
    $stmt = $conn->prepare($sqlSpendingGoals);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $resultSpendingGoals = $stmt->get_result();

    $spendingGoals = [];
    while($row = $resultSpendingGoals->fetch_assoc()) {
        $spendingGoals[$row['title']] = $row['isOn'] ? 'on' : 'off';
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

            .form-container {
                background-color: rgba(242, 242, 242, 1);
                border-radius:25px;
                padding:30px 50px;
            }

            .option-container {
                padding: 20px 0;
            }

            .option-container input, .option-container select {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                border: 1px solid #AAAAAA;
                box-sizing: border-box;
                margin-top: 10px;
                font-size: 20px;
            }

            .option-title-container {
                display: flex;
                align-items: center;
            }

            .option-container:not(:last-child) {
                border-bottom: 2px solid black;
            }

            .option-container i {
                font-size: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #757575;
                color: white;
                border-radius: 50%;
                padding: 10px;
                width: 25px;
                height: 25px;
            }

            .option-container .toggle {
                margin-left: auto;
            }

            .tooltip-container .tooltip {
                background-color: #424242; 
                color: white; 
                padding: 8px 16px; 
                border-radius: 6px; 
                font-size: 16px; 
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2); 
                position: absolute;
                z-index: 100; 
                white-space: nowrap; 
                transition: opacity 0.3s; 
                visibility: hidden; 
                opacity: 0; 
                bottom: 130%;
                left: 50%;
                transform: translateX(-50%);
                display: block;
            }

            .tooltip-container .tooltip::after {
                content: " ";
                position: absolute;
                top: 100%; /* At the bottom of the tooltip */
                left: 51%;
                transform: translateX(-50%);
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: #424242 transparent transparent transparent;
            }

            .tooltip-container {
                position: relative;
                margin-left: 10px;
            }

            .tooltip-container:hover .tooltip {
                visibility: visible; 
                opacity: 1; 
            }
            
            .option-container button {
                width: fit-content;
                font-size: 20px;
            }
                
            .option-heading-wrapper {
                display: flex;
                align-items: center;
            }
            .option-heading-wrapper span {
                display: none;
                font-size: 16px;
                color: #666;
                font-style: italic;
            }

            @media screen and (max-width: 1000px) {
                .option-container .toggle {
                    margin-left: 0;
                    margin-bottom: 20px;
                }

                .option-title-container {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .tooltip-container {
                    display: none;
                }

                .option-title-container span {
                    display: block;
                }

                .option-heading-wrapper {
                    flex-direction: column;
                    align-items: flex-start;
                }

                .option-title-container h3 {
                    margin-bottom: 5px;
                }

                .option-container button {
                    margin-top: 20px;
                }
            }

            @media screen and (max-width: 768px) {
                .form-container {
                    padding: 20px;
                }

                .main-content h2 {
                    text-align: center;
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
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
                <a href="insights.php"><i class="fas fa-chart-bar"></i> <span>Insights</span></a>
                <a href="reminders.php" class="active"><i class="fas fa-bell"></i> <span>Reminders</span></a>
            </div>
            <div class="logout-section">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>

        <div class="content">
            <div class="title">
                <h1>Reminders</h1>
            </div>
            <div class="content-container">
                <div class="main-content">
                <h2>Reminder settings</h2>
                    <div class="form-container">
                        <div id="transactionN" class="option-container">
                            <div class="option-title-container">
                                <div class="option-heading-wrapper">
                                    <h3>Transaction Notifications</h3>
                                    <span>Get notified when a transaction is added</span>
                                    <div class="tooltip-container">
                                        <i class="fas fa-question"></i>
                                        <div class="tooltip">Get notified when a transaction is added</div>
                                    </div>
                                </div>
                                <div class="toggle">
                                    <button type="button" id="transaction-off-button" class="transaction toggle-button" data-type="off">Off</button>
                                    <button type="button" id="transaction-on-button" class="transaction toggle-button" data-type="on">On</button>
                                </div>
                            </div>
                        </div>
                        <div id="budgetR" class="option-container">
                            <div class="option-title-container">
                                <div class="option-heading-wrapper">
                                    <h3>Monthly Budget Reminders</h3>
                                    <span>Get notified when reaching your monthly budget</span>
                                    <div class="tooltip-container">
                                        <i class="fas fa-question"></i>
                                        <div class="tooltip">Get notified when reaching your monthly budget</div>
                                    </div>
                                </div>
                                <div class="toggle">
                                    <button type="button" id="budget-off-button" class="budget toggle-button" data-type="off">Off</button>
                                    <button type="button" id="budget-on-button" class="budget toggle-button" data-type="on">On</button>
                                </div>
                            </div>
                            <input type="number" id="budget-amount" placeholder="Enter your monthly budget">
                            <button type="button" class="btn-primary" id="update-budget-amount">Update Budget</button>
                        </div>
                        <div id="remindersR" class="option-container">
                            <div class="option-title-container">
                                <div class="option-heading-wrapper">
                                    <h3>Monthly Spending Reminders</h3>
                                    <span>Get notified when spending goals are exceeded</span>
                                    <div class="tooltip-container">
                                        <i class="fas fa-question"></i>
                                        <div class="tooltip">Get notified when spending goals are exceeded</div>
                                    </div>
                                </div>
                                <div class="toggle">
                                    <button type="button" id="spending-off-button" class="spending toggle-button active" data-type="off">Off</button>
                                    <button type="button" id="spending-on-button" class="spending toggle-button" data-type="on">On</button>
                                </div>
                            </div>
                            <select id="category-selector" onchange="updateSpendingToggle(this.value)">
                                <!-- Dynamically populated options -->
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars($category['title']); ?>"
                                            data-is-on="<?php echo $spendingGoals[$category['title']]; ?>">
                                        <?php echo htmlspecialchars($category['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" id="spending-amount" placeholder="Enter your spending goal">
                            <button type="button" class="btn-primary" id="update-spending-amount">Update Goal</button>
                        </div>
                    </div>
                </div>
            </div>  
        </div>
        <script>
            function updateSpendingToggle(categoryTitle) {
                // Find the selected category's isOn status
                var option = document.querySelector('#category-selector option[value="' + categoryTitle + '"]');
                var isOnStatus = option.getAttribute('data-is-on');

                // Update the active class based on the isOn status
                var spendingButtonOn = document.getElementById('spending-on-button');
                var spendingButtonOff = document.getElementById('spending-off-button');

                if(isOnStatus === 'on') {
                    spendingButtonOn.classList.add('active');
                    spendingButtonOff.classList.remove('active');
                } else {
                    spendingButtonOff.classList.add('active');
                    spendingButtonOn.classList.remove('active');
                }
            }

            // Set the active class of transaction remidners based on the database value
            window.onload = function() {
                var transactionIsOn = "<?php echo $transactionIsOn; ?>";
                var budgetIsOn = "<?php echo $budgetIsOn; ?>";
                var transactionButtonOn = document.getElementById('transaction-on-button');
                var transactionButtonOff = document.getElementById('transaction-off-button');
                var budgetButtonOn = document.getElementById('budget-on-button');
                var budgetButtonOff = document.getElementById('budget-off-button');

                if(transactionIsOn === 'on') {
                    transactionButtonOn.classList.add('active');
                    transactionButtonOff.classList.remove('active');
                } else {
                    transactionButtonOff.classList.add('active');
                    transactionButtonOn.classList.remove('active');
                }

                if(budgetIsOn === 'on') {
                    budgetButtonOn.classList.add('active');
                    budgetButtonOff.classList.remove('active');
                } else {
                    budgetButtonOff.classList.add('active');
                    budgetButtonOn.classList.remove('active');
                }

                // Update spending toggle buttons based on the selected category
                updateSpendingToggle(document.getElementById('category-selector').value);
            };

            // Update toggle buttons and hidden input on click
            var toggleButtons = document.querySelectorAll('.toggle-button');
            var transactionToggleButtons = document.querySelectorAll('.transaction');
            var budgetToggleButtons = document.querySelectorAll('.budget');
            var spendingToggleButtons = document.querySelectorAll('.spending');

            transactionToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    transactionToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                });
            });
            budgetToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    budgetToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                });
            });
            spendingToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    spendingToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                });
            });

            // Update toggle buttons and hidden input on click for transaction reminders
            transactionToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var isOnValue = button.getAttribute('data-type') === 'on' ? 1 : 0; // Convert 'on'/'off' to 1/0
                    transactionToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                    
                    // Perform an AJAX request to update the setting in the database
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'update_transaction_reminder.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        // Handle the response here
                        console.log(this.responseText);
                    };
                    xhr.send('transactionIsOn=' + isOnValue);
                });
            });

            // Update toggle buttons and hidden input on click for budget reminders
            budgetToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var isOnValue = button.getAttribute('data-type') === 'on' ? 1 : 0; // Convert 'on'/'off' to 1/0
                    budgetToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                    
                    // Perform an AJAX request to update the setting in the database
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'update_budget_reminder.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        // Handle the response here
                        console.log(this.responseText);
                    };
                    xhr.send('budgetIsOn=' + isOnValue);
                });
            });
            
            // Update the spending goal when a toggle button is clicked
            spendingToggleButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var categoryTitle = document.getElementById('category-selector').value;
                    var isOnValue = button.getAttribute('data-type') === 'on' ? 1 : 0;
                    spendingToggleButtons.forEach(function(btn) { btn.classList.remove('active'); });
                    button.classList.add('active');
                    
                    // Perform an AJAX request to update the setting in the database
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', 'update_spending_goal.php', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        // Handle the response here
                        console.log(this.responseText);
                        // Update the option data attribute
                        var option = document.querySelector('#category-selector option[value="' + categoryTitle + '"]');
                        option.setAttribute('data-is-on', button.getAttribute('data-type'));
                    };
                    xhr.send('categoryTitle=' + encodeURIComponent(categoryTitle) + '&isOn=' + isOnValue);
                });
            });

            document.getElementById('update-budget-amount').addEventListener('click', function() {
                var monthlyBudget = document.getElementById('budget-amount').value;
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_budget_amount.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    console.log(this.responseText);
                };
                xhr.send('monthlyBudget=' + monthlyBudget);
            });

            document.getElementById('update-spending-amount').addEventListener('click', function() {
                var categoryTitle = document.getElementById('category-selector').value;
                var spendingAmount = document.getElementById('spending-amount').value;
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_spending_goal_amount.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    console.log(this.responseText);
                };
                xhr.send('categoryTitle=' + encodeURIComponent(categoryTitle) + '&spendingAmount=' + spendingAmount);
            });
        </script>    
        <script src="script.js"></script>
    </body>
    </html>
