<?php
session_start();

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/db.php';

    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $role = isset($_POST['role']) ? strtolower(trim($_POST['role'])) : '';

    $validRoles = ['manager', 'staff', 'member'];

    if ($userId <= 0 || $name === '' || !in_array($role, $validRoles, true)) {
        $error = 'Please provide a valid user ID, name, and role.';
    } else {
        if ($role === 'member') {
            $stmt = $mysqli->prepare(
                'SELECT m.Member_ID 
                 FROM MEMBER m 
                 JOIN SYSTEM_USER u ON m.User_ID = u.User_ID 
                 WHERE u.User_ID = ? AND u.Name = ?'
            );
            $stmt->bind_param('is', $userId, $name);
            $stmt->execute();
            $result = $stmt->get_result();
            $member = $result->fetch_assoc();
            $stmt->close();

            if ($member) {
                $_SESSION['user'] = [
                    'user_id' => $userId,
                    'name' => $name,
                    'role' => 'member',
                    'member_id' => (int) $member['Member_ID'],
                ];
                header('Location: dashboard.php');
                exit;
            }

            $error = 'Member not found. Please verify your details.';
        } else {
            $stmt = $mysqli->prepare(
                'SELECT s.Staff_ID, s.Role 
                 FROM STAFF s 
                 JOIN SYSTEM_USER u ON s.User_ID = u.User_ID 
                 WHERE u.User_ID = ? AND u.Name = ?'
            );
            $stmt->bind_param('is', $userId, $name);
            $stmt->execute();
            $result = $stmt->get_result();
            $staff = $result->fetch_assoc();
            $stmt->close();

            if ($staff) {
                $dbRole = strtolower($staff['Role']);
                $normalizedRole = $dbRole === 'manager' ? 'manager' : 'staff';

                if ($role === 'manager' && $normalizedRole !== 'manager') {
                    $error = 'You do not have manager privileges.';
                } elseif ($role === 'staff' && $normalizedRole === 'manager') {
                    // Manager logging in as staff is allowed; treat as manager.
                    $normalizedRole = 'manager';
                } else {
                    $_SESSION['user'] = [
                        'user_id' => $userId,
                        'name' => $name,
                        'role' => $normalizedRole,
                        'staff_id' => (int) $staff['Staff_ID'],
                    ];
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $error = 'Staff record not found. Please verify your details.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="auth-card">
        <h1>Library Management</h1>
        <p class="subtitle">Sign in to access the system</p>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="POST" class="form-grid">
            <label>
                User ID
                <input type="number" name="user_id" required>
            </label>
            <label>
                Full Name
                <input type="text" name="name" required>
            </label>
            <label>
                Role
                <select name="role" required>
                    <option value="">Select role</option>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                    <option value="member">Member</option>
                </select>
            </label>
            <button type="submit" class="primary-btn full-width">Sign In</button>
        </form>
    </div>
</body>
</html>

