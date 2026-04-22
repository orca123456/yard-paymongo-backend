<?php
require_once 'paymongo_config.php';
require_once 'db.php'; // This must connect to the SAME database used by admin_dashboard.php

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

function logWebhook($message) {
    file_put_contents(
        __DIR__ . '/webhook-log.txt',
        date('Y-m-d H:i:s') . " | " . $message . PHP_EOL,
        FILE_APPEND
    );
}

// ── Verify signature ───────────────────────────────────────────────────────
$parts = parseSignatureHeader($signatureHeader);
$timestamp = $parts['t'] ?? '';
$testSignature = $parts['te'] ?? '';
$liveSignature = $parts['li'] ?? '';

$providedSignature = $liveSignature !== '' ? $liveSignature : $testSignature;

if ($timestamp === '' || $providedSignature === '' || !defined('PAYMONGO_WEBHOOK_SECRET') || !PAYMONGO_WEBHOOK_SECRET) {
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

// ── Decode event ───────────────────────────────────────────────────────────
$data = json_decode($payload, true);
$eventType = $data['data']['attributes']['type'] ?? '';

logWebhook("Event received: {$eventType}");

// ── Handle successful checkout payment ─────────────────────────────────────
if ($eventType === 'checkout_session.payment.paid') {
    $checkoutData = $data['data']['attributes']['data'] ?? [];
    $checkoutId = $checkoutData['id'] ?? '';

    $checkoutAttributes = $checkoutData['attributes'] ?? [];
    $billing = $checkoutAttributes['billing'] ?? [];

    $email = trim($billing['email'] ?? '');
    $name  = trim($billing['name'] ?? '');

    logWebhook("Paid event | checkout_id={$checkoutId} | name={$name} | email={$email}");

    $updated = false;

    // First try: match by checkout_session_id (BEST WAY)
    if ($checkoutId !== '') {
        $stmt = $conn->prepare("
            UPDATE preorders
            SET payment_status = 'paid',
                payment_method = 'QRPH',
                paid_at = NOW()
            WHERE checkout_session_id = ?
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $checkoutId);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $updated = true;
                logWebhook("Updated by checkout_session_id: {$checkoutId}");
            }

            $stmt->close();
        } else {
            logWebhook("Prepare failed for checkout_session_id update: " . $conn->error);
        }
    }

    // Fallback: match the latest unpaid order by email
    if (!$updated && $email !== '') {
        $stmt = $conn->prepare("
            SELECT id
            FROM preorders
            WHERE email = ?
              AND (payment_status = 'unpaid' OR payment_status IS NULL)
            ORDER BY id DESC
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($order && !empty($order['id'])) {
                $orderId = (int)$order['id'];

                $stmt2 = $conn->prepare("
                    UPDATE preorders
                    SET payment_status = 'paid',
                        payment_method = 'QRPH',
                        paid_at = NOW()
                    WHERE id = ?
                    LIMIT 1
                ");

                if ($stmt2) {
                    $stmt2->bind_param("i", $orderId);
                    $stmt2->execute();

                    if ($stmt2->affected_rows > 0) {
                        $updated = true;
                        logWebhook("Updated by email fallback | order_id={$orderId} | email={$email}");
                    }

                    $stmt2->close();
                } else {
                    logWebhook("Prepare failed for fallback update: " . $conn->error);
                }
            } else {
                logWebhook("No unpaid order found for email fallback: {$email}");
            }
        } else {
            logWebhook("Prepare failed for fallback select: " . $conn->error);
        }
    }

    if (!$updated) {
        logWebhook("No matching preorder row updated.");
    }
}

http_response_code(200);
echo json_encode(['message' => 'Webhook received']);
