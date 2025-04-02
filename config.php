<?php
// config.php example
$host = "localhost";
$user = "root";
$password = "pass123";
$dbname = "oneclick_payments";

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$key_id = "rzp_test_3i3aUje3qXaxGE";
$key_secret = "Hbf7IwNppbLlKLsVbIBpmyJo";



$password="gcexrozggeaqwzmd";
$username="adityakulkarni54321@gmail.com";
$api_url = "https://api.razorpay.com/v1/orders";


?>
