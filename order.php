<?php
header("Content-Type: application/json");
// In production, restrict allowed origins to your trusted domains
// header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Retrieve API credentials from environment variables
$key_id = $_ENV['RAZORPAY_KEY_ID'];
$key_secret = $_ENV['RAZORPAY_KEY_SECRET'];

// Simple API key check (customize as needed)
$headers = getallheaders();
$apiKey = $headers['X-API-KEY'] 
    ?? $headers['x-api-key'] 
    ?? $_SERVER['HTTP_X_API_KEY'] 
    ?? null;

if ($apiKey !== 'hDRFkvaUct0SONDDFzMjyQHC') {
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

// Validate and sanitize input fields
$name = isset($data['name']) ? trim(filter_var($data['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH)) : null;
$email = isset($data['email']) ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : null;
$phone = isset($data['phone']) ? preg_replace('/[^0-9+]/', '', $data['phone']) : null;
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$mode = isset($data['mode']) ? filter_var($data['mode'], FILTER_SANITIZE_STRING) : null;

// Enhanced validation checks
$errors = [];
if (!$name || strlen($name) < 2 || strlen($name) > 100 || !preg_match('/^[\p{L}\p{M}\s.\'-]+$/u', $name)) {
    $errors[] = "Name must be 2-100 characters with only letters, spaces, hyphens, apostrophes or periods";
}

if (!$email) {
    $errors[] = "Invalid email address";
}

if (!$phone || !preg_match('/^[0-9+]{10,15}$/', $phone)) {
    $errors[] = "Phone must be 10-15 digits (may include + prefix)";
}

if ($amount <= 0) {
    $errors[] = "Amount must be a positive number";
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(["error" => "Validation failed", "details" => $errors]);
    exit;
}

error_log("Received amount: " . $amount);
// Optionally log non-sensitive operational data (avoid logging sensitive fields)
// error_log("Order mode: " . $mode);

$orderData = [
    "amount" => $amount,
    "currency" => "INR",
    "receipt" => "order_" . uniqid(),
    "payment_capture" => 1
];

$api_url = "https://api.razorpay.com/v1/orders";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Enforce SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);

// Check for cURL errors
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    http_response_code(500);
    echo json_encode(["error" => "cURL error", "details" => $error_msg]);
    exit;
}

$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status != 200) {
    http_response_code($http_status);
    echo json_encode(["error" => "Failed to create order", "details" => $response]);
    exit;
}

$order = json_decode($response, true);
echo json_encode($order);
?>