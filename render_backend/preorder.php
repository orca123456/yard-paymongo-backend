<?php
require_once 'paymongo_config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$fbLink  = trim($_POST['fb_link'] ?? '');
$notes   = trim($_POST['notes'] ?? '');
$product = trim($_POST['product'] ?? '');
$price   = (float) ($_POST['price'] ?? 0);

if ($name === '' || $email === '' || $product === '' || $price <= 0) {
    http_response_code(422);
    exit('Missing required fields');
}

if (!PAYMONGO_SECRET_KEY) {
    http_response_code(500);
    exit('PAYMONGO_SECRET_KEY is missing in Render environment variables.');
}

$appUrl = APP_URL ?: 'https://yardhandicraft.onrender.com';

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
        (name, email, contact, fb_link, address, product, price, notes, payment_method, order_status, payment_status)
        VALUES
        (:name, :email, :contact, :fb_link, :address, :product, :price, :notes, 'QRPH', 'pending', 'pending')
        RETURNING id
    ");

    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':contact' => $contact,
        ':fb_link' => $fbLink,
        ':address' => $address,
        ':product' => $product,
        ':price' => $price,
        ':notes' => $notes
    ]);

    $orderId = (int) $stmt->fetchColumn();

} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error while saving order: ' . $e->getMessage());
}

$amountCentavos = (int) round($price * 100);

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
                'price'          => (string) $price
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
    http_response_code(500);
    exit('Failed to connect to PayMongo');
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

http_response_code(500);
header('Content-Type: text/plain');
echo "PayMongo error:\n";
print_r($result);
