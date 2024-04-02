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
    $categoriesQuery = "SELECT title, colour, customCategoryID FROM CustomCategory WHERE userID = ?";
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

            .delete-category {
                color: #FF0000 !important;
            }

            /* The Modal (background) */
            .modal {
                display: none; /* Hidden by default */
                position: fixed; /* Stay in place */ 
                z-index: 9999; /* Sit on top */
                left: 0;
                top: 0;
                width: 100%; /* Full width */
                height: 100%; /* Full height */
                overflow: auto; /* Enable scroll if needed */
                background-color: rgb(0,0,0); /* Fallback color */
                background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
                justify-content: center;
                align-items: center;
            }

            /* Modal Content/Box */
            .modal-content {
                background-color: #fefefe;
                margin: 0px auto;
                padding: 50px;
                border: 1px solid #888;
                border-radius: 25px;
                width: 60%; /* Could be more or less, depending on screen size */
            }

            .modal-content h2 {
                text-align: center;
            }

            .modal-content input {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border-radius: 5px;
                border: 1px solid #AAAAAA;
                box-sizing: border-box;
                margin-top: 0;
            }

            .modal-content button {
                font-size: 20px;
                font-weight: bold;
                padding: 15px 40px;
            }

            .modal-content label {
                margin-bottom: 5px;
            }

            .modal-content input[type="color"] {
                padding: 0;
                width: 40px;
                height: 40px;
                display: block;
                margin-top: 10px;
            }

            .color-picker-container {
                display: flex;
                align-items: center;
            }

            /* The Close Button */
            .close {
                color: #ff0000;
                float: left;
                font-size: 40px;
                font-weight: bold;
            }

            .close:hover, .close:focus {
                color: #ff9999;
                text-decoration: none;
                cursor: pointer;
            }

            #customCategoryExample span {
                margin-right: 10px;
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

        <!-- Username Change Modal -->
        <div id="usernameModal" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <span id="close-username" class="close">&times;</span>
                <h2>Change Username</h2>
                <form id="usernameForm">
                    <label for="newUsername">New Username</label>
                    <input type="text" id="newUsername" name="newUsername" required>
                    
                    <label for="reNewUsername">Re-enter New Username</label>
                    <input type="text" id="reNewUsername" name="reNewUsername" required>
                    
                    <button class="btn-primary" type="submit">Submit Username</button>
                </form>
            </div>
        </div>

        <!-- Password Change Modal -->
        <div id="passwordModal" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <span id="close-password" class="close">&times;</span>
                <h2>Change Password</h2>
                <form id="passwordForm">
                    <label for="newUsername">New Password</label>
                    <input type="text" id="newPassword" name="newPassword" required>
                    
                    <label for="reNewPassword">Re-enter New Password</label>
                    <input type="text" id="reNewPassword" name="reNewPassword" required>
                    
                    <button class="btn-primary" type="submit">Submit Password</button>
                </form>
            </div>
        </div>

        <!-- Email Change Modal -->
        <div id="emailModal" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <span id="close-email" class="close">&times;</span>
                <h2>Change Email</h2>
                <form id="EmailForm">
                    <label for="newEmail">New Email</label>
                    <input type="text" id="newEmail" name="newEmail" required>
                    
                    <label for="reNewEmail">Re-enter New Email</label>
                    <input type="text" id="reNewEmail" name="reNewEmail" required>
                    
                    <button class="btn-primary" type="submit">Submit Email</button>
                </form>
            </div>
        </div>

        <!-- Add Custom Category Modal -->
        <div id="addCategoryModal" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <span id="close-addCategory" class="close">&times;</span>
                <h2>Add Custom Category</h2>
                <form id="addCategoryForm">
                    <label for="newAddCategory">Category Title</label>
                    <input type="text" id="newAddCategory" name="newAddCategory" required>
                    
                    <label for="color">Colour</label>
                    <div class="color-picker-container">
                        <input type="color" id="color" name="color" required>
                        <div id="customCategoryExample">
                            <span>Preview:</span><i class="fas fa-question" id="exampleIcon"></i>
                        </div>
                    </div>
                    
                    <button class="btn-primary" type="submit">Create Category</button>
                </form>
            </div>
        </div>

        <!-- Edit Custom Category Modal -->
        <div id="editCategoryModal" class="modal">
            <!-- Modal content -->
            <div class="modal-content">
                <span id="close-editCategory" class="close">&times;</span>
                <h2>Edit Category</h2>
                <form id="editCategoryForm">
                    <input type="hidden" id="editCategoryId" name="editCategoryId">
                    <label for="editCategoryTitle">Category Title</label>
                    <input type="text" id="editCategoryTitle" name="editCategoryTitle" required>
                    <button class="btn-primary" type="submit">Save Changes</button>
                </form>
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
                            <button class="icon-button" id="username-edit-btn"><i class="fas fa-edit"></i></button>
                        </div>
                        <div class="details-wrapper">
                            <p><strong>Password: </strong> <?php echo htmlspecialchars($userData['password']); ?></p>
                            <button class="icon-button" id="password-edit-btn"><i class="fas fa-edit"></i></button>
                        </div>
                        <div class="details-wrapper">
                            <p><strong>Email: </strong> <?php echo htmlspecialchars($userData['emailAddress']); ?></p>
                            <button class="icon-button" id="email-edit-btn"><i class="fas fa-edit"></i></button>
                        </div>  
                        <button class="delete-button">Delete Account</button>
                    </div>

                    <div class="custom-categories">
                        <div class="custom-categories-header">
                            <h2>Custom Categories</h2>
                            <button id="add-category-btn">Add New Category</button>
                        </div>
                        <div class="categories-list">
                            <?php foreach ($customCategories as $category): ?>
                                <div class="category">
                                    <span class="category-color" style="background-color: <?php echo htmlspecialchars($category['colour']); ?>"></span>
                                    <?php echo htmlspecialchars($category['title']); ?>
                                    <div class="category-buttons">
                                        <button class="icon-button edit-category"><i class="fas fa-edit"></i></button>
                                        <button class="icon-button delete-category" data-category-id="<?php echo $category['customCategoryID']; ?>"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>  
        </div>       
        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // Function to handle the category deletion
                function deleteCategory(categoryId, categoryElement) {
                    // AJAX call to server-side PHP script for deletion
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "delete_custom_category.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onreadystatechange = function() {
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                            try {
                                var response = JSON.parse(this.responseText);
                                // Rest of the success code...
                            } catch (e) {
                                console.error("Parsing error:", e);
                                console.error(this.responseText);
                                // Handle parsing error here
                            }

                            if (response.success) {
                                // Remove the category element from the UI
                                categoryElement.remove();
                                alert(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    };
                    xhr.send("categoryId=" + encodeURIComponent(categoryId));
                }

                // Attach event listeners to all delete buttons
                document.querySelectorAll('.delete-category').forEach(function(button, index) {
                    button.addEventListener('click', function() {
                        var categoryId = this.getAttribute('data-category-id');
                        var categoryElement = this.closest('.category');
                        if (confirm('Are you sure you want to delete this category?')) {
                            deleteCategory(categoryId, categoryElement);
                        }
                    });
                });

                // Attach event listeners to all edit buttons
                document.querySelectorAll('.edit-category').forEach(function(button) {
                    button.addEventListener('click', function() {
                        var categoryId = this.getAttribute('data-category-id');
                        var categoryTitle = this.closest('.category').querySelector('span').nextSibling.textContent.trim();

                        // Fill the modal with the current category's details
                        document.getElementById('editCategoryId').value = categoryId;
                        document.getElementById('editCategoryTitle').value = categoryTitle;

                        // Show the modal
                        editCategoryModal.style.display = "flex";
                    });
                });

                // Colour picker example update
                const colorPicker = document.getElementById('color');
                const exampleIcon = document.getElementById('exampleIcon');

                colorPicker.addEventListener('input', function() {
                    exampleIcon.style.backgroundColor = colorPicker.value;
                });
            });

            var usernameModal = document.getElementById("usernameModal");
            var passwordModal = document.getElementById("passwordModal");
            var emailModal = document.getElementById("emailModal");
            var addCategoryModal = document.getElementById("addCategoryModal");
            var editCategoryModal = document.getElementById("editCategoryModal");

            var usernameBtn = document.getElementById("username-edit-btn");
            var passwordBtn = document.getElementById("password-edit-btn");
            var emailBtn = document.getElementById("email-edit-btn");
            var addCategoryBtn = document.getElementById("add-category-btn");

            var closeUsername = document.getElementById("close-username");            
            var closePassword = document.getElementById("close-password");            
            var closeEmail = document.getElementById("close-email");
            var closeAddCategory = document.getElementById("close-addCategory");
            var closeEditCategory = document.getElementById("close-editCategory");       

            // When the user clicks the button, open the modal 
            usernameBtn.onclick = function() {
                usernameModal.style.display = "flex";
            }

            passwordBtn.onclick = function() {
                passwordModal.style.display = "flex";
            }

            emailBtn.onclick = function() {
                emailModal.style.display = "flex";
            }

            addCategoryBtn.onclick = function() {
                addCategoryModal.style.display = "flex";
            }


            // When the user clicks on <span> (x), close the modal
            closeUsername.onclick = function() {
                usernameModal.style.display = "none";
            }

            closePassword.onclick = function() {
                passwordModal.style.display = "none";
            }

            closeEmail.onclick = function() {
                emailModal.style.display = "none";
            }

            closeAddCategory.onclick = function() {
                addCategoryModal.style.display = "none";
            }

            closeEditCategory.onclick = function() {
                editCategoryModal.style.display = "none";
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == usernameModal) {
                    usernameModal.style.display = "none";
                } else if (event.target == passwordModal) {
                    passwordModal.style.display = "none";
                } else if (event.target == emailModal) {
                    emailModal.style.display = "none";
                } else if (event.target == addCategoryModal) {
                    addCategoryModal.style.display = "none";
                } else if (event.target == editCategoryModal) {
                    editCategoryModal.style.display = "none";
                }
            }

            // Handle the form submission for username change
            document.getElementById('usernameForm').onsubmit = function(e) {
                e.preventDefault();
                var newUsername = document.getElementById('newUsername').value;
                var reNewUsername = document.getElementById('reNewUsername').value;

                // Validate the new usernames match and are not empty
                if(newUsername && newUsername === reNewUsername) {
                    // Proceed with the AJAX call to update the username
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "update_username.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                    xhr.onreadystatechange = function() {
                        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                            var response = JSON.parse(this.responseText);

                            if(response.success) {
                                // Update UI with the new username or close the modal
                                document.getElementById("usernameModal").style.display = "none";
                                // Update the username display on the page
                                var usernameDisplay = document.querySelector('.details-wrapper > p > strong').nextSibling;
                                if (usernameDisplay.nodeType === Node.TEXT_NODE) {
                                    usernameDisplay.nodeValue = ` ${newUsername}`;
                                }
                            } else {
                                // Handle failure
                                alert("Username update failed: " + response.message);
                            }
                        }
                    };

                    xhr.send("newUsername=" + encodeURIComponent(newUsername));
                } else {
                    // Usernames do not match or are empty, handle the validation error
                    alert("The usernames do not match or are empty.");
                }
            };

            // // Handle the form submission for password change
            // document.getElementById('passwordForm').onsubmit = function(e) {
            //     e.preventDefault();
            //     var newUsername = document.getElementById('newPassword').value;
            //     var reNewUsername = document.getElementById('reNewPassword').value;

            //     // Validate the new passwords match and are not empty
            //     if(newPassword && newPassword === reNewPassword) {
            //         // Proceed with the AJAX call to update the password
            //         var xhr = new XMLHttpRequest();
            //         xhr.open("POST", "update_password.php", true);
            //         xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            //         xhr.onreadystatechange = function() {
            //             if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            //                 var response = JSON.parse(this.responseText);

            //                 if(response.success) {
            //                     // Update UI with the new password or close the modal
            //                     document.getElementById("passwordModal").style.display = "none";
            //                     // Update the password display on the page
            //                     var passwordDisplay = document.querySelector('.details-wrapper > p > strong').nextSibling;
            //                     if (passwordDisplay.nodeType === Node.TEXT_NODE) {
            //                         passwordDisplay.nodeValue = ` ${newPassword}`;
            //                     }
            //                 } else {
            //                     // Handle failure
            //                     alert("Password update failed: " + response.message);
            //                 }
            //             }
            //         };

            //         xhr.send("newPassword=" + encodeURIComponent(newPassword));
            //     } else {
            //         // Passwords do not match or are empty, handle the validation error
            //         alert("The passwords do not match or are empty.");
            //     }
            // };

            // // Handle the form submission for email change
            // document.getElementById('emailForm').onsubmit = function(e) {
            //     e.preventDefault();
            //     var newEmail = document.getElementById('newEmail').value;
            //     var reNewEmail = document.getElementById('reNewEmail').value;

            //     // Validate the new emails match and are not empty
            //     if(newEmail && newEmail === reNewEmail) {
            //         // Proceed with the AJAX call to update the email
            //         var xhr = new XMLHttpRequest();
            //         xhr.open("POST", "update_email.php", true);
            //         xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            //         xhr.onreadystatechange = function() {
            //             if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            //                 var response = JSON.parse(this.responseText);

            //                 if(response.success) {
            //                     // Update UI with the new email or close the modal
            //                     document.getElementById("emailModal").style.display = "none";
            //                     // Update the email display on the page
            //                     var emailDisplay = document.querySelector('.details-wrapper > p > strong').nextSibling;
            //                     if (emailDisplay.nodeType === Node.TEXT_NODE) {
            //                         emailDisplay.nodeValue = ` ${newEmail}`;
            //                     }
            //                 } else {
            //                     // Handle failure
            //                     alert("Email update failed: " + response.message);
            //                 }
            //             }
            //         };

            //         xhr.send("newEmail=" + encodeURIComponent(newEmail));
            //     } else {
            //         // Emails do not match or are empty, handle the validation error
            //         alert("The emails do not match or are empty.");
            //     }
            // };

            // Handle the form submission for adding a new category
            document.getElementById('addCategoryForm').onsubmit = function(e) {
                e.preventDefault();
                var newCategoryTitle = document.getElementById('newAddCategory').value;
                var newCategoryColor = document.getElementById('color').value;

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "add_custom_category.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Append the new category to the categories list in the UI
                            var categoriesList = document.querySelector('.categories-list');
                            var newCategoryHTML = `
                                <div class="category">
                                    <span class="category-color" style="background-color: ${response.newCategory.colour};"></span>
                                    ${response.newCategory.title}
                                    <div class="category-buttons">
                                        <button class="icon-button" id="edit-category"><i class="fas fa-edit"></i></button>
                                        <button class="icon-button delete-category" data-category-id="${response.newCategory.customCategoryID}"><i class="fas fa-trash-alt"></i></button>
                                    </div>
                                </div>
                            `;
                            categoriesList.innerHTML += newCategoryHTML;

                            // Close the modal and reset the form
                            addCategoryModal.style.display = "none";
                            document.getElementById('addCategoryForm').reset();

                            alert(response.message);
                        } else {
                            alert(response.message);
                        }
                    }
                };
                xhr.send(`title=${encodeURIComponent(newCategoryTitle)}&colour=${encodeURIComponent(newCategoryColor)}`);
            };

            // Handle the form submission for editing a category
            document.getElementById('editCategoryForm').onsubmit = function(e) {
                e.preventDefault();
                var categoryId = document.getElementById('editCategoryId').value;
                var newTitle = document.getElementById('editCategoryTitle').value;

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "update_custom_category.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function() {
                    if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                        var response = JSON.parse(this.responseText);
                        if (response.success) {
                            // Update UI accordingly...
                            alert(response.message);
                            // Reload or update the page content to reflect the changes
                            window.location.reload();
                        } else {
                            alert(response.message);
                        }
                    }
                };
                xhr.send(`categoryId=${encodeURIComponent(categoryId)}&title=${encodeURIComponent(newTitle)}`);
            };

        </script> 
        <script src="script.js"></script>
    </body>
    </html>
