<?php
session_start();

// Database connection and form handling
// Update $dbuser and $dbpass to match your environment
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
    // In production, avoid echoing raw errors
    die('Database connection failed: ' . $e->getMessage());
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (!$terms) {
        $error = 'You must agree to the Terms of Service and Privacy Policy.';
    } else {
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $passwordHash,
            ]);
            // After successful registration redirect to login page
            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            // Handle duplicate email or other DB errors
            if ($e->getCode() === '23000') {
                $error = 'An account with this email already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// add logout handling (placed before any output)
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account - ApexClone</title>
    <link rel="stylesheet" href="/styles/style.css">
    <script src="/script/script.js" defer></script>
</head>
<body>
    <header>
        
    </header>
    <main>
        <dev class="container">
            <section class="nadpis">
                <img src="/imgs/apexlogo.png" alt="">
                <h1>Create an account</h1>
            </section>
            <section class="form">
                <!-- show messages -->
                <?php if ($error): ?>
                    <div class="error" style="color: #c00; margin-bottom:8px;"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success" style="color: #0a0; margin-bottom:8px;"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- form: method set to POST, inputs given name attributes -->
                <form method="post" action="">
                    <p>Username</p>
                    <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    <p>Email</p>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    <p>Password</p>
                    <input type="password" name="password" required>
                    <div class="terms">
                        <input type="checkbox" name="terms" id="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> > 
                        <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
                    </div>
                    <button type="submit">Sign Up</button>
                </form>
            <section class="sign_in">
                <div class="sign_in_card">
                    <p>Already have an account? </p>
                    <a href="login.php">Sign In</a>
                </div>
            </section>
        </dev>
    </main>
    <footer>
        
    </footer>
</body>
</html>

<?php
?>