<?php
session_start(); // Start the session.

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // username and password sent from form
    $myusername = mysqli_real_escape_string($conn, $_POST['username']);
    $mypassword = $_POST['password']; // Password as plain text; in development only.

    $sql = "SELECT userID, password FROM User WHERE username = '$myusername'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        // output data of each row
        $row = $result->fetch_assoc();
        if ($mypassword == $row['password']) { // Directly comparing the plaintext passwords.
            // Password is correct, so start a new session
            $_SESSION['login_user'] = $myusername; // store the username
            $_SESSION['userID'] = $row['userID']; // store the userID
            // Redirect user to transactions.php
            header("location: transactions.php");
            exit;
        } else {
            // If the password is not correct, send back to the login page with an error
            header("location: index.html?error=Invalid credentials");
            exit;
        }
    } else {
        // If the result is not exactly one row, then the username does not exist
        header("location: index.html?error=Username does not exist");
        exit;
    }
}
$conn->close();
?>
