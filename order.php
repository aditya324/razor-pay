<?php
header("Content-Type: application/json");
// In production, restrict allowed origins to your trusted domains
// header("Access-Control-Allow-Origin: https://yourdomain.com");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

require('config.php'); // Ensure this file is stored securely and outside the web root if possible.
require 'vendor/autoload.php';

// Simple API key check (customize as needed)
$headers = getallheaders();
if (!isset($headers['X-API-KEY']) || $headers['X-API-KEY'] !== 'your_secret_api_key') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
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
$name = isset($data['name']) ? filter_var($data['name'], FILTER_SANITIZE_STRING) : null;
$email = isset($data['email']) ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : null;
$phone = isset($data['phone']) ? filter_var($data['phone'], FILTER_SANITIZE_STRING) : null;
$amount = isset($data['amount']) ? filter_var($data['amount'], FILTER_VALIDATE_FLOAT) : null;
$mode = isset($data['mode']) ? filter_var($data['mode'], FILTER_SANITIZE_STRING) : null;

// Check that required fields are valid
if (!$name || !$email || !$phone || !$amount) {
    http_response_code(400);
    echo json_encode(["error" => "Missing or invalid required fields"]);
    exit;
}

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
