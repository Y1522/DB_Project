<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require __DIR__ . '/db.php';

$user = $_SESSION['user'];
$isPrivileged = in_array($user['role'], ['manager', 'staff'], true);

$messages = [];
$errors = [];
$checkResults = [
    'borrowed' => null,
    'memberFines' => null,
    'available' => null,
];
$finesPeriodRows = [];

function queryAll($mysqli, $sql)
{
    $result = $mysqli->query($sql);
    if ($result === false) {
        return [];
    }

    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();

    return $rows;
}

function refValues(array &$arr): array
{
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }

    return $refs;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_book':
            if (!$isPrivileged) {
                $errors[] = 'Only staff and managers can add books.';
                break;
            }

            $title = trim($_POST['title'] ?? '');
            $authorId = (int) ($_POST['author_id'] ?? 0);
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $publisherId = (int) ($_POST['publisher_id'] ?? 0);
            $isbn = trim($_POST['isbn'] ?? '');
            $publishYear = (int) ($_POST['publish_year'] ?? 0);
            $copiesTotal = (int) ($_POST['copies_total'] ?? 0);
            $copiesAvailable = (int) ($_POST['copies_available'] ?? 0);
            $shelf = trim($_POST['shelf_location'] ?? '');

            if (
                $title === '' || $authorId <= 0 || $categoryId <= 0 || $publisherId <= 0 ||
                $isbn === '' || $publishYear <= 0 || $copiesTotal <= 0 || $copiesAvailable < 0 ||
                $copiesAvailable > $copiesTotal || $shelf === ''
            ) {
                $errors[] = 'Please fill in all book fields correctly. Copies available cannot exceed total copies.';
                break;
            }

            $stmt = $mysqli->prepare(
                'INSERT INTO BOOKS 
                (Title, Author_ID, Category_ID, Publisher_ID, ISBN, Publish_Year, Copies_Total, Copies_Available, Shelf_Location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'siiisiiis',
                $title,
                $authorId,
                $categoryId,
                $publisherId,
                $isbn,
                $publishYear,
                $copiesTotal,
                $copiesAvailable,
                $shelf
            );

            if ($stmt->execute()) {
                $messages[] = 'Book added successfully.';
            } else {
                $errors[] = 'Failed to add book: ' . $stmt->error;
            }

            $stmt->close();
            break;

        case 'update_book':
            if (!$isPrivileged) {
                $errors[] = 'Only staff and managers can update books.';
                break;
            }

            $bookId = (int) ($_POST['book_id'] ?? 0);
            $title = trim($_POST['update_title'] ?? '');
            $copiesAvailableRaw = $_POST['update_copies_available'] ?? '';
            $fields = [];
            $types = '';
            $params = [];

            if ($bookId <= 0) {
                $errors[] = 'Please provide a valid book ID.';
                break;
            }

            if ($title !== '') {
                $fields[] = 'Title = ?';
                $types .= 's';
                $params[] = $title;
            }

            if ($copiesAvailableRaw !== '') {
                $copiesAvailable = (int) $copiesAvailableRaw;
                if ($copiesAvailable < 0) {
                    $errors[] = 'Copies available cannot be negative.';
                    break;
                }
                $fields[] = 'Copies_Available = ?';
                $types .= 'i';
                $params[] = $copiesAvailable;
            }

            if (empty($fields)) {
                $errors[] = 'Nothing to update. Provide Title or Copies Available.';
                break;
            }

            $types .= 'i';
            $params[] = $bookId;
            $sql = 'UPDATE BOOKS SET ' . implode(', ', $fields) . ' WHERE Book_ID = ?';

            $stmt = $mysqli->prepare($sql);
            $refs = refValues($params);
            $stmt->bind_param($types, ...$refs);

            if ($stmt->execute()) {
                $messages[] = 'Book updated successfully.';
            } else {
                $errors[] = 'Failed to update book: ' . $stmt->error;
            }

            $stmt->close();
            break;

        case 'delete_book':
            if (!$isPrivileged) {
                $errors[] = 'Only staff and managers can delete books.';
                break;
            }

            $bookId = (int) ($_POST['delete_book_id'] ?? 0);
            if ($bookId <= 0) {
                $errors[] = 'Please provide a valid book ID.';
                break;
            }

            $stmt = $mysqli->prepare(
                "SELECT COUNT(*) AS total 
                 FROM BORROWING 
                 WHERE Book_ID = ? AND (Status = 'Borrowed' OR Status = 'Overdue')"
            );
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($result['total'] > 0) {
                $errors[] = 'Cannot delete book while it is borrowed or overdue.';
                break;
            }

            $stmt = $mysqli->prepare('DELETE FROM BOOKS WHERE Book_ID = ?');
            $stmt->bind_param('i', $bookId);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $messages[] = 'Book deleted successfully.';
                } else {
                    $errors[] = 'No book found with the provided ID.';
                }
            } else {
                $errors[] = 'Failed to delete book: ' . $stmt->error;
            }

            $stmt->close();
            break;

        case 'check_book_borrowed':
            $bookId = (int) ($_POST['check_book_id'] ?? 0);
            if ($bookId <= 0) {
                $errors[] = 'Provide a valid book ID to check.';
                break;
            }

            $stmt = $mysqli->prepare(
                "SELECT CASE 
                    WHEN EXISTS (
                        SELECT 1 FROM BORROWING 
                        WHERE Book_ID = ? AND (Status = 'Borrowed' OR Status = 'Overdue')
                    ) THEN 'Yes' ELSE 'No' END AS Is_Borrowed"
            );
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $checkResults['borrowed'] = $stmt->get_result()->fetch_assoc()['Is_Borrowed'];
            $stmt->close();
            break;

        case 'check_member_fines':
            $memberId = (int) ($_POST['check_member_id'] ?? 0);
            if ($memberId <= 0) {
                $errors[] = 'Provide a valid member ID.';
                break;
            }

            if (!$isPrivileged && (!isset($user['member_id']) || $memberId !== (int) $user['member_id'])) {
                $errors[] = 'You can only check fines for your own membership.';
                break;
            }

            $stmt = $mysqli->prepare(
                "SELECT CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM FINE f
                        JOIN BORROWING b ON f.Borrow_ID = b.Borrow_ID
                        WHERE b.Member_ID = ? AND f.Paid = 'No' AND f.Amount > 0
                    ) THEN 'Yes' ELSE 'No' END AS Has_Unpaid_Fines"
            );
            $stmt->bind_param('i', $memberId);
            $stmt->execute();
            $checkResults['memberFines'] = $stmt->get_result()->fetch_assoc()['Has_Unpaid_Fines'];
            $stmt->close();
            break;

        case 'check_book_available':
            $bookId = (int) ($_POST['check_available_book_id'] ?? 0);
            if ($bookId <= 0) {
                $errors[] = 'Provide a valid book ID to check availability.';
                break;
            }

            $stmt = $mysqli->prepare(
                "SELECT CASE 
                    WHEN Copies_Available > 0 THEN 'Yes'
                    ELSE 'No' END AS Is_Available
                 FROM BOOKS WHERE Book_ID = ?"
            );
            $stmt->bind_param('i', $bookId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $checkResults['available'] = $row['Is_Available'] ?? 'No';
            $stmt->close();
            break;

        case 'fines_period':
            if (!$isPrivileged) {
                $errors[] = 'Only staff and managers can view fines by period.';
                break;
            }

            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? '';

            if ($start === '' || $end === '') {
                $errors[] = 'Please provide both start and end dates.';
                break;
            }

            $stmt = $mysqli->prepare(
                "SELECT 
                    f.Fine_ID,
                    u.Name AS Member_Name,
                    b.Title AS Book_Title,
                    f.Amount,
                    f.Paid,
                    br.Borrow_Date,
                    br.Due_Date,
                    br.Return_Date,
                    DATEDIFF(IFNULL(br.Return_Date, CURDATE()), br.Due_Date) AS Days_Late
                 FROM FINE f
                 JOIN BORROWING br ON f.Borrow_ID = br.Borrow_ID
                 JOIN MEMBER m ON br.Member_ID = m.Member_ID
                 JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
                 JOIN BOOKS b ON br.Book_ID = b.Book_ID
                 WHERE br.Borrow_Date BETWEEN ? AND ?
                 ORDER BY br.Borrow_Date"
            );
            $stmt->bind_param('ss', $start, $end);
            $stmt->execute();
            $result = $stmt->get_result();
            $finesPeriodRows = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            break;

        case 'add_member':
            if (!$isPrivileged) {
                $errors[] = 'Only staff and managers can add members.';
                break;
            }

            $name = trim($_POST['member_name'] ?? '');
            $email = trim($_POST['member_email'] ?? '');
            $phone = trim($_POST['member_phone'] ?? '');
            $address = trim($_POST['member_address'] ?? '');
            $joinDate = $_POST['member_join_date'] ?? date('Y-m-d');

            if ($name === '' || $email === '' || $phone === '' || $address === '') {
                $errors[] = 'Please fill in all member fields.';
                break;
            }

            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare('INSERT INTO SYSTEM_USER (Name, Email) VALUES (?, ?)');
                $stmt->bind_param('ss', $name, $email);
                $stmt->execute();
                $userId = $mysqli->insert_id;
                $stmt->close();

                $stmt = $mysqli->prepare('INSERT INTO MEMBER (User_ID, Phone, Address, Join_Date) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('isss', $userId, $phone, $address, $joinDate);
                $stmt->execute();
                $stmt->close();

                $mysqli->commit();
                $messages[] = "Member '$name' added successfully (User ID: $userId).";
            } catch (Exception $e) {
                $mysqli->rollback();
                $errors[] = 'Failed to add member: ' . $e->getMessage();
            }
            break;

        case 'add_staff':
            if ($user['role'] !== 'manager') {
                $errors[] = 'Only managers can add staff members.';
                break;
            }

            $name = trim($_POST['staff_name'] ?? '');
            $email = trim($_POST['staff_email'] ?? '');
            $role = trim($_POST['staff_role'] ?? '');
            $hireDate = $_POST['staff_hire_date'] ?? date('Y-m-d');

            if ($name === '' || $email === '' || $role === '') {
                $errors[] = 'Please fill in all staff fields.';
                break;
            }

            $mysqli->begin_transaction();
            try {
                $stmt = $mysqli->prepare('INSERT INTO SYSTEM_USER (Name, Email) VALUES (?, ?)');
                $stmt->bind_param('ss', $name, $email);
                $stmt->execute();
                $userId = $mysqli->insert_id;
                $stmt->close();

                $stmt = $mysqli->prepare('INSERT INTO STAFF (User_ID, Role, Hire_Date) VALUES (?, ?, ?)');
                $stmt->bind_param('iss', $userId, $role, $hireDate);
                $stmt->execute();
                $stmt->close();

                $mysqli->commit();
                $messages[] = "Staff '$name' added successfully as $role (User ID: $userId).";
            } catch (Exception $e) {
                $mysqli->rollback();
                $errors[] = 'Failed to add staff: ' . $e->getMessage();
            }
            break;
    }
}

