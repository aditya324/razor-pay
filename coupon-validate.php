<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-API-KEY");

require 'vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

// Database connection
$conn = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_NAME']);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
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

// Validate coupon code
if (!isset($data['couponCode']) || empty($data['couponCode'])) {
    http_response_code(400);
    echo json_encode(["error" => "Coupon code is required"]);
    exit;
}

$couponCode = $conn->real_escape_string(trim($data['couponCode']));

// Check if coupon exists and is unused
$stmt = $conn->prepare("SELECT id, code, discount_amount, is_used FROM coupons WHERE code = ?");
$stmt->bind_param("s", $couponCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid coupon code"]);
    exit;
}

$coupon = $result->fetch_assoc();

if ($coupon['is_used'] == 1) {
    echo json_encode(["success" => false, "message" => "This coupon has already been used"]);
    exit;
}

// At this point, coupon is valid
echo json_encode([
    "success" => true, 
    "message" => "Coupon applied successfully!",
    "discount_amount" => $coupon['discount_amount'],
    "coupon_id" => $coupon['id']
]);