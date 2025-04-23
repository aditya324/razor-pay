<?php
header("Content-Type: application/json");
// In production, restrict allowed origins to your trusted domain(s)
// header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection using environment variables
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed", "details" => $conn->connect_error]);
    exit;
}

// Retrieve API and SMTP credentials from .env
$key_secret = $_ENV['RAZORPAY_KEY_SECRET'];
$smtp_username = $_ENV['SMTP_USERNAME'];
$smtp_password = $_ENV['SMTP_PASSWORD'];
$admin_email = $_ENV['SMTP_USERNAME']; // Using SMTP username as admin email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Optional: Simple API key check
$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] 
    ?? $headers['x-api-key'] 
    ?? $_SERVER['HTTP_X_API_KEY'] 
    ?? null;

if ($apiKey !== 'uk935K7p4j96UCJgHK8kAU4q') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized auth key error"]);
    exit;
}

// Retrieve and decode JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON input"]);
    exit;
}

// Validate required fields
if (!isset($data['payment_id'], $data['order_id'], $data['signature'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required payment fields"]);
    exit;
}

// Sanitize required inputs
$payment_id = filter_var($data['payment_id'], FILTER_SANITIZE_STRING);
$order_id   = filter_var($data['order_id'], FILTER_SANITIZE_STRING);
$signature  = filter_var($data['signature'], FILTER_SANITIZE_STRING);

// Validate and sanitize optional fields
$name    = isset($data['name'])    ? filter_var($data['name'], FILTER_SANITIZE_STRING) : '';
$email   = isset($data['email'])   ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : '';
$phone   = isset($data['phone'])   ? filter_var($data['phone'], FILTER_SANITIZE_STRING) : '';
$city    = isset($data['city'])    ? filter_var($data['city'], FILTER_SANITIZE_STRING) : '';
$address = isset($data['address']) ? filter_var($data['address'], FILTER_SANITIZE_STRING) : '';
$amount  = isset($data['amount'])  ? filter_var($data['amount'], FILTER_VALIDATE_FLOAT) : 0;
$mode    = isset($data['mode'])    ? filter_var($data['mode'], FILTER_SANITIZE_STRING) : '';

// Process coupon data if available
$coupon_id = isset($data['coupon_id']) ? (int)$data['coupon_id'] : null;
$discount_amount = isset($data['discount_amount']) ? (float)$data['discount_amount'] : 0;

// Ensure amount is valid
if ($amount === false) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid amount"]);
    exit;
}

// Convert amount to rupees (assuming the amount is in paisa)
$toRupees = $amount / 100;

// Verify the payment signature using key_secret from .env
$generated_signature = hash_hmac("sha256", $order_id . "|" . $payment_id, $key_secret);
if ($generated_signature !== $signature) {
    http_response_code(400);
    echo json_encode(["error" => "Payment verification failed"]);
    exit;
}

// Prepare the SQL statement using prepared statements
$stmt = $conn->prepare("INSERT INTO payments (order_id, payment_id, name, email, phone, city, address, amount, status, mode, coupon_id, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Database error", "details" => $conn->error]);
    exit;
}

$status = "success";
$stmt->bind_param("sssssssissid", $order_id, $payment_id, $name, $email, $phone, $city, $address, $toRupees, $status, $mode, $coupon_id, $discount_amount);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["error" => "Payment Failed", "details" => $stmt->error]);
    exit;
}
$stmt->close();

