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
    
    // Fetch user details
    $userID = $_SESSION["userID"];
    $userQuery = "SELECT username, password, emailAddress FROM User WHERE userID = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userData = $userResult->fetch_assoc();

    // Fetch custom categories
    $categoriesQuery = "SELECT title, colour FROM CustomCategory WHERE userID = ?";
    $stmt = $conn->prepare($categoriesQuery);
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $categoriesResult = $stmt->get_result();
    $customCategories = $categoriesResult->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profile</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Varela+Round&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> <!-- Font Awesome CDN -->
        <style>
            .user-details, .custom-categories {
                background-color: #fff; /* or any color you prefer */
                border-radius: 10px;
                padding: 20px;
            }

            .user-details p, .custom-categories .category {
                display: flex;
                align-items: center;
            }

            .user-details p {
                margin: 15px 0;
            }

            .user-details button, .custom-categories button {
                border: none;
                border-radius: 25px;
                padding: 12px 30px;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
            }

            .user-details button {
                margin-left: auto;
                background-color: #666; /* Red color for the delete button */
                color: white;
            }

            .custom-categories button {
                background-color: #007BFF; /* Blue color for the add button */
                color: white;
            }

            .delete-button {
                background-color: #FF0000 !important; /* Red color for the delete button */
                color: white;
                margin-top: 10px;
            }

            .icon-button {
                background: none;
                border: none;
                cursor: pointer;
                margin-left: 10px;
                padding: 0 !important;
                background-color: rgba(0, 0, 0, 0) !important;
                color: #666 !important;
                font-size: 20px !important;
            }

            .category-color {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: inline-block;
                margin-right: 10px;
            }

            .category {
                border-bottom: 1px solid #eaeaea;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            .user-details strong {
                margin-right: 10px;
            }

            .details-wrapper {
                display: flex;
                align-items: center;
            }

            .custom-categories-header {
                display: flex;
                align-items: center;
            }

            .custom-categories-header button {
                margin-left: auto;
            }

            .categories-list {
                display: grid; 
                grid-template-columns: 1fr 1fr;
                gap: 10px 30px;
                margin-top: 15px;
            }

            .category-buttons {
                margin-left: auto;
            }

            .category-buttons button {
                padding: 0;
                background-color: rgba(0, 0, 0, 0);
                color: #666;
                font-size: 20px;
            }

            #delete-category {
                color: #FF0000 !important;
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
                <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> <span>Profile</span></a>
                <a href="transactions.php"><i class="fas fa-exchange-alt"></i> <span>Transactions</span></a>
                <a href="insights.php"><i class="fas fa-chart-bar"></i> <span>Insights</span></a>
                <a href="reminders.php"><i class="fas fa-bell"></i> <span>Reminders</span></a>
            </div>
            <div class="logout-section">
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
            </div>
        </div>

        <div class="content">
            <div class="title">
                <h1>Profile</h1>
            </div>
            <div class="content-container">
                <div class="main-content">
                    <div class="user-details">
                        <h2>User Details</h2>
                        <div class="details-wrapper">
                            <p><strong>Username: </strong> <?php echo htmlspecialchars($userData['username']); ?></p>
                            <button class="icon-button"><i class="fas fa-edit"></i></button>
                        </div>
                        <div class="details-wrapper">
                            <p><strong>Password: </strong> <?php echo htmlspecialchars($userData['password']); ?></p>
                            <button class="icon-button"><i class="fas fa-edit"></i></button>
                        </div>
                        <div class="details-wrapper">
                            <p><strong>Email: </strong> <?php echo htmlspecialchars($userData['emailAddress']); ?></p>
                            <button class="icon-button"><i class="fas fa-edit"></i></button>
                        </div>  
                        <button class="delete-button">Delete Account</button>
                    </div>

                    <div class="custom-categories">
                        <div class="custom-categories-header">
                            <h2>Custom Categories</h2>
                            <button>Add New Category</button>
                        </div>
                        <div class="categories-list">
                            <?php foreach ($customCategories as $category): ?>
                                <div class="category">
                                    <span class="category-color" style="background-color: <?php echo htmlspecialchars($category['colour']); ?>"></span>
                                    <?php echo htmlspecialchars($category['title']); ?>
                                    <div class="category-buttons">
                                        <button class="icon-button" id="edit-category"><i class="fas fa-edit"></i></button>
                                        <button class="icon-button" id="delete-category"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>  
        </div>        
        <script src="script.js"></script>
    </body>
    </html>
