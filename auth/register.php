<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$errors = [];
$success = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $checkStmt = $conn->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $checkStmt->execute(['email' => $email]);
        if ($checkStmt->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
            $insertStmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
            ]);
            $success = 'Registration successful! You can now log in.';
            $name = '';
            $email = '';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Create Account</h1>
            <p class="muted">Register to save trip plans and access the dashboard.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-grid">
                <label>
                    Full Name
                    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </label>
                <label>
                    Email Address
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <label>
                    Confirm Password
                    <input type="password" name="confirm_password" required>
                </label>
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>

            <p class="text-center small">Already have an account? <a href="login.php">Log in</a></p>
            <p class="text-center small"><a href="../index.php">&larr; Back to Home</a></p>
        </div>
    </div>
</body>
</html>
