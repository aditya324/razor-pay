<?php
// coupon-management.php - Admin interface for managing coupons
// NOTE: This should be protected by admin authentication

session_start();

// Secure session validation
function validateAdminSession() {
    // Check if all session variables are set
    if (!isset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['user_agent'], $_SESSION['ip_address'])) {
        return false;
    }
    
    // Validate session fingerprint
    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] || $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        return false;
    }
    
    return true;
}

// Redirect to login if not authenticated
if (!validateAdminSession()) {
    // Clear all session data
    $_SESSION = array();
    session_destroy();
    
    // Redirect to login
    header('Location: admin-login.php');
    exit;
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submissions
$message = '';
$error = '';

// Add new coupon
if (isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount_amount = floatval($_POST['discount_amount']);
    
    // Validate inputs
    if (empty($code)) {
        $error = "Coupon code cannot be empty";
    } elseif ($discount_amount <= 0) {
        $error = "Discount amount must be greater than zero";
    } else {
        // Check if code already exists
        $stmt = $conn->prepare("SELECT id FROM coupons WHERE code = ?");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Coupon code already exists";
        } else {
            // Insert new coupon
            $insertStmt = $conn->prepare("INSERT INTO coupons (code, discount_amount) VALUES (?, ?)");
            $insertStmt->bind_param("sd", $code, $discount_amount);
            
            if ($insertStmt->execute()) {
                $message = "Coupon added successfully";
            } else {
                $error = "Error adding coupon: " . $insertStmt->error;
            }
            $insertStmt->close();
        }
        $stmt->close();
    }
}

// Delete coupon
if (isset($_POST['delete_coupon'])) {
    $coupon_id = intval($_POST['coupon_id']);
    
    $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->bind_param("i", $coupon_id);
    
    if ($stmt->execute()) {
        $message = "Coupon deleted successfully";
    } else {
        $error = "Error deleting coupon: " . $stmt->error;
    }
    $stmt->close();
}

// Reset coupon usage status
if (isset($_POST['reset_coupon'])) {
    $coupon_id = intval($_POST['coupon_id']);
    
    $stmt = $conn->prepare("UPDATE coupons SET is_used = 0, used_by = NULL, used_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $coupon_id);
    
    if ($stmt->execute()) {
        $message = "Coupon usage reset successfully";
    } else {
        $error = "Error resetting coupon: " . $stmt->error;
    }
    $stmt->close();
}

// Generate multiple coupons
if (isset($_POST['generate_coupons'])) {
    $prefix = trim($_POST['prefix']);
    $count = intval($_POST['count']);
    $discount_amount = floatval($_POST['bulk_discount_amount']);
    
    if (empty($prefix)) {
        $error = "Prefix cannot be empty";
    } elseif ($count <= 0 || $count > 100) {
        $error = "Count must be between 1 and 100";
    } elseif ($discount_amount <= 0) {
        $error = "Discount amount must be greater than zero";
    } else {
        $successful = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));
            $code = $prefix . $random;
            
            $stmt = $conn->prepare("INSERT INTO coupons (code, discount_amount) VALUES (?, ?)");
            $stmt->bind_param("sd", $code, $discount_amount);
            
            if ($stmt->execute()) {
                $successful++;
            }
            $stmt->close();
        }
        
        if ($successful > 0) {
            $message = "Generated $successful coupon(s) successfully";
        } else {
            $error = "Failed to generate coupons";
        }
    }
}

// Get all coupons
$result = $conn->query("SELECT id, code, discount_amount, is_used, used_by, used_at, created_at FROM coupons ORDER BY created_at DESC");
$coupons = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $coupons[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - One Click Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>


    <style>/* Add these styles to your existing CSS or include in a style tag */

/* Coupon input styling */
#couponCode:disabled {
  background-color: #f0f0f0;
  color: #666;
}

/* Coupon message styling */
.coupon-success {
  color: #10b981;
  font-weight: 500;
}

.coupon-error {
  color: #ef4444;
}

