<?php
session_start();

// Database credentials -- match those in index.php
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

$error = '';
$success = '';

// show registration message when redirected from index.php
if (isset($_GET['registered'])) {
    $success = 'Registration successful. Please log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Redirect to protected page after login
                header('Location: protected.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ApexClone</title>
    <link rel="stylesheet" href="/styles/login.css">
</head>
<body>
    <main>
        <div class="container">
            <section class="nadpis">
                <img src="/imgs/apexlogo.png" alt="">
                <h1>Sign In</h1>
            </section>

            <?php if ($error): ?>
                <div class="error" style="color:#c00;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success" style="color:#0a0;"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="index.php">Go to home</a> — or <a href="index.php?logout=1">Log out</a></p>
            <?php else: ?>
                <section class="form">
                    <form method="post" action="">
                        <p>Email</p>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        <p>Password</p>
                        <input type="password" name="password" required>
                        <button type="submit">Sign In</button>
                    </form>
                </section>
                <section class="sign_in">
                    <div class="sign_in_card">
                        <p>Don't have an account? </p>
                        <a href="index.php">Create account</a>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
