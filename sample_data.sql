-- Sample data for Library Management System
-- Run this after importing Library Management.sql

USE library_management;

-- Create system users
INSERT INTO SYSTEM_USER (User_ID, Name, Email) VALUES 
(1, 'eng.el5oly', 'el5oly@FCDS.com'),
(2, 'Sarah', 'sarah@library.com'),
(3, 'Mike', 'mike@library.com'),
(4, 'youssef', 'youssef@library.com'),
(5, 'ahmed', 'ahmed@library.com'),
(6, 'adham', 'adham@library.com'),
(7, 'osama', 'osama@library.com'),
(8, 'mazen', 'mazen@library.com'),
(9, 'sohieb', 'sohieb@library.com'),
(10, 'mohamed', 'mohamed@library.com');


-- Create staff (manager and regular staff)
INSERT INTO STAFF (Staff_ID, User_ID, Role, Hire_Date) VALUES
(1, 1, 'Manager', '2024-01-01'),
(2, 2, 'Staff', '2024-02-01'),
(3, 3, 'Staff', '2024-03-01'),
(4, 4, 'Staff', '2024-02-01');

-- Create member
INSERT INTO MEMBER (Member_ID, User_ID, Phone, Address, Join_Date) VALUES
(1, 5, '1234567890', '123 Main St', '2024-03-01'),
(2, 6, '1234567890', '123 Main St', '2024-03-01'),
(3, 7, '1234567890', '123 Main St', '2024-03-01'),
(4, 8, '1234567890', '123 Main St', '2024-03-01'),
(5, 9, '1234567890', '123 Main St', '2024-03-01'),
(6, 10, '01012345678', '123 Main St', '2024-03-01');
-- Add sample authors
INSERT INTO AUTHORS (Author_ID, Author_Name, Nationality) VALUES
(1, 'George Orwell', 'British'),
(2, 'Jane Austen', 'British'),
(3, 'F. Scott Fitzgerald', 'American');

-- Add sample categories
INSERT INTO CATEGORIES (Category_ID, Category_Name) VALUES
(1, 'Fiction'),
(2, 'Classic'),
(3, 'Science Fiction'),
(4, 'Romance'),
(5, 'CS');

-- Add sample publishers
INSERT INTO PUBLISHERS (Publisher_ID, Publisher_Name, Country) VALUES
(1, 'Penguin Books', 'UK'),
(2, 'Scribner', 'USA'),
(3, 'HarperCollins', 'USA');

-- Add sample books
INSERT INTO BOOKS (Book_ID, Title, Author_ID, Category_ID, Publisher_ID, ISBN, Publish_Year, Copies_Total, Copies_Available, Shelf_Location) VALUES
(1, '1984', 1, 3, 1, '9780451524935', 1949, 10, 8, 'A-12-C'),
(2, 'Pride and Prejudice', 2, 4, 3, '9780141439518', 1813, 5, 5, 'B-03-A'),
(3, 'The Great Gatsby', 3, 2, 2, '9780743273565', 1925, 7, 6, 'C-08-B');

