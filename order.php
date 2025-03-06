<?php
header("Content-Type: application/json");


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require('config.php'); // Include API keys



$data = json_decode(file_get_contents("php://input"), true);
$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'];
$amount = 3000000; // Amount in paisa (30,000 INR = 3,00,000 paise)

$key_id = "rzp_test_J60bqBOi1z1aF5"; // Replace with your actual key
$key_secret = "uk935K7p4j96UCJgHK8kAU4q";
// Create order request payload
$orderData = [
    "amount" => $amount,
    "currency" => "INR",
    "receipt" => "order_" . uniqid(),
    "payment_capture" => 1 // Auto-capture payment
];

// Razorpay API endpoint
$api_url = "https://api.razorpay.com/v1/orders";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret); // Authentication
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debugging: Check if API returns an error
if ($http_status != 200) {
    echo json_encode(["error" => "Failed to create order", "details" => $response]);
    exit;
}

// Return order response
$order = json_decode($response, true);
echo json_encode($order);
?>
