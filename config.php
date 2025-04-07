<?php
// config.php example
$host = "localhost";
$user = "oneclick";
$password = "PxsdQgTPmZ9gFInG4URy";
$dbname = "oneclickpayments";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$key_id = "rzp_test_3i3aUje3qXaxGE";
$key_secret = "Hbf7IwNppbLlKLsVbIBpmyJo";



$password="zggjywigeuhqyikl";
$username=" oneclickacademy1@gmail.com";
$api_url = "https://api.razorpay.com/v1/orders";


?>
