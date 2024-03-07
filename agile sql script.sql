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
    isOn BOOLEAN NOT NULL,
    goalAmount DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID),
    FOREIGN KEY (categoryID) REFERENCES Category(categoryID),
    FOREIGN KEY (customCategoryID) REFERENCES CustomCategory(customCategoryID)
);

CREATE TABLE BudgetReminder (
    budgetReminderID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
	isOn BOOLEAN NOT NULL,
    monthlyBudget DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID)
);

CREATE TABLE TransactionNotes (
    transactionNotesID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    isOn BOOLEAN NOT NULL,
    FOREIGN KEY (userID) REFERENCES User(userID)
);

INSERT INTO User(username, password, emailAddress) VALUES ('testname', 'testpass', 'test@email.com');

INSERT INTO Category (title, colour, icon) VALUES ('Utilities', '#F38F98', 'fas fa-lightbulb');
INSERT INTO Category (title, colour, icon) VALUES ('Leisure', '#9D8A8B', 'fas fa-smile');
INSERT INTO Category (title, colour, icon) VALUES ('Transport', '#9980F2', 'fas fa-subway');
INSERT INTO Category (title, colour, icon) VALUES ('Subscriptions', '#8FD0F2', 'fas fa-sync');
INSERT INTO Category (title, colour, icon) VALUES ('Shopping', '#54D19F', 'fas fa-shopping-cart');
INSERT INTO Category (title, colour, icon) VALUES ('Debt', '#B354D1', 'fas fa-credit-card');
INSERT INTO Category (title, colour, icon) VALUES ('Entertainment', '#F0BE68', 'fas fa-film');

INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'in', 5.00, '2024-01-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'out', 5.00, '2023-01-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'in', 5.00, '2024-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 4, "Comment", 'out', 5.00, '2023-02-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 5, "Comment", 'in', 5.00, '2024-03-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 6, "Comment", 'out', 5.00, '2023-03-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 7, "Comment", 'in', 5.00, '2023-12-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 1, "Comment", 'out', 5.00, '2023-11-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 2, "Comment", 'in', 5.00, '2023-10-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 3, "Comment", 'out', 5.00, '2023-09-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 4, "Comment", 'in', 5.00, '2023-08-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 5, "Comment", 'out', 5.00, '2023-07-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 6, "Comment", 'in', 5.00, '2023-06-01');
INSERT INTO Transaction (userID, categoryID, comment, type, amount, date) VALUES (1, 7, "Comment", 'out', 5.00, '2023-05-01');
INSERT INTO Transaction (userID, customCategoryID, comment, type, amount, date) VALUES (1, 1, "Sausage Roll", 'in', 5.00, '2023-04-01');

INSERT INTO CustomCategory(userID, title, colour) VALUES (1, 'Custom Category', '#371a41');