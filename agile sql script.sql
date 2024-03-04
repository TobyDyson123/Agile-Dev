CREATE SCHEMA IF NOT EXISTS agile;

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
    colour VARCHAR(255) NOT NULL
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
