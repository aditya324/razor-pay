<?php
header("Content-Type: application/json");
require 'vendor/autoload.php';
require 'config.php';

// API key check
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
if ($apiKey !== 'hDRFkvaUct0SONDDFzMjyQHC') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$couponCode = $input['couponCode'] ?? null;
$currentAmount = $input['amount'] ?? null;

if (!$couponCode || !$currentAmount) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required fields"]);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Lock coupon row for update
    $stmt = $conn->prepare("
        SELECT * FROM coupons 
        WHERE code = ? 
        AND is_active = TRUE
        AND start_date <= NOW() 
        AND (end_date >= NOW() OR end_date IS NULL)
        FOR UPDATE
    ");
    $stmt->bind_param("s", $couponCode);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();

    if (!$coupon) {
        throw new Exception("Invalid or expired coupon code");
    }

    // Check usage limits
    if ($coupon['is_single_use'] && $coupon['current_uses'] >= $coupon['max_uses']) {
        throw new Exception("This coupon has already been used");
    }

    // Check minimum order amount
    if ($coupon['min_order_amount'] && $currentAmount < $coupon['min_order_amount']) {
        throw new Exception("Order amount too low for this coupon");
    }

    // Calculate discount
    if ($coupon['discount_type'] === 'percentage') {
        $discountAmount = $currentAmount * ($coupon['discount'] / 100);
        $discountedAmount = $currentAmount - $discountAmount;
    } else {
        $discountAmount = min($coupon['discount'], $currentAmount);
        $discountedAmount = $currentAmount - $discountAmount;
    }

    $conn->commit();

    echo json_encode([
        "valid" => true,
        "discount_amount" => $discountAmount,
        "discounted_amount" => $discountedAmount,
        "coupon_code" => $coupon['code'],
        "message" => "Coupon applied successfully"
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "valid" => false,
        "message" => $e->getMessage()
    ]);
}
?>