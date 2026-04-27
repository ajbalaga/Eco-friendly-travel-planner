<?php
declare(strict_types=1);
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare('SELECT user_id, name, email, password FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int) $user['user_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: ../pages/dashboard.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sustainable Travel Planner</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Welcome Back</h1>
            <p class="muted">Log in to continue planning eco-friendly trips.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="form-grid">
                <label>
                    Email Address
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="btn btn-primary btn-block">Log In</button>
            </form>

            <p class="text-center small">No account yet? <a href="register.php">Create one</a></p>
            <p class="text-center small"><a href="../index.php">&larr; Back to Home</a></p>
        </div>
    </div>
</body>
</html>
