<?php
// failure.php
require('config.php');
require './vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Validate and sanitize input
$order_id    = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING) ?? 'N/A';
$name        = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_STRING) ?? 'N/A';
$email       = filter_input(INPUT_GET, 'email', FILTER_SANITIZE_EMAIL) ?? 'N/A';
$phone       = filter_input(INPUT_GET, 'phone', FILTER_SANITIZE_STRING) ?? 'N/A';
$address     = filter_input(INPUT_GET, 'address', FILTER_SANITIZE_STRING) ?? 'N/A';
$course      = filter_input(INPUT_GET, 'course', FILTER_SANITIZE_STRING) ?? 'N/A';
$totalAmount = filter_input(INPUT_GET, 'amount', FILTER_SANITIZE_NUMBER_INT) ?? 0;
$amount      = $totalAmount > 0 ? ($totalAmount / 100) : 0;
$payment_id  = filter_input(INPUT_GET, 'payment_id', FILTER_SANITIZE_STRING) ?? 'N/A';

// Email sending logic
$emailSent = false;
$emailError = '';
$admin_email = $username; // From config.php

if (!empty($admin_email) && filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = $username; // From config.php
        $mail->Password   = $password; // From config.php
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS
        $mail->Port       = 587;
        $mail->CharSet = 'UTF-8';
        
        // Recipients
        $mail->setFrom($username, 'Payment System'); // From address
        $mail->addAddress($admin_email, 'Admin');
        $mail->addReplyTo($email, $name); // Allow admin to reply to customer
        
        // Content
        $mail->isHTML(false); // Set email format to plain text
        $mail->Subject = 'Payment Failure Notification - Order ID: ' . $order_id;
        
        $body = "Hello Admin,\n\n" .
                "A payment failure occurred with the following details:\n\n" .
                "Order ID: $order_id\n" .
                "Name: $name\n" .
                "Email: $email\n" .
                "Phone: $phone\n" .
                "Address: $address\n" .
                "Course: $course\n" .
                "Total Amount: ₹$amount\n" .
                "Payment ID: $payment_id\n\n" .
                "Please investigate the issue and contact the customer if needed.\n\n" .
                "Regards,\nYour Website Team";
        
        $mail->Body = $body;
        
        // Send email
        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
        $emailError = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        error_log($emailError);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 700px;
            margin-top: 30px;
            margin-bottom: 50px;
        }
        .alert-box {
            border-left: 5px solid #dc3545;
            border-radius: 0;
        }
        .summary-table th {
            width: 30%;
            background-color: #f1f1f1;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="card shadow">
            <div class="card-header bg-danger text-white">
                <h2 class="mb-0"><i class="bi bi-x-circle-fill"></i> Payment Failed</h2>
            </div>
            
            <div class="card-body">
                <div class="alert alert-danger alert-box">
                    <h4 class="alert-heading">Oops! Payment Unsuccessful</h4>
                    <p>We encountered an issue processing your payment. Please try again or contact our support team for assistance.</p>
                    <hr>
                    <p class="mb-0">Your order has not been confirmed. No amount has been deducted from your account.</p>
                </div>

                <?php if ($emailError): ?>
                <div class="alert alert-warning">
                    <small>Note: We couldn't notify the admin about this failure. Please contact support.</small>
                </div>
                <?php endif; ?>

                <h4 class="mt-4 mb-3">Order Summary</h4>
                <table class="table table-bordered summary-table">
                    <tbody>
                        <tr>
                            <th>Order ID</th>
                            <td><?php echo htmlspecialchars($order_id); ?></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td><?php echo htmlspecialchars($name); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($email); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo htmlspecialchars($phone); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo htmlspecialchars($address); ?></td>
                        </tr>
                        <tr>
                            <th>Course</th>
                            <td><?php echo htmlspecialchars($course); ?></td>
                        </tr>
                        <tr>
                            <th>Amount</th>
                            <td>₹<?php echo number_format($amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Payment ID</th>
                            <td><?php echo htmlspecialchars($payment_id); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                    <a href="payment.php" class="btn btn-primary btn-lg px-4 me-md-2">
                        <i class="bi bi-arrow-repeat"></i> Try Payment Again
                    </a>
                    <a href="contact.php" class="btn btn-outline-secondary btn-lg px-4">
                        <i class="bi bi-headset"></i> Contact Support
                    </a>
                </div>
            </div>
            
            <div class="card-footer text-muted">
                <small>For any queries, please email us at <?php echo htmlspecialchars($admin_email); ?></small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>