// If coupon was used, ensure it's marked as used
if ($coupon_id) {
    $updateStmt = $conn->prepare("UPDATE coupons SET is_used = 1, used_by = ?, used_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("si", $email, $coupon_id);
    $updateStmt->execute();
    $updateStmt->close();
}

// Function to send email
function sendEmail($mail, $recipientEmail, $recipientName, $subject, $body) {
    try {
        $mail->clearAddresses();
        $mail->addAddress($recipientEmail, $recipientName);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed to $recipientEmail: " . $mail->ErrorInfo);
        return false;
    }
}

// Setup PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_username;
    $mail->Password   = $smtp_password;
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('oneclickacademy@gmail.com', 'One Click Academy');
    $mail->isHTML(true);
    
    // Embed the logo image for both emails
    $mail->addEmbeddedImage('./images/newlogo2.png', 'logo_cid');

    // Get coupon details if available
    $couponInfo = "";
    if ($coupon_id) {
        $couponStmt = $conn->prepare("SELECT code FROM coupons WHERE id = ?");
        $couponStmt->bind_param("i", $coupon_id);
        $couponStmt->execute();
        $couponResult = $couponStmt->get_result();
        if ($couponResult->num_rows > 0) {
            $couponData = $couponResult->fetch_assoc();
            $couponInfo = "<strong>Coupon Applied:</strong> " . htmlspecialchars($couponData['code']) . "<br>
                          <strong>Discount Amount:</strong> ₹" . htmlspecialchars($discount_amount) . "<br>";
        }
        $couponStmt->close();
    }

    // Customer email body
    $customerEmailBody = "
        <html>
        <body>
            <img src='cid:logo_cid' alt='Logo' style='width:150px;'><br><br>
            Dear " . htmlspecialchars($name) . ",<br><br>
            Thank you for your purchase. Your payment details are as follows:<br><br>
            <strong>Payment ID:</strong> " . htmlspecialchars($payment_id) . "<br>
            <strong>Order ID:</strong> " . htmlspecialchars($order_id) . "<br>
            <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
            <strong>Phone no:</strong> " . htmlspecialchars($phone) . "<br>
            <strong>Mode of Teaching:</strong> " . htmlspecialchars($mode) . "<br>
            " . $couponInfo . "
            <strong>Amount Paid:</strong> ₹" . htmlspecialchars($toRupees) . "<br>
            <strong>State:</strong> " . htmlspecialchars($address) . "<br>
            <strong>City:</strong> " . htmlspecialchars($city) . "<br><br>
            We appreciate your business.<br><br>
            Best regards,<br>
            OneClick Academy
        </body>
        </html>
    ";

    // Admin email body
    $adminEmailBody = "
        <html>
        <body>
            <img src='cid:logo_cid' alt='Logo' style='width:150px;'><br><br>
            New payment received!<br><br>
            <strong>Customer Name:</strong> " . htmlspecialchars($name) . "<br>
            <strong>Payment ID:</strong> " . htmlspecialchars($payment_id) . "<br>
            <strong>Order ID:</strong> " . htmlspecialchars($order_id) . "<br>
            <strong>Email:</strong> " . htmlspecialchars($email) . "<br>
            <strong>Phone no:</strong> " . htmlspecialchars($phone) . "<br>
            <strong>Mode of Teaching:</strong> " . htmlspecialchars($mode) . "<br>
            " . $couponInfo . "
            <strong>Amount Paid:</strong> ₹" . htmlspecialchars($toRupees) . "<br>
            <strong>State:</strong> " . htmlspecialchars($address) . "<br>
            <strong>City:</strong> " . htmlspecialchars($city) . "<br><br>
            Payment confirmed at: " . date('Y-m-d H:i:s') . "<br>
        </body>
        </html>
    ";

    // Send customer email
    $customerEmailSent = false;
    if ($email) {
        $customerEmailSent = sendEmail($mail, $email, $name, 'Payment Confirmation', $customerEmailBody);
    }

    // Send admin email
    $adminEmailSent = sendEmail($mail, $admin_email, 'Admin', 'New Payment Received', $adminEmailBody);

    $response = [
        "message" => "Payment successful",
        "redirect" => "success.html",
        "data" => [
            "name" => $name,
            "email" => $email,
            "phone" => $phone,
            "city" => $city,
            "order_id" => $order_id,
            "address" => $address,
            "coupon_applied" => $coupon_id ? true : false,
            "discount_amount" => $discount_amount
        ]
    ];

    // Add email status to response
    if ($email && !$customerEmailSent) {
        $response['email_status'] = "Customer email failed to send";
    }
    if (!$adminEmailSent) {
        $response['admin_email_status'] = "Admin email failed to send";
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(["message" => "Payment successful, but email sending failed", "error" => $mail->ErrorInfo]);
}
?>