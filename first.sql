CREATE DATABASE library_management;
USE library_management;

CREATE TABLE SYSTEM_USER (
    User_ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE STAFF (
    Staff_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Role VARCHAR(50) NOT NULL,
    Hire_Date DATE NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES SYSTEM_USER(User_ID)
);

CREATE TABLE MEMBER (
    Member_ID INT AUTO_INCREMENT PRIMARY KEY,
    User_ID INT NOT NULL,
    Phone VARCHAR(11) NOT NULL,
    Address VARCHAR(200) NOT NULL,
    Join_Date DATE NOT NULL,
    FOREIGN KEY (User_ID) REFERENCES SYSTEM_USER(User_ID)
);

CREATE TABLE AUTHORS (
    Author_ID INT AUTO_INCREMENT PRIMARY KEY,
    Author_Name VARCHAR(100) NOT NULL,
    Nationality VARCHAR(50)
);

CREATE TABLE CATEGORIES (
    Category_ID INT AUTO_INCREMENT PRIMARY KEY,
    Category_Name VARCHAR(100) NOT NULL
);

CREATE TABLE PUBLISHERS (
    Publisher_ID INT AUTO_INCREMENT PRIMARY KEY,
    Publisher_Name VARCHAR(100) NOT NULL,
    Country VARCHAR(50)
);

CREATE TABLE BOOKS (
    Book_ID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(200) NOT NULL,
    Author_ID INT NOT NULL,
    Category_ID INT NOT NULL,
    Publisher_ID INT NOT NULL,
    ISBN VARCHAR(20) NOT NULL UNIQUE,
    Publish_Year INT,
    Copies_Total INT NOT NULL,
    Copies_Available INT NOT NULL,
    Shelf_Location VARCHAR(50) NOT NULL,
    FOREIGN KEY (Author_ID) REFERENCES AUTHORS(Author_ID),
    FOREIGN KEY (Category_ID) REFERENCES CATEGORIES(Category_ID),
    FOREIGN KEY (Publisher_ID) REFERENCES PUBLISHERS(Publisher_ID)
);

CREATE TABLE BORROWING (
    Borrow_ID INT AUTO_INCREMENT PRIMARY KEY,
    Member_ID INT NOT NULL,
    Book_ID INT NOT NULL,
    Staff_ID INT NOT NULL,
    Borrow_Date DATE NOT NULL,
    Due_Date DATE NOT NULL,
    Return_Date DATE,
    Status VARCHAR(20) NOT NULL,
    FOREIGN KEY (Member_ID) REFERENCES MEMBER(Member_ID),
    FOREIGN KEY (Book_ID) REFERENCES BOOKS(Book_ID),
    FOREIGN KEY (Staff_ID) REFERENCES STAFF(Staff_ID)
);

CREATE TABLE FINE (
    Fine_ID INT AUTO_INCREMENT PRIMARY KEY,
    Borrow_ID INT NOT NULL UNIQUE,
    Amount DECIMAL(10,2) NOT NULL,
    Paid VARCHAR(3) NOT NULL,
    FOREIGN KEY (Borrow_ID) REFERENCES BORROWING(Borrow_ID)
);

USE library_management;

INSERT INTO SYSTEM_USER (Name, Email) VALUES
('youssef', 'youssef@gemail.com'),
('adham', 'adham@gemail.com'),
('osama', 'osama@gemail.com'),
('Olaa', 'olaa@gemail.com'),
('El5oly', 'eng.el5oly@FCDS.com');

INSERT INTO STAFF (User_ID, Role, Hire_Date) VALUES
(1, 'Librarian', '2023-01-10'),
(2, 'Assistant', '2023-03-12'),
(3, 'Manager', '2023-06-25'),
(4, 'Clerk', '2024-02-05'),
(5, 'Technician', '2024-05-20');

INSERT INTO MEMBER (User_ID, Phone, Address, Join_Date) VALUES
(1, '01280650225', '10 Main St', '2023-02-15'),
(2, '01280650595', '22 Lake Rd', '2023-05-12'),
(3, '01280650755', '34 Oak Ave', '2023-07-20'),
(4, '01280650575', '56 Pine St', '2024-01-25'),
(5, '01280650557', '78 Maple Blvd', '2024-03-10');

INSERT INTO AUTHORS (Author_Name, Nationality) VALUES
('Eng.El5oly', 'Egyption'),
('Rowling', 'British'),
('Mark Twain', 'American'),
('Agatha Christie', 'British'),
('Ernest Hemingway', 'American');

INSERT INTO CATEGORIES (Category_Name) VALUES
('CS'),
('Science'),
('Mystery'),
('History'),
('Philosophy');

INSERT INTO PUBLISHERS (Publisher_Name, Country) VALUES
('Penguin Books', 'UK'),
('HarperCollins', 'USA'),
('Oxford Press', 'UK'),
('Scholastic', 'USA'),
('Random House', 'USA');

INSERT INTO BOOKS (Title, Author_ID, Category_ID, Publisher_ID, ISBN, Publish_Year, Copies_Total, Copies_Available, Shelf_Location) VALUES
('1984', 1, 4, 1, '9780451524935', 1949, 10, 8, 'A-12-F'),
('Harry Potter', 2, 2, 4, '9780747532743', 1997, 12, 11, 'B-10-D'),
('Tom Sawyer', 3, 4, 2, '9780486400778', 1876, 6, 4, 'D-04-A'),
('Murder on the Orient Express', 4, 3, 1, '9780062693662', 1934, 7, 6, 'C-08-G'),
('The Old Man and The Sea', 5, 4, 5, '9780684801223', 1952, 8, 7, 'E-15-H');

INSERT INTO BORROWING (Member_ID, Book_ID, Staff_ID, Borrow_Date, Due_Date, Return_Date, Status) VALUES
(1, 1, 1, '2024-01-10', '2024-01-24', '2024-01-20', 'Returned'),
(2, 2, 2, '2024-02-01', '2024-02-15', NULL, 'Borrowed'),
(3, 3, 3, '2024-02-10', '2024-02-24', '2024-02-28', 'Returned'),
(4, 4, 4, '2024-03-01', '2024-03-15', NULL, 'Overdue'),
(5, 5, 5, '2024-03-10', '2024-03-24', NULL, 'Borrowed');

INSERT INTO FINE (Borrow_ID, Amount, Paid) VALUES
(6, 0.00, 'Yes'),
(7, 0.00, 'No'),
(8, 0.00, 'Yes'),
(9, 10.00, 'No'),
(10, 0.00, 'No');

-- all books 
SELECT 
    b.Book_ID,
    b.Title,
    a.Author_Name,
    c.Category_Name,
    p.Publisher_Name,
    b.ISBN,
    b.Publish_Year,
    b.Copies_Total,
    b.Copies_Available,
    b.Shelf_Location
FROM BOOKS b
JOIN AUTHORS a ON b.Author_ID = a.Author_ID
JOIN CATEGORIES c ON b.Category_ID = c.Category_ID
JOIN PUBLISHERS p ON b.Publisher_ID = p.Publisher_ID
ORDER BY b.Title;
-- add new book 
INSERT INTO BOOKS 
(Title, Author_ID, Category_ID, Publisher_ID, ISBN, Publish_Year, Copies_Total, Copies_Available, Shelf_Location)
VALUES 
('The Great Gatsby', 3, 4, 2, '9780743273565', 1925, 5, 5, 'F-12-B');
-- update book info 
UPDATE BOOKS 
SET Title = '1984 (Updated Edition)',
    Copies_Available = 9
WHERE Book_ID = 1;
-- delete a book 
DELETE FROM BOOKS
WHERE Book_ID = 1
AND Book_ID NOT IN (
    SELECT Book_ID 
    FROM BORROWING 
    WHERE Status = 'Borrowed' OR Status = 'Overdue'
);
-- check if book is borrowed 
SELECT 
    CASE 
        WHEN EXISTS (
            SELECT * FROM BORROWING 
            WHERE Book_ID = 2 
            AND (Status = 'Borrowed' OR Status = 'Overdue')
        ) THEN 'Yes'
        ELSE 'No'
    END AS Is_Borrowed;
-- check if member has fines 
SELECT 
    CASE 
        WHEN EXISTS (
            SELECT * 
            FROM FINE f
            JOIN BORROWING b ON f.Borrow_ID = b.Borrow_ID
            WHERE b.Member_ID = 1
            AND f.Paid = 'No'
            AND f.Amount > 0
        ) THEN 'Yes'
        ELSE 'No'
    END AS Has_Unpaid_Fines;
-- check if book is available 
SELECT 
    CASE 
        WHEN Copies_Available > 0 THEN 'Yes'
        ELSE 'No'
    END AS Is_Available
FROM BOOKS
WHERE Book_ID = 1;
-- all fines 
SELECT 
    f.Fine_ID,
    m.Member_ID,
    u.Name AS Member_Name,
    b.Title AS Book_Title,
    f.Amount,
    f.Paid,
    br.Borrow_Date,
    br.Due_Date
FROM FINE f
JOIN BORROWING br ON f.Borrow_ID = br.Borrow_ID
JOIN MEMBER m ON br.Member_ID = m.Member_ID
JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
JOIN BOOKS b ON br.Book_ID = b.Book_ID
ORDER BY f.Paid, f.Amount DESC;
-- fines for period 
SELECT 
    f.Fine_ID,
    u.Name AS Member_Name,
    b.Title AS Book_Title,
    f.Amount,
    f.Paid,
    br.Borrow_Date,
    br.Due_Date,
    br.Return_Date,
    DATEDIFF(
        IFNULL(br.Return_Date, CURDATE()),
        br.Due_Date
    ) AS Days_Late
FROM FINE f
JOIN BORROWING br ON f.Borrow_ID = br.Borrow_ID
JOIN MEMBER m ON br.Member_ID = m.Member_ID
JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
JOIN BOOKS b ON br.Book_ID = b.Book_ID
WHERE br.Borrow_Date BETWEEN '2024-03-01' AND '2024-03-31'
ORDER BY br.Borrow_Date;
-- all memeber + total borrows 
SELECT 
    m.Member_ID,
    u.Name,
    u.Email,
    m.Phone,
    m.Address,
    m.Join_Date,
    (
        SELECT COUNT(*)
        FROM BORROWING 
        WHERE Member_ID = m.Member_ID
    ) AS Total_Borrows
FROM MEMBER m
JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
ORDER BY m.Join_Date DESC;
-- borrowing summary ( acooring to the mouth ) 
SELECT 
    DATE_FORMAT(br.Borrow_Date, '%Y-%m') AS Month,
    COUNT(br.Borrow_ID) AS Total_Borrows,
    SUM(br.Status = 'Returned') AS Returned_Books,
    SUM(br.Status = 'Overdue') AS Overdue_Books,
    IFNULL(SUM(f.Amount), 0) AS Total_Fines_Amount,
    IFNULL(SUM(CASE WHEN f.Paid = 'No' THEN f.Amount ELSE 0 END), 0) AS Unpaid_Fines
FROM BORROWING br
LEFT JOIN FINE f ON br.Borrow_ID = f.Borrow_ID
GROUP BY DATE_FORMAT(br.Borrow_Date, '%Y-%m')
ORDER BY Month DESC;
-- most popular books 
SELECT 
    b.Book_ID,
    b.Title,
    a.Author_Name,
    COUNT(br.Borrow_ID) AS Times_Borrowed
FROM BOOKS b
JOIN AUTHORS a ON b.Author_ID = a.Author_ID
LEFT JOIN BORROWING br ON b.Book_ID = br.Book_ID
GROUP BY b.Book_ID, b.Title, a.Author_Name
ORDER BY Times_Borrowed DESC
LIMIT 2;
-- most popular author 
SELECT 
    a.Author_ID,
    a.Author_Name,
    COUNT(br.Borrow_ID) AS Total_Borrows,
    COUNT(DISTINCT b.Book_ID) AS Total_Books
FROM AUTHORS a
JOIN BOOKS b ON a.Author_ID = b.Author_ID
LEFT JOIN BORROWING br ON b.Book_ID = br.Book_ID
GROUP BY a.Author_ID, a.Author_Name
ORDER BY Total_Borrows DESC
LIMIT 2;
-- most active member 
SELECT 
    m.Member_ID,
    u.Name,
    u.Email,
    COUNT(br.Borrow_ID) AS Total_Borrows,
    MIN(br.Borrow_Date) AS First_Borrow,
    MAX(br.Borrow_Date) AS Last_Borrow,
    (
        SELECT COUNT(*) 
        FROM BORROWING b2 
        WHERE b2.Member_ID = m.Member_ID 
        AND b2.Status = 'Overdue'
    ) AS Overdue_Count
FROM MEMBER m
JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
LEFT JOIN BORROWING br ON m.Member_ID = br.Member_ID
GROUP BY m.Member_ID, u.Name, u.Email
ORDER BY Total_Borrows DESC
LIMIT 2;
