<?php
declare(strict_types=1);

// 1. Security Headers
header("X-XSS-Protection: 1; mode=block");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$success = '';
$info = '';
$errors = [];

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. FETCH CURRENT DATA
$stmt = $conn->prepare("SELECT name, email, profile_image, password FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// 3. HANDLE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security token mismatch.");
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $image = $_FILES['profile_image'] ?? null;
    
    $image_uploaded = ($image && $image['error'] === UPLOAD_ERR_OK);
    $password_provided = !empty($new_password);

    // Basic Validation
    if (empty($name)) $errors[] = "Name is required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";

    // CHANGE DETECTION
    if ($name === $user['name'] && 
        $email === $user['email'] && 
        !$password_provided && 
        !$image_uploaded) {
        $info = "Everything looks up to date! No changes were made.";
    } else {
        $image_filename = $user['profile_image'];
        $password_update_sql = "";
        $params = [$name, $email];

        if ($password_provided) {
            if (strlen($new_password) < 8) {
                $errors[] = "Password must be at least 8 characters.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "Passwords do not match.";
            } else {
                $password_update_sql = ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
        }

        if ($image_uploaded && empty($errors)) {
            $upload_dir = "../assets/uploads/profiles/";
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($image['tmp_name']);
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            
            if (in_array($mime_type, $allowed_mimes)) {
                if ($image['size'] > 2000000) {
                    $errors[] = "Image is too heavy. Max limit is 2MB.";
                } else {
                    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
                    $image_filename = "user_" . $user_id . "_" . time() . "." . $ext;
                    if (move_uploaded_file($image['tmp_name'], $upload_dir . $image_filename)) {
                        if ($user['profile_image'] && file_exists($upload_dir . $user['profile_image'])) {
                            unlink($upload_dir . $user['profile_image']);
                        }
                    }
                }
            } else {
                $errors[] = "Please upload a JPG, PNG, or WebP image.";
            }
        }

        if (empty($errors)) {
            $params[] = $image_filename;
            $params[] = $user_id;
            $sql = "UPDATE users SET name = ?, email = ? $password_update_sql, profile_image = ? WHERE user_id = ?";
            
            try {
                $stmt = $conn->prepare($sql);
                if ($stmt->execute($params)) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['profile_image'] = $image_filename;
                    $success = "Profile updated successfully! ✨";
                    
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $user['profile_image'] = $image_filename;
                }
            } catch (PDOException $e) {
                $errors[] = ($e->getCode() == '23000') ? "Email already exists." : "Update failed.";
            }
        }
    }
}

$display_img = !empty($user['profile_image']) && file_exists("../assets/uploads/profiles/" . $user['profile_image'])
    ? "../assets/uploads/profiles/" . $user['profile_image'] 
    : "../assets/images/default-avatar.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Eco-Travel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/edit_user_profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body class="profile-page-container">

    <div class="profile-card">
        <div class="profile-header">
            <h1>Edit Profile</h1>
            <p>Keep your travel profile fresh and secure.</p>
        </div>

        <?php if ($success): ?>
            <div class="profile-alert success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php elseif ($info): ?>
            <div class="profile-alert info"><i class="fas fa-info-circle"></i> <?php echo $info; ?></div>
        <?php elseif (!empty($errors)): ?>
            <div class="profile-alert error">
                <i class="fas fa-exclamation-circle"></i> 
                <span><?php echo implode('<br>', $errors); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="profile-form" id="profileForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="avatar-edit-section">
                <div class="avatar-circle">
                    <img id="preview" src="<?php echo $display_img; ?>" alt="Profile">
                    <label for="imageUpload" class="camera-overlay" title="Upload New Photo">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <input type="file" name="profile_image" id="imageUpload" hidden accept="image/*">
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="userName">Full Name</label>
                    <input type="text" name="name" id="userName" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="userEmail">Email Address</label>
                    <input type="email" name="email" id="userEmail" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>

            <div class="password-divider">
                <span>Change Password</span>
                <small>Leave empty to keep current</small>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="newPass">New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="new_password" id="newPass" placeholder="••••••••">
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPass">Confirm Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirmPass" placeholder="••••••••">
                        <span class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
            </div>

            <button type="submit" class="save-btn" id="submitBtn" disabled>Save Changes</button>
        </form>

        <div class="profile-footer">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        const form = document.getElementById('profileForm');
        const submitBtn = document.getElementById('submitBtn');
        const imgInput = document.getElementById('imageUpload');
        
        const originalData = {
            name: document.getElementById('userName').value.trim(),
            email: document.getElementById('userEmail').value.trim()
        };

        const detectChanges = () => {
            const currentName = document.getElementById('userName').value.trim();
            const currentEmail = document.getElementById('userEmail').value.trim();
            const passValue = document.getElementById('newPass').value;
            const hasFile = imgInput.files.length > 0;

            const changed = 
                currentName !== originalData.name || 
                currentEmail !== originalData.email || 
                passValue.length > 0 || 
                hasFile;

            submitBtn.disabled = !changed;
        };

        form.addEventListener('input', detectChanges);

        imgInput.onchange = function() {
            const [file] = this.files;
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
                detectChanges();
            }
        };
    </script>
</body>
</html>