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
                            <h3>Transaction Notifications</h3>
                            <div class="tooltip-container">
                                <i class="fas fa-question"></i>
                                <div class="tooltip">Get notified when a transaction is added</div>
                            </div>
                            <div class="toggle">(o-)</div>
                        </div>
                        <div id="budgetR" class="option-container">
                            <h3>Budget Reminders</h3>
                            <div class="tooltip-container">
                                <i class="fas fa-question"></i>
                                <div class="tooltip">Get notified when reaching your monthly budget</div>
                            </div>
                            <div class="toggle">(o-)</div>
                        </div>
                        <div id="remindersR" class="option-container">
                            <h3>Spending Reminders</h3>
                            <div class="tooltip-container">
                                <i class="fas fa-question"></i>
                                <div class="tooltip">Get notified when spending goals are exceeded</div>
                            </div>
                            <div class="toggle">(o-)</div>
                        </div>
                    </div>
                </div>
            </div>  
        </div>        
        <script src="script.js"></script>
    </body>
    </html>
