<?php
require_once 'paymongo_config.php';
require_once 'db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// ── Collect & trim inputs ────────────────────────────────────
$name     = trim(strip_tags($_POST['name'] ?? ''));
$email    = trim(strip_tags($_POST['email'] ?? ''));
$address  = trim(strip_tags($_POST['address'] ?? ''));
$contact  = trim(strip_tags($_POST['contact'] ?? ''));
$fbLink   = trim(strip_tags($_POST['fb_link'] ?? ''));
$notes    = trim(strip_tags($_POST['notes'] ?? ''));
$product  = trim(strip_tags($_POST['product'] ?? ''));
$price    = (float) ($_POST['price'] ?? 0);
$quantity = (int) ($_POST['quantity'] ?? 1);

// ── Server-side validation ───────────────────────────────────
$errors = [];

// Name: required, letters and spaces only, then format
if ($name === '') {
    $errors[] = 'Full name is required.';
} elseif (!preg_match('/^[a-zA-Z\s\x{00C0}-\x{017F}]+$/u', $name)) {
    $errors[] = 'Name should only contain letters and spaces.';
} else {
    $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
}

// Email: required, valid format
if ($email === '') {
    $errors[] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

// Address: required, clean up extra spaces
if ($address === '') {
    $errors[] = 'Delivery address is required.';
} else {
    $address = preg_replace('/\s{2,}/', ' ', $address);
}

// Contact: required, exactly 11 digits starting with 09
if ($contact === '') {
    $errors[] = 'Contact number is required.';
} elseif (!preg_match('/^09\d{9}$/', $contact)) {
    $errors[] = 'Please enter a valid 11-digit contact number (e.g. 09XXXXXXXXX).';
}

// Quantity: must be positive integer
if ($quantity < 1) {
    $quantity = 1;
}

// Facebook link: optional, but if provided must be facebook.com
if ($fbLink !== '' && !preg_match('/facebook\.com/i', $fbLink)) {
    $errors[] = 'Please enter a valid Facebook profile link.';
}

// Product & price: required
if ($product === '' || $price <= 0) {
    $errors[] = 'Product information is missing.';
}

// Sanitize notes
$notes = htmlspecialchars($notes, ENT_QUOTES, 'UTF-8');

// If validation failed, show user-friendly error page
if (!empty($errors)) {
    http_response_code(422);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>Order Error - YardHandicraft</title>';
    echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
    echo '<style>*{margin:0;padding:0;box-sizing:border-box;font-family:Verdana,Geneva,Tahoma,sans-serif;}';
    echo 'body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#fff5f5;padding:2rem;}';
    echo '.card{max-width:500px;width:100%;background:#fff;border-radius:1.5rem;box-shadow:0 .8rem 2rem rgba(0,0,0,.08);padding:3rem 2.5rem;text-align:center;}';
    echo '.icon{width:80px;height:80px;margin:0 auto 1.5rem;border-radius:50%;background:rgba(231,76,60,.12);color:#e74c3c;display:flex;align-items:center;justify-content:center;font-size:3.5rem;}';
    echo 'h1{font-size:2.4rem;color:#333;margin-bottom:1rem;}';
    echo 'ul{text-align:left;margin:1.5rem 0;padding-left:2rem;}';
    echo 'li{font-size:1.4rem;color:#666;padding:.5rem 0;}';
    echo '.btn{display:inline-block;padding:1rem 2.5rem;border-radius:5rem;font-size:1.4rem;text-decoration:none;background:#333;color:#fff;margin-top:1.5rem;}';
    echo '.btn:hover{background:#e84393;}</style></head><body>';
    echo '<div class="card"><div class="icon"><i class="fas fa-exclamation-triangle"></i></div>';
    echo '<h1>Please Fix the Following</h1><ul>';
    foreach ($errors as $err) {
        echo '<li>' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</li>';
    }
    echo '</ul><a href="javascript:history.back();" class="btn">Go Back & Fix</a></div></body></html>';
    exit;
}

// ── Check PayMongo key ───────────────────────────────────────
if (!PAYMONGO_SECRET_KEY) {
    error_log('PAYMONGO_SECRET_KEY is missing');
    http_response_code(500);
    exit('Payment service is temporarily unavailable. Please try again later.');
}

$appUrl = APP_URL ?: 'https://yardhandicraft.onrender.com';

// ── Ensure table exists & insert order ───────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS preorders (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150),
            contact VARCHAR(50),
            fb_link TEXT,
            address TEXT,
            product VARCHAR(150),
            price NUMERIC(10, 2) DEFAULT 0,
            quantity INTEGER DEFAULT 1,
            notes TEXT,
            payment_method VARCHAR(100),
            order_status VARCHAR(30) DEFAULT 'pending',
            payment_status VARCHAR(30) DEFAULT 'pending',
            paymongo_checkout_id VARCHAR(150),
            paymongo_payment_id VARCHAR(150),
            paid_at TIMESTAMPTZ,
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    $stmt = $pdo->prepare("
        INSERT INTO preorders
        (name, email, contact, fb_link, address, product, price, quantity, notes, payment_method, order_status, payment_status)
        VALUES
        (:name, :email, :contact, :fb_link, :address, :product, :price, :quantity, :notes, 'QRPH', 'pending', 'pending')
        RETURNING id
    ");

    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':contact'  => $contact,
        ':fb_link'  => $fbLink,
        ':address'  => $address,
        ':product'  => $product,
        ':price'    => $price,
        ':quantity' => $quantity,
        ':notes'    => $notes
    ]);

    $orderId = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    error_log('Database error in preorder.php: ' . $e->getMessage());
    http_response_code(500);
    exit('Something went wrong while processing your order. Please try again later.');
}

