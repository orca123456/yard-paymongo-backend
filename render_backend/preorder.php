<?php
require_once 'paymongo_config.php';

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
$price   = (float)($_POST['price'] ?? 0);

if ($name === '' || $email === '' || $product === '' || $price <= 0) {
    http_response_code(422);
    exit('Missing required fields');
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
            'description' => 'Yard Handicraft Demo Order',
            'line_items' => [
                [
                    'currency' => 'PHP',
                    'amount'   => $amountCentavos,
                    'name'     => $product,
                    'quantity' => 1
                ]
            ],
            'payment_method_types' => ['qrph'],
            'success_url' => APP_URL . '/success.php',
            'cancel_url'  => APP_URL . '/cancel.php',
            'metadata' => [
                'customer_name'  => $name,
                'customer_email' => $email,
                'contact'        => $contact,
                'address'        => $address,
                'fb_link'        => $fbLink,
                'notes'          => $notes,
                'product'        => $product,
                'price'          => $price
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

if (isset($result['data']['attributes']['checkout_url'])) {
    header('Location: ' . $result['data']['attributes']['checkout_url']);
    exit;
}

header('Content-Type: text/plain');
echo "PayMongo error:\n";
print_r($result);
