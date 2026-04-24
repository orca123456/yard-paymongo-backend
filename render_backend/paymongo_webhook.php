<?php
require_once 'paymongo_config.php';
require_once 'db.php';

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

function parseSignatureHeader($header)
{
    $parts = [];

    foreach (explode(',', $header) as $piece) {
        $segments = explode('=', trim($piece), 2);

        if (count($segments) === 2) {
            $parts[$segments[0]] = $segments[1];
        }
    }

    return $parts;
}

function logWebhook($message)
{
    file_put_contents(
        __DIR__ . '/webhook-log.txt',
        date('Y-m-d H:i:s') . " | " . $message . PHP_EOL,
        FILE_APPEND
    );
}

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
} catch (PDOException $e) {
    logWebhook('Table setup failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Table setup failed']);
    exit;
}

$parts = parseSignatureHeader($signatureHeader);
$timestamp = $parts['t'] ?? '';
$testSignature = $parts['te'] ?? '';
$liveSignature = $parts['li'] ?? '';

$providedSignature = $liveSignature !== '' ? $liveSignature : $testSignature;

if ($timestamp === '' || $providedSignature === '' || !PAYMONGO_WEBHOOK_SECRET) {
    logWebhook('Missing signature data');
    http_response_code(400);
    echo json_encode(['message' => 'Missing signature data']);
    exit;
}

$expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, PAYMONGO_WEBHOOK_SECRET);

if (!hash_equals($expectedSignature, $providedSignature)) {
    logWebhook('Invalid signature');
    http_response_code(400);
    echo json_encode(['message' => 'Invalid signature']);
    exit;
}

$data = json_decode($payload, true);
$eventType = $data['data']['attributes']['type'] ?? '';

logWebhook("Event received: {$eventType}");

if ($eventType === 'checkout_session.payment.paid') {
    $checkoutData = $data['data']['attributes']['data'] ?? [];
    $checkoutId = $checkoutData['id'] ?? '';

    $checkoutAttributes = $checkoutData['attributes'] ?? [];
    $billing = $checkoutAttributes['billing'] ?? [];
    $payments = $checkoutAttributes['payments'] ?? [];

    $email = trim($billing['email'] ?? '');
    $paymentId = $payments[0]['id'] ?? null;

    $metadata = $checkoutAttributes['metadata'] ?? [];
    $orderIdFromMetadata = isset($metadata['order_id']) ? (int) $metadata['order_id'] : 0;

    logWebhook("Paid event | checkout_id={$checkoutId} | email={$email} | order_id={$orderIdFromMetadata}");

    $updated = false;

    if ($orderIdFromMetadata > 0) {
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET payment_status = 'paid',
                payment_method = 'QRPH',
                paymongo_payment_id = :payment_id,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':payment_id' => $paymentId,
            ':id' => $orderIdFromMetadata
        ]);

        if ($stmt->rowCount() > 0) {
            $updated = true;
            logWebhook("Updated by metadata order_id: {$orderIdFromMetadata}");
        }
    }

    if (!$updated && $checkoutId !== '') {
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET payment_status = 'paid',
                payment_method = 'QRPH',
                paymongo_payment_id = :payment_id,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE paymongo_checkout_id = :checkout_id
        ");

        $stmt->execute([
            ':payment_id' => $paymentId,
            ':checkout_id' => $checkoutId
        ]);

        if ($stmt->rowCount() > 0) {
            $updated = true;
            logWebhook("Updated by checkout id: {$checkoutId}");
        }
    }

    if (!$updated && $email !== '') {
        $stmt = $pdo->prepare("
            UPDATE preorders
            SET payment_status = 'paid',
                payment_method = 'QRPH',
                paymongo_payment_id = :payment_id,
                paid_at = NOW(),
                updated_at = NOW()
            WHERE id = (
                SELECT id
                FROM preorders
                WHERE email = :email
                  AND payment_status = 'pending'
                ORDER BY id DESC
                LIMIT 1
            )
        ");

        $stmt->execute([
            ':payment_id' => $paymentId,
            ':email' => $email
        ]);

        if ($stmt->rowCount() > 0) {
            $updated = true;
            logWebhook("Updated by email fallback: {$email}");
        }
    }

    if (!$updated) {
        logWebhook('No matching preorder updated.');
    }
}

if ($eventType === 'payment.failed') {
    logWebhook('Payment failed event received.');
}

http_response_code(200);
echo json_encode(['message' => 'Webhook received']);
