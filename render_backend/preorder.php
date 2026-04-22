<?php
require_once 'paymongo_config.php';
require_once 'db.php'; // must connect to the SAME database used by admin_dashboard.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

function columnExists(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

$hasEmail = columnExists($conn, 'preorders', 'email');
$hasPaymentStatus = columnExists($conn, 'preorders', 'payment_status');
$hasPaidAt = columnExists($conn, 'preorders', 'paid_at');
$hasCheckoutSessionId = columnExists($conn, 'preorders', 'checkout_session_id');
$hasPaymentMethod = columnExists($conn, 'preorders', 'payment_method');

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$fbLink  = trim($_POST['fb_link'] ?? '');
$notes   = trim($_POST['notes'] ?? '');
$product = trim($_POST['product'] ?? '');
$price   = (float)($_POST['price'] ?? 0);

if ($name === '' || $email === '' || $product === '' || $price <= 0) {
    http_response_code(422);
    exit('Missing required fields');
}

$amountCentavos = (int) round($price * 100);

// 1) Save order first in database
$orderId = 0;

if ($hasEmail && $hasPaymentStatus && $hasPaymentMethod) {
    $stmt = $conn->prepare("
        INSERT INTO preorders
        (name, email, address, contact, fb_link, product, price, payment_method, order_status, notes, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'QRPH', 'pending', ?, 'unpaid')
    ");
    $stmt->bind_param("ssssssdss", $name, $email, $address, $contact, $fbLink, $product, $price, $notes);
} elseif ($hasEmail) {
    $stmt = $conn->prepare("
        INSERT INTO preorders
        (name, email, address, contact, fb_link, product, price, order_status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->bind_param("ssssssd s", $name, $email, $address, $contact, $fbLink, $product, $price, $notes);
} else {
    $stmt = $conn->prepare("
        INSERT INTO preorders
        (name, address, contact, fb_link, product, price, order_status, notes)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
    ");
    $stmt->bind_param("sssssds", $name, $address, $contact, $fbLink, $product, $price, $notes);
}

if (!$stmt) {
    http_response_code(500);
    exit('Failed to prepare database insert: ' . $conn->error);
}

$stmt->execute();

if ($stmt->affected_rows > 0) {
    $orderId = (int)$stmt->insert_id;
}
$stmt->close();

if ($orderId <= 0) {
    http_response_code(500);
    exit('Failed to save preorder in database');
}

// 2) Create PayMongo checkout session
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
            'description' => 'Yard Handicraft Order #' . $orderId,
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount'   => $amountCentavos,
                    'name'     => $product,
                    'quantity' => 1
                ]
            ],
            'payment_method_types' => ['qrph'],
            'success_url' => APP_URL . '/success.php?order_id=' . urlencode((string)$orderId),
            'cancel_url'  => APP_URL . '/cancel.php?order_id=' . urlencode((string)$orderId),
            'metadata' => [
                'local_order_id' => (string)$orderId,
                'customer_email' => $email,
                'product'        => $product
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
    // optional: mark failed if available
    if ($hasPaymentStatus) {
        $stmt = $conn->prepare("UPDATE preorders SET payment_status = 'failed' WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $stmt->close();
        }
    }

    http_response_code(500);
    exit('Failed to connect to PayMongo');
}

$result = json_decode($response, true);

// 3) Save checkout_session_id if available
if (isset($result['data']['id']) && $hasCheckoutSessionId) {
    $checkoutSessionId = $result['data']['id'];

    $stmt = $conn->prepare("UPDATE preorders SET checkout_session_id = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $checkoutSessionId, $orderId);
        $stmt->execute();
        $stmt->close();
    }
}

// 4) Redirect to PayMongo checkout page
if (isset($result['data']['attributes']['checkout_url'])) {
    header('Location: ' . $result['data']['attributes']['checkout_url']);
    exit;
}

// optional: mark failed if checkout creation failed
if ($hasPaymentStatus) {
    $stmt = $conn->prepare("UPDATE preorders SET payment_status = 'failed' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $stmt->close();
    }
}

header('Content-Type: text/plain');
echo "PayMongo error:\n";
print_r($result);