$allBooks = queryAll(
    $mysqli,
    "SELECT 
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
     ORDER BY b.Title"
);

$allFines = $isPrivileged ? queryAll(
    $mysqli,
    "SELECT 
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
     ORDER BY f.Paid, f.Amount DESC"
) : [];

$membersWithBorrows = $isPrivileged ? queryAll(
    $mysqli,
    "SELECT 
        m.Member_ID,
        u.Name,
        u.Email,
        m.Phone,
        m.Address,
        m.Join_Date,
        (
            SELECT COUNT(*) FROM BORROWING WHERE Member_ID = m.Member_ID
        ) AS Total_Borrows
     FROM MEMBER m
     JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
     ORDER BY m.Join_Date DESC"
) : [];

$borrowingSummary = $isPrivileged ? queryAll(
    $mysqli,
    "SELECT 
        DATE_FORMAT(br.Borrow_Date, '%Y-%m') AS Month,
        COUNT(br.Borrow_ID) AS Total_Borrows,
        SUM(br.Status = 'Returned') AS Returned_Books,
        SUM(br.Status = 'Overdue') AS Overdue_Books,
        IFNULL(SUM(f.Amount), 0) AS Total_Fines_Amount,
        IFNULL(SUM(CASE WHEN f.Paid = 'No' THEN f.Amount ELSE 0 END), 0) AS Unpaid_Fines
     FROM BORROWING br
     LEFT JOIN FINE f ON br.Borrow_ID = f.Borrow_ID
     GROUP BY DATE_FORMAT(br.Borrow_Date, '%Y-%m')
     ORDER BY Month DESC"
) : [];

