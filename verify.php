<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require('config.php'); 

require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents("php://input"), true);

$payment_id = $data['payment_id'];
$order_id   = $data['order_id'];
$signature  = $data['signature'];
$name       = isset($data['name']) ? $data['name'] : '';
$email      = isset($data['email']) ? $data['email'] : '';
$phone      = isset($data['phone']) ? $data['phone'] : '';
$city       = isset($data['city']) ? $data['city'] : '';
$address    = isset($data['address']) ? $data['address'] : '';
$amount     = isset($data['amount']) ? $data['amount'] : 0;

$toRupees = $amount / 100;

$generated_signature = hash_hmac("sha256", $order_id . "|" . $payment_id, $key_secret);

if ($generated_signature === $signature) {
    
    $stmt = $conn->prepare("INSERT INTO payments (order_id, payment_id, name, email, phone, city, address, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $status = "success";  
    $stmt->bind_param("sssssssis", $order_id, $payment_id, $name, $email, $phone, $city, $address, $toRupees, $status);
    
    if ($stmt->execute()) {
     
        $mail = new PHPMailer(true); 
        
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = $username; 
            $mail->Password   = $password; 
            $mail->SMTPSecure = 'tls';            
            $mail->Port       = 587;              

            $mail->setFrom('oneclickacademy@gmail.com', 'One Click Academy');
            $mail->addAddress($email, $name);

            // Set email format to HTML
            $mail->isHTML(true);
            $mail->Subject = 'Payment Confirmation';
            
         
            $mail->addEmbeddedImage('./images/oneclicklogo.png', 'logo_cid'); 

            $mail->Body = "
                <html>
                <body>
                    <img src='cid:logo_cid' alt='Logo' style='width:150px;'><br><br>
                    Dear $name,<br><br>
                    Thank you for your purchase. Your payment details are as follows:<br><br>
                    <strong>Payment ID:</strong> $payment_id<br>
                    <strong>Order ID:</strong> $order_id<br>
                    <strong>Amount:</strong> $toRupees rupees<br>
                    <strong>Address:</strong> $address<br>
                    <strong>City:</strong> $city<br><br>
                    We appreciate your business.<br><br>
                    Best regards,<br>
                    One Click Academy
                </body>
                </html>
            ";

            $mail->send();
            echo json_encode(["message" => "Payment successful", "redirect" => "https://oneclickacademy.com/"]);
        } catch (Exception $e) {
            echo json_encode(["message" => "Payment successful, but email sending failed", "error" => $mail->ErrorInfo]);
        }
    } else {
        echo json_encode(["message" => "Payment Failed", "error" => $stmt->error]);
    }
    
    $stmt->close();
} else {
    echo json_encode(["message" => "Payment verification failed"]);
}
?>
