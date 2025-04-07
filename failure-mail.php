<?php
require('config.php');
require './vendor/autoload.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    file_put_contents('mail-debug.txt', file_get_contents("php://input"));

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['email'])) {
        http_response_code(400);
        echo "Invalid data";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $username; // from config.php
        $mail->Password = $password; // from config.php
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($username, 'One Click Academy - Payment System');
        $mail->addAddress($username);
        $mail->addReplyTo($data['email'], $data['name']);

        $mail->isHTML(false);
        $mail->Subject = 'Payment Failure - Order ID: ' . $data['order_id'];

        $mail->Body = "A payment failure occurred:\n\n" .
            "Order ID: {$data['order_id']}\n" .
            "Name: {$data['name']}\n" .
            "Email: {$data['email']}\n" .
            "Phone: {$data['phone']}\n" .
            "Address: {$data['address']}\n" .
            "Course: {$data['course']}\n" .
            "Amount: ₹" . number_format($data['amount'] / 100, 2) . "\n" .
            "Mode: {$data['mode']}\n\n" .
            "Please investigate and contact the student if necessary.";

        $mail->send();
        echo "Success";
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        http_response_code(500);
        echo "Mailer error";
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
