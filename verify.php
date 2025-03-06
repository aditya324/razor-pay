<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
require('config.php');

$data = json_decode(file_get_contents("php://input"), true);

$payment_id = $data['payment_id'];
$order_id = $data['order_id'];
$signature = $data['signature'];

$generated_signature = hash_hmac("sha256", $order_id . "|" . $payment_id, $key_secret);

if ($generated_signature === $signature) {
    echo json_encode(["message" => "Payment successful"]);
} else {
    echo json_encode(["message" => "Payment verification failed"]);
}