/* Applied discount styling */
.discount-price {
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

.original-price {
  text-decoration: line-through;
  color: #6b7280;
  font-size: 0.875rem;
}

.discount-badge {
  background-color: #10b981;
  color: white;
  font-size: 0.75rem;
  padding: 2px 6px;
  border-radius: 9999px;
}

/* Remove coupon button */
#removeCoupon {
  color: #ef4444;
  font-size: 0.875rem;
  text-decoration: underline;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  margin-left: 8px;
}

#removeCoupon:hover {
  color: #b91c1c;
}</style>
</head>
<body class="bg-gray-100">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Coupon Management</h1>
        
        <?php if (!empty($message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $message; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Add New Coupon -->
            <div class="bg-white p-6 rounded shadow">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New Coupon</h2>
                <form method="post" action="">
                    <div class="mb-4">
                        <label for="code" class="block text-gray-700 font-medium mb-2">Coupon Code</label>
                        <input type="text" id="code" name="code" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="discount_amount" class="block text-gray-700 font-medium mb-2">Discount Amount (₹)</label>
                        <input type="number" id="discount_amount" name="discount_amount" step="0.01" min="0" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <button type="submit" name="add_coupon" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        Add Coupon
                    </button>
                </form>
            </div>
            
            <!-- Generate Multiple Coupons -->
            <div class="bg-white p-6 rounded shadow">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Generate Multiple Coupons</h2>
                <form method="post" action="">
                    <div class="mb-4">
                        <label for="prefix" class="block text-gray-700 font-medium mb-2">Prefix</label>
                        <input type="text" id="prefix" name="prefix" class="w-full px-3 py-2 border rounded" required>
                        <p class="text-sm text-gray-500 mt-1">E.g. "SUMMER" will generate codes like "SUMMER7A2B3"</p>
                    </div>
                    <div class="mb-4">
                        <label for="count" class="block text-gray-700 font-medium mb-2">Count</label>
                        <input type="number" id="count" name="count" min="1" max="100" value="5" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label for="bulk_discount_amount" class="block text-gray-700 font-medium mb-2">Discount Amount (₹)</label>
                        <input type="number" id="bulk_discount_amount" name="bulk_discount_amount" step="0.01" min="0" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <button type="submit" name="generate_coupons" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        Generate Coupons
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Coupons List -->
        <div class="mt-8 bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">All Coupons</h2>
            
            <?php if (empty($coupons)): ?>
                <p class="text-gray-500">No coupons found</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b text-left">Code</th>
                                <th class="py-2 px-4 border-b text-left">Discount (₹)</th>
                                <th class="py-2 px-4 border-b text-left">Status</th>
                                <th class="py-2 px-4 border-b text-left">Used By</th>
                                
                                <th class="py-2 px-4 border-b text-left">Used At</th>
                                <th class="py-2 px-4 border-b text-left">Created At</th>
                                <th class="py-2 px-4 border-b text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($coupon['code']); ?></td>
                                    <td class="py-2 px-4 border-b">₹<?php echo htmlspecialchars($coupon['discount_amount']); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <?php if ($coupon['is_used']): ?>
                                            <span class="inline-block bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Used</span>
                                        <?php else: ?>
                                            <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs">Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-2 px-4 border-b"><?php echo $coupon['used_by'] ? htmlspecialchars($coupon['used_by']) : '-'; ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo $coupon['used_at'] ? htmlspecialchars($coupon['used_at']) : '-'; ?></td>
                                    <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($coupon['created_at']); ?></td>
                                    <td class="py-2 px-4 border-b">
                                        <div class="flex space-x-2">
                                            <?php if ($coupon['is_used']): ?>
                                                <form method="post" action="" onsubmit="return confirm('Are you sure you want to reset this coupon?');">
                                                    <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                    <button type="submit" name="reset_coupon" class="text-blue-500 hover:text-blue-700">Reset</button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                                <button type="submit" name="delete_coupon" class="text-red-500 hover:text-red-700">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>