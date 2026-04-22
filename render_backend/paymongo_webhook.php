<?php
require_once 'paymongo_config.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

function parseSignatureHeader($header) {
    $parts = [];
    foreach (explode(',', $header) as $piece) {
        $segments = explode('=', trim($piece), 2);
        if (count($segments) === 2) {
            $parts[$segments[0]] = $segments[1];
        }
    }
    return $parts;
}

$parts = parseSignatureHeader($signatureHeader);
$timestamp = $parts['t'] ?? '';
$testSignature = $parts['te'] ?? '';
$liveSignature = $parts['li'] ?? '';

$providedSignature = $liveSignature !== '' ? $liveSignature : $testSignature;

if ($timestamp === '' || $providedSignature === '' || !PAYMONGO_WEBHOOK_SECRET) {
    http_response_code(400);
    echo json_encode(['message' => 'Missing signature data']);
    exit;
}

$expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, PAYMONGO_WEBHOOK_SECRET);

if (!hash_equals($expectedSignature, $providedSignature)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid signature']);
    exit;
}

$data = json_decode($payload, true);
$eventType = $data['data']['attributes']['type'] ?? '';

file_put_contents(
    __DIR__ . '/webhook-log.txt',
    date('Y-m-d H:i:s') . " | " . $eventType . " | " . $payload . PHP_EOL . PHP_EOL,
    FILE_APPEND
);

if ($eventType === 'checkout_session.payment.paid') {
    $checkoutId = $data['data']['attributes']['data']['id'] ?? '';
    $billing = $data['data']['attributes']['data']['attributes']['billing'] ?? [];
    $email = $billing['email'] ?? '';
    $name = $billing['name'] ?? '';

    file_put_contents(
        __DIR__ . '/paid-orders.txt',
        date('Y-m-d H:i:s') . " | checkout_id=" . $checkoutId . " | name=" . $name . " | email=" . $email . PHP_EOL,
        FILE_APPEND
    );
}

http_response_code(200);
echo json_encode(['message' => 'Webhook received']);
