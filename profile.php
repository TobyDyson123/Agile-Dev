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
                        <p><strong>Username:</strong> <?php echo htmlspecialchars($userData['username']); ?></p>
                        <p><strong>Password:</strong> <?php echo htmlspecialchars($userData['password']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['emailAddress']); ?></p>
                        <button>Delete Account</button>
                    </div>

                    <div class="custom-categories">
                        <h2>Custom Categories</h2>
                        <?php foreach ($customCategories as $category): ?>
                            <div class="category">
                                <p><?php echo htmlspecialchars($category['title']); ?></p>
                                <!-- You can use the color for styling or an icon -->
                                <!-- Additional elements like edit and delete buttons go here -->
                            </div>
                        <?php endforeach; ?>
                        <button>Add New Category</button>
                    </div>
                </div>
            </div>  
        </div>        
        <script src="script.js"></script>
    </body>
    </html>