$popularBooks = queryAll(
    $mysqli,
    "SELECT 
        b.Book_ID,
        b.Title,
        a.Author_Name,
        COUNT(br.Borrow_ID) AS Times_Borrowed
     FROM BOOKS b
     JOIN AUTHORS a ON b.Author_ID = a.Author_ID
     LEFT JOIN BORROWING br ON b.Book_ID = br.Book_ID
     GROUP BY b.Book_ID, b.Title, a.Author_Name
     ORDER BY Times_Borrowed DESC
     LIMIT 2"
);

$popularAuthors = $isPrivileged ? queryAll(
    $mysqli,
    "SELECT 
        a.Author_ID,
        a.Author_Name,
        COUNT(br.Borrow_ID) AS Total_Borrows,
        COUNT(DISTINCT b.Book_ID) AS Total_Books
     FROM AUTHORS a
     JOIN BOOKS b ON a.Author_ID = b.Author_ID
     LEFT JOIN BORROWING br ON b.Book_ID = br.Book_ID
     GROUP BY a.Author_ID, a.Author_Name
     ORDER BY Total_Borrows DESC
     LIMIT 2"
) : [];

$activeMembers = $isPrivileged ? queryAll(
    $mysqli,
    "SELECT 
        m.Member_ID,
        u.Name,
        u.Email,
        COUNT(br.Borrow_ID) AS Total_Borrows,
        MIN(br.Borrow_Date) AS First_Borrow,
        MAX(br.Borrow_Date) AS Last_Borrow,
        (
            SELECT COUNT(*) FROM BORROWING b2 
            WHERE b2.Member_ID = m.Member_ID AND b2.Status = 'Overdue'
        ) AS Overdue_Count
     FROM MEMBER m
     JOIN SYSTEM_USER u ON m.User_ID = u.User_ID
     LEFT JOIN BORROWING br ON m.Member_ID = br.Member_ID
     GROUP BY m.Member_ID, u.Name, u.Email
     ORDER BY Total_Borrows DESC
     LIMIT 2"
) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="top-bar">
        <div>
            <h1>Library Management</h1>
            <p>Welcome, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>)</p>
        </div>
        <div class="top-bar-actions">
            <a class="secondary-btn" href="logout.php">Logout</a>
        </div>
    </header>

    <main class="content-wrapper">
        <?php foreach ($messages as $message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>

        <section class="card">
            <h2>All Books</h2>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Publisher</th>
                            <th>ISBN</th>
                            <th>Year</th>
                            <th>Total</th>
                            <th>Available</th>
                            <th>Shelf</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allBooks as $book): ?>
                            <tr>
                                <td><?= (int) $book['Book_ID']; ?></td>
                                <td><?= htmlspecialchars($book['Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($book['Author_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($book['Category_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($book['Publisher_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($book['ISBN'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($book['Publish_Year'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= (int) $book['Copies_Total']; ?></td>
                                <td><?= (int) $book['Copies_Available']; ?></td>
                                <td><?= htmlspecialchars($book['Shelf_Location'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($isPrivileged): ?>
            <section class="card grid-2">
                <div>
                    <h2>Add New Book</h2>
                    <form method="POST" class="form-grid compact">
                        <input type="hidden" name="action" value="add_book">
                        <label>Title<input type="text" name="title" required></label>
                        <label>Author ID<input type="number" name="author_id" required></label>
                        <label>Category ID<input type="number" name="category_id" required></label>
                        <label>Publisher ID<input type="number" name="publisher_id" required></label>
                        <label>ISBN<input type="text" name="isbn" required></label>
                        <label>Publish Year<input type="number" name="publish_year" required></label>
                        <label>Copies Total<input type="number" name="copies_total" required></label>
                        <label>Copies Available<input type="number" name="copies_available" required></label>
                        <label>Shelf Location<input type="text" name="shelf_location" required></label>
                        <button class="primary-btn" type="submit">Add Book</button>
                    </form>
                </div>
                <div>
                    <h2>Update Book</h2>
                    <form method="POST" class="form-grid compact">
                        <input type="hidden" name="action" value="update_book">
                        <label>Book ID<input type="number" name="book_id" required></label>
                        <label>New Title<input type="text" name="update_title"></label>
                        <label>New Copies Available<input type="number" name="update_copies_available"></label>
                        <button class="primary-btn" type="submit">Update Book</button>
                    </form>
                </div>
            </section>

            <section class="card">
                <h2>Delete Book</h2>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="delete_book">
                    <label>Book ID<input type="number" name="delete_book_id" required></label>
                    <button class="danger-btn" type="submit">Delete Book</button>
                </form>
                <p class="caption">Books currently borrowed or overdue cannot be removed.</p>
            </section>
        <?php endif; ?>

        <?php if ($isPrivileged): ?>
            <section class="card">
                <h2>👥 User Management</h2>
                <div class="grid-2">
                    <div>
                        <h3>Add New Member</h3>
                        <form method="POST" class="form-grid">
                            <input type="hidden" name="action" value="add_member">
                            <label>Full Name<input type="text" name="member_name" required></label>
                            <label>Email<input type="email" name="member_email" required></label>
                            <label>Phone<input type="text" name="member_phone" required maxlength="11"></label>
                            <label>Address<input type="text" name="member_address" required></label>
                            <label>Join Date<input type="date" name="member_join_date" value="<?= date('Y-m-d'); ?>"></label>
                            <button class="primary-btn" type="submit">Add Member</button>
                        </form>
                    </div>
                    <?php if ($user['role'] === 'manager'): ?>
                        <div>
                            <h3>Add New Staff</h3>
                            <form method="POST" class="form-grid">
                                <input type="hidden" name="action" value="add_staff">
                                <label>Full Name<input type="text" name="staff_name" required></label>
                                <label>Email<input type="email" name="staff_email" required></label>
                                <label>Role
                                    <select name="staff_role" required>
                                        <option value="">Select Role</option>
                                        <option value="Manager">Manager</option>
                                        <option value="Staff">Staff</option>
                                    </select>
                                </label>
                                <label>Hire Date<input type="date" name="staff_hire_date" value="<?= date('Y-m-d'); ?>"></label>
                                <button class="primary-btn" type="submit">Add Staff</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="card grid-3">
            <div>
                <h2>Is Book Borrowed?</h2>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="check_book_borrowed">
                    <label>Book ID<input type="number" name="check_book_id" required></label>
                    <button class="secondary-btn" type="submit">Check</button>
                </form>
                <?php if ($checkResults['borrowed'] !== null): ?>
                    <p class="metric">Borrowed: <strong><?= htmlspecialchars($checkResults['borrowed'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <?php endif; ?>
            </div>

            <div>
                <h2>Member Has Unpaid Fines?</h2>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="check_member_fines">
                    <label>Member ID<input type="number" name="check_member_id" required></label>
                    <button class="secondary-btn" type="submit">Check</button>
                </form>
                <?php if ($checkResults['memberFines'] !== null): ?>
                    <p class="metric">Has Unpaid Fines: <strong><?= htmlspecialchars($checkResults['memberFines'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <?php endif; ?>
            </div>

            <div>
                <h2>Book Available?</h2>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="check_book_available">
                    <label>Book ID<input type="number" name="check_available_book_id" required></label>
                    <button class="secondary-btn" type="submit">Check</button>
                </form>
                <?php if ($checkResults['available'] !== null): ?>
                    <p class="metric">Available: <strong><?= htmlspecialchars($checkResults['available'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($isPrivileged): ?>
            <section class="card">
                <h2>All Fines</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Member</th>
                                <th>Book</th>
                                <th>Amount</th>
                                <th>Paid</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allFines as $fine): ?>
                                <tr>
                                    <td><?= (int) $fine['Fine_ID']; ?></td>
                                    <td><?= htmlspecialchars($fine['Member_Name'], ENT_QUOTES, 'UTF-8'); ?> (#<?= (int) $fine['Member_ID']; ?>)</td>
                                    <td><?= htmlspecialchars($fine['Book_Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($fine['Amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($fine['Paid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($fine['Borrow_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($fine['Due_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2>Fines By Period</h2>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="action" value="fines_period">
                    <label>Start<input type="date" name="start_date" required></label>
                    <label>End<input type="date" name="end_date" required></label>
                    <button class="primary-btn" type="submit">Load Fines</button>
                </form>
                <?php if (!empty($finesPeriodRows)): ?>
                    <div class="table-wrapper mt">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Book</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Borrowed</th>
                                    <th>Due</th>
                                    <th>Returned</th>
                                    <th>Days Late</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($finesPeriodRows as $fine): ?>
                                    <tr>
                                        <td><?= (int) $fine['Fine_ID']; ?></td>
                                        <td><?= htmlspecialchars($fine['Member_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Book_Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Paid'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Borrow_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Due_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Return_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($fine['Days_Late'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card">
                <h2>Members & Total Borrows</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Joined</th>
                                <th>Total Borrows</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membersWithBorrows as $member): ?>
                                <tr>
                                    <td><?= (int) $member['Member_ID']; ?></td>
                                    <td><?= htmlspecialchars($member['Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Join_Date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $member['Total_Borrows']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2>Borrowing Summary (Monthly)</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Total Borrows</th>
                                <th>Returned</th>
                                <th>Overdue</th>
                                <th>Total Fines</th>
                                <th>Unpaid Fines</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($borrowingSummary as $summary): ?>
                                <tr>
                                    <td><?= htmlspecialchars($summary['Month'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $summary['Total_Borrows']; ?></td>
                                    <td><?= (int) $summary['Returned_Books']; ?></td>
                                    <td><?= (int) $summary['Overdue_Books']; ?></td>
                                    <td><?= htmlspecialchars($summary['Total_Fines_Amount'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($summary['Unpaid_Fines'], ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="card grid-2">
            <div>
                <h2>Most Popular Books</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Borrows</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($popularBooks as $book): ?>
                                <tr>
                                    <td><?= (int) $book['Book_ID']; ?></td>
                                    <td><?= htmlspecialchars($book['Title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($book['Author_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $book['Times_Borrowed']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($isPrivileged): ?>
                <div>
                    <h2>Top Authors</h2>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Total Borrows</th>
                                    <th>Total Books</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popularAuthors as $author): ?>
                                    <tr>
                                        <td><?= (int) $author['Author_ID']; ?></td>
                                        <td><?= htmlspecialchars($author['Author_Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= (int) $author['Total_Borrows']; ?></td>
                                        <td><?= (int) $author['Total_Books']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <?php if ($isPrivileged): ?>
            <section class="card">
                <h2>Most Active Members</h2>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Total Borrows</th>
                                <th>First Borrow</th>
                                <th>Last Borrow</th>
                                <th>Overdue Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeMembers as $member): ?>
                                <tr>
                                    <td><?= (int) $member['Member_ID']; ?></td>
                                    <td><?= htmlspecialchars($member['Name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $member['Total_Borrows']; ?></td>
                                    <td><?= htmlspecialchars($member['First_Borrow'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= htmlspecialchars($member['Last_Borrow'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?= (int) $member['Overdue_Count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>