// ── Create PayMongo checkout session ─────────────────────────
$amountCentavos = (int) round($price * $quantity * 100);

$payload = [
    'data' => [
        'attributes' => [
            'billing' => [
                'name'  => $name,
                'email' => $email
            ],
            'send_email_receipt' => true,
            'show_description' => true,
            'show_line_items' => true,
            'description' => 'YardHandicraft Order #' . $orderId,
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount'   => $amountCentavos,
                    'name'     => $product,
                    'quantity' => $quantity
                ]
            ],
            'payment_method_types' => ['qrph'],
            'success_url' => $appUrl . '/success.php?order_id=' . $orderId,
            'cancel_url'  => $appUrl . '/cancel.php?order_id=' . $orderId,
            'metadata' => [
                'order_id'       => (string) $orderId,
                'customer_name'  => $name,
                'customer_email' => $email,
                'contact'        => $contact,
                'address'        => $address,
                'fb_link'        => $fbLink,
                'notes'          => $notes,
                'product'        => $product,
                'price'          => (string) $price,
                'quantity'       => (string) $quantity
            ]
        ]
    ]
];

$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
        ]),
        'content' => json_encode($payload),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = file_get_contents('https://api.paymongo.com/v1/checkout_sessions', false, $context);

if ($response === false) {
    error_log('Failed to connect to PayMongo');
    http_response_code(500);
    exit('Payment service is temporarily unavailable. Please try again later.');
}

$result = json_decode($response, true);

if (isset($result['data']['id'], $result['data']['attributes']['checkout_url'])) {
    $checkoutId = $result['data']['id'];

    $stmt = $pdo->prepare("
        UPDATE preorders
        SET paymongo_checkout_id = :checkout_id,
            updated_at = NOW()
        WHERE id = :id
    ");

    $stmt->execute([
        ':checkout_id' => $checkoutId,
        ':id' => $orderId
    ]);

    header('Location: ' . $result['data']['attributes']['checkout_url']);
    exit;
}

error_log('PayMongo error: ' . print_r($result, true));
http_response_code(500);
exit('Payment service encountered an error. Please try again later.');
