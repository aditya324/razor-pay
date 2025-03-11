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
$amount = $data['amount']; 



$orderData = [
    "amount" => $amount,
    "currency" => "INR",
    "receipt" => "order_" . uniqid(),
    "payment_capture" => 1
];

// Razorpay API endpoint
$api_url = "https://api.razorpay.com/v1/orders";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_USERPWD, $key_id . ":" . $key_secret); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_status != 200) {
    echo json_encode(["error" => "Failed to create order", "details" => $response]);
    exit;
}


$order = json_decode($response, true);
echo json_encode($order);
?>