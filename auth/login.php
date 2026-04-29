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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="login-page-container">
    <div class="login-overlay">
        <div class="login-card">
            <div class="login-header">
                <h1>Welcome Back</h1>
                <p>Log in to manage your carbon-conscious journeys.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="login-alert">
                    <span class="alert-icon"><i class="fas fa-exclamation-circle"></i></span>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="login-field">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="explorer@nature.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <span class="toggle-password" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="login-submit-btn">
                    Sign In
                </button>
            </form>

            <div class="login-footer">
                <p>New here? <a href="register.php">Start your journey</a></p>
                <a href="../index.php" class="login-home-link">← Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
