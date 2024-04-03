CREATE SCHEMA IF NOT EXISTS agile;

SET FOREIGN_KEY_CHECKS = 0;

USE agile;

-- Drop tables if they already exist
DROP TABLE IF EXISTS TransactionNotes;
DROP TABLE IF EXISTS BudgetReminder;
DROP TABLE IF EXISTS SpendingGoals;
DROP TABLE IF EXISTS Transaction;
DROP TABLE IF EXISTS CustomCategory;
DROP TABLE IF EXISTS Category;
DROP TABLE IF EXISTS User;

-- Create tables
CREATE TABLE User (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    emailAddress VARCHAR(255) NOT NULL
);

CREATE TABLE Category (
    categoryID INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    colour VARCHAR(255) NOT NULL,
    icon VARCHAR(255) NOT NULL
);

CREATE TABLE CustomCategory (
    customCategoryID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    colour VARCHAR(255) NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID)
);

CREATE TABLE Transaction (
    transactionID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    categoryID INT,
    customCategoryID INT,
    comment TEXT,
    type ENUM('in', 'out') NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    date DATE NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID),
    FOREIGN KEY (categoryID) REFERENCES Category(categoryID),
    FOREIGN KEY (customCategoryID) REFERENCES CustomCategory(customCategoryID)
);

CREATE TABLE SpendingGoals (
    spendingGoalsID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    categoryID INT,
    customCategoryID INT,
    isOn TINYINT(1) NOT NULL DEFAULT 0,
    goalAmount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID),
    FOREIGN KEY (categoryID) REFERENCES Category(categoryID),
    FOREIGN KEY (customCategoryID) REFERENCES CustomCategory(customCategoryID)
);

CREATE TABLE BudgetReminder (
    budgetReminderID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
	isOn TINYINT(1) NOT NULL DEFAULT 0,
    monthlyBudget DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID)
);

CREATE TABLE TransactionNotes (
    transactionNotesID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    isOn TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES User(userID)
);

DELIMITER $$

CREATE TRIGGER NewTransactionNotes
AFTER INSERT ON User FOR EACH ROW
BEGIN
    INSERT INTO TransactionNotes (userID) VALUES (NEW.userID);
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER NewBudgetReminder
AFTER INSERT ON User FOR EACH ROW
BEGIN
    INSERT INTO BudgetReminder (userID) VALUES (NEW.userID);
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER NewSpendingGoals
AFTER INSERT ON User FOR EACH ROW
BEGIN
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 1);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 2);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 3);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 4);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 5);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 6);
    INSERT INTO SpendingGoals (userID, categoryID) VALUES (NEW.userID, 7);
END$$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER NewCustomCategorySpendingGoals
AFTER INSERT ON CustomCategory FOR EACH ROW
BEGIN
    INSERT INTO SpendingGoals (userID, customCategoryID) VALUES (NEW.userID, NEW.customCategoryID);
END$$

DELIMITER ;

INSERT INTO User(username, password, emailAddress) VALUES ('testname', 'testpass', 'yifomon921@sentrau.com');

INSERT INTO Category (title, colour, icon) VALUES ('Utilities', '#F38F98', 'fas fa-lightbulb');
INSERT INTO Category (title, colour, icon) VALUES ('Leisure', '#9D8A8B', 'fas fa-smile');
INSERT INTO Category (title, colour, icon) VALUES ('Transport', '#9980F2', 'fas fa-subway');
INSERT INTO Category (title, colour, icon) VALUES ('Subscriptions', '#8FD0F2', 'fas fa-sync');
INSERT INTO Category (title, colour, icon) VALUES ('Shopping', '#54D19F', 'fas fa-shopping-cart');
INSERT INTO Category (title, colour, icon) VALUES ('Debt', '#B354D1', 'fas fa-credit-card');
INSERT INTO Category (title, colour, icon) VALUES ('Entertainment', '#F0BE68', 'fas fa-film');

INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'in', 5.00, '2024-01-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 8.00, '2024-01-10');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'in', 3.00, '2024-02-27');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 4.00, '2024-02-13');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 4.00, '2023-11-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'in', 6.00, '2023-11-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 3.00, '2023-10-10');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'in', 2.00, '2023-10-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'out', 5.00, '2023-01-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'in', 7.00, '2023-01-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'in', 5.00, '2024-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'in', 5.00, '2024-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'out', 5.00, '2024-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'out', 5.00, '2024-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 4, "Comment", 'out', 5.00, '2023-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 5, "Comment", 'in', 5.00, '2024-03-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 6, "Comment", 'out', 5.00, '2023-03-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 7, "Comment", 'in', 5.00, '2023-12-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 5.00, '2023-11-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'in', 6.00, '2023-11-02');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'out', 3.00, '2023-11-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'in', 5.00, '2023-10-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'out', 2.00, '2023-10-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'out', 5.00, '2023-09-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 4, "Comment", 'in', 5.00, '2023-08-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 5, "Comment", 'out', 5.00, '2023-07-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 6, "Comment", 'in', 5.00, '2023-06-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 7, "Comment", 'out', 5.00, '2023-05-01');
INSERT INTO Transaction (userID, customCategoryID, comment, type, amount, date) VALUES (1, 1, "Sausage Roll", 'out', 10.00, '2023-04-01');

INSERT INTO CustomCategory(userID, title, colour) VALUES (1, 'Custom Category', '#371a41');
INSERT INTO CustomCategory(userID, title, colour) VALUES (1, 'More Category', '#518511');
INSERT INTO CustomCategory(userID, title, colour) VALUES (1, 'Another Category', '#818A11');

SELECT t.transactionID, COALESCE(c.title, cc.title) AS category, t.amount, t.comment, t.type FROM Transaction AS t LEFT JOIN Category AS c ON t.categoryID = c.categoryID LEFT JOIN CustomCategory AS cc ON t.customCategoryID = cc.customCategoryID WHERE t.userID = 1;