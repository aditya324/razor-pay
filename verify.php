<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require('config.php');  // This file should also set up your $conn for DB connection

$data = json_decode(file_get_contents("php://input"), true);

$payment_id = $data['payment_id'];
$order_id   = $data['order_id'];
$signature  = $data['signature'];
$name       = isset($data['name']) ? $data['name'] : '';
$email      = isset($data['email']) ? $data['email'] : '';
$phone      = isset($data['phone']) ? $data['phone'] : '';
$city       = isset($data['city']) ? $data['city'] : '';
$address    = isset($data['address']) ? $data['address'] : '';

// Generate the expected signature
$generated_signature = hash_hmac("sha256", $order_id . "|" . $payment_id, $key_secret);

if ($generated_signature === $signature) {
    // Payment verified successfully.
    // Prepare an SQL statement to insert the payment and user details into the database.
    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_id, name, email, phone, city, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $status = "success";  // You can change or extend this status as needed.
    $stmt->bind_param("ssssssss", $order_id, $payment_id, $name, $email, $phone, $city, $address, $status);
    
    if($stmt->execute()){
        echo json_encode(["message" => "Payment successful"]);
    } else {
        echo json_encode(["message" => "Payment Failed", "error" => $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["message" => "Payment verification failed"]);
}
?>
