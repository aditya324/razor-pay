<?php
session_start();
require 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: coupon-management.php');
    exit;
}

// Database connection
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        
        // Input validation
        if (empty($username) || empty($password)) {
            $error = "Please enter both username and password";
        } elseif (strlen($username) > 50 || strlen($password) > 72) {
            $error = "Invalid input length";
        } else {
            // Get admin user
            $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $admin['password'])) {
                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    
                    // Update last login
                    $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->bind_param("i", $admin['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Redirect to secure page
                    header('Location: coupon-management.php');
                    exit;
                }
            }
            
            // Generic error message to prevent username enumeration
            $error = "Invalid username or password";
            // Log failed login attempt (you should implement proper logging)
            error_log("Failed login attempt for username: $username");
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Secure Access</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f8fafc;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding-top: 5rem;
        }
        .login-box {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.5);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1 class="text-2xl font-bold text-center mb-6">Admin Portal</h1>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 mb-2">Username</label>
                    <input type="text" id="username" name="username" class="form-input" required autocomplete="username">
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="form-input" required autocomplete="current-password">
                </div>
                
                <button type="submit" name="login" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>