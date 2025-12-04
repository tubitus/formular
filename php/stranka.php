<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$dbhost = '127.0.0.1';
$dbname = 'apexclone';
$dbuser = 'root';
$dbpass = 'root';

try {
    $pdo = new PDO("mysql:host={$dbhost};dbname={$dbname};charset=utf8mb4", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch current user data
$stmt = $pdo->prepare('SELECT username, email, profile_picture, profile_picture_type FROM users WHERE id = :id');
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();

// Handle username change
if (isset($_POST['change_username'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    
    if ($new_username === '') {
        $error = 'Username cannot be empty.';
    } elseif (strlen($new_username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE users SET username = :username WHERE id = :id');
            $stmt->execute([':username' => $new_username, ':id' => $user_id]);
            $_SESSION['username'] = $new_username;
            $user['username'] = $new_username;
            $success = 'Username updated successfully.';
        } catch (PDOException $e) {
            $error = 'Error updating username: ' . $e->getMessage();
        }
    }
}

// Handle email change
if (isset($_POST['change_email'])) {
    $new_email = trim($_POST['new_email'] ?? '');
    
    if ($new_email === '') {
        $error = 'Email cannot be empty.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
            $stmt->execute([':email' => $new_email, ':id' => $user_id]);
            $user['email'] = $new_email;
            $success = 'Email updated successfully.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'This email is already in use.';
            } else {
                $error = 'Error updating email: ' . $e->getMessage();
            }
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($current_password === '' || $new_password === '' || $confirm_password === '') {
        $error = 'Please fill all password fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
        $stmt->execute([':id' => $user_id]);
        $current_user = $stmt->fetch();
        
        if (!password_verify($current_password, $current_user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
                $stmt->execute([':password' => $password_hash, ':id' => $user_id]);
                $success = 'Password updated successfully.';
            } catch (PDOException $e) {
                $error = 'Error updating password: ' . $e->getMessage();
            }
        }
    }
}

// Handle profile picture upload
if (isset($_POST['upload_picture'])) {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid image file.';
    } else {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Only JPG, PNG, and GIF files are allowed.';
        } elseif ($file['size'] > $max_size) {
            $error = 'File size must be less than 5MB.';
        } else {
            $image_data = file_get_contents($file['tmp_name']);
            
            try {
                $stmt = $pdo->prepare('UPDATE users SET profile_picture = :pic, profile_picture_type = :type WHERE id = :id');
                $stmt->execute([
                    ':pic' => $image_data,
                    ':type' => $file['type'],
                    ':id' => $user_id
                ]);
                $user['profile_picture'] = $image_data;
                $user['profile_picture_type'] = $file['type'];
                $success = 'Profile picture updated successfully.';
            } catch (PDOException $e) {
                $error = 'Error saving profile picture: ' . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management - ApexClone</title>
    <link rel="stylesheet" href="/styles/stranka.css">
</head>
<body>
    <div class="account-container">
        <div class="account-header">
            <h1>Account Settings</h1>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Profile Picture Section -->
        <section class="account-section">
            <h2>Profile Picture</h2>
            <form method="post" enctype="multipart/form-data" id="pic-form">
                <input type="file" name="profile_picture" id="profile_picture" accept="image/*" style="display:none;">
                <input type="hidden" name="upload_picture" value="1">
                <div class="profile-pic-container">
                    <?php if ($user['profile_picture']): ?>
                        <img src="data:<?php echo htmlspecialchars($user['profile_picture_type']); ?>;base64,<?php echo base64_encode($user['profile_picture']); ?>" alt="Profile Picture" class="profile-pic" onclick="document.getElementById('profile_picture').click();">
                    <?php else: ?>
                        <div class="profile-pic-placeholder" onclick="document.getElementById('profile_picture').click();">Click to Upload</div>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- Username Change Section -->
        <section class="account-section">
            <h2>Change Username</h2>
            <form method="post" class="account-form">
                <p>Current Username: <strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
                <input type="text" name="new_username" placeholder="Enter new username" required>
                <button type="submit" name="change_username">Update Username</button>
            </form>
        </section>

        <!-- Email Change Section -->
        <section class="account-section">
            <h2>Change Email</h2>
            <form method="post" class="account-form">
                <p>Current Email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
                <input type="email" name="new_email" placeholder="Enter new email" required>
                <button type="submit" name="change_email">Update Email</button>
            </form>
        </section>

        <!-- Password Change Section -->
        <section class="account-section">
            <h2>Change Password</h2>
            <form method="post" class="account-form">
                <input type="password" name="current_password" placeholder="Current password" required>
                <input type="password" name="new_password" placeholder="New password" required>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
                <button type="submit" name="change_password">Update Password</button>
            </form>
        </section>
    </div>
    <script>
        const fileInput = document.getElementById('profile_picture');
        const picForm = document.getElementById('pic-form');
        
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                picForm.submit();
            }
        });
    </script>
</body>
</html>