<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// ── Collect & sanitize inputs ────────────────────────────────
$name    = trim(strip_tags($_POST['name'] ?? ''));
$fbLink  = trim(strip_tags($_POST['fb_link'] ?? ''));
$number  = trim(strip_tags($_POST['number'] ?? ''));
$message = trim(strip_tags($_POST['message'] ?? ''));

// ── Server-side validation ───────────────────────────────────
$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (!preg_match('/^[a-zA-Z\s\x{00C0}-\x{017F}]+$/u', $name)) {
    $errors[] = 'Name should only contain letters and spaces.';
} else {
    $name = mb_convert_case(mb_strtolower($name), MB_CASE_TITLE, 'UTF-8');
}

if ($number === '') {
    $errors[] = 'Contact number is required.';
} elseif (!preg_match('/^09\d{9}$/', $number)) {
    $errors[] = 'Please enter a valid 11-digit contact number (e.g. 09XXXXXXXXX).';
}

if ($message === '') {
    $errors[] = 'Message is required.';
}

// Facebook link is optional, but validate if provided
if ($fbLink !== '' && !preg_match('/facebook\.com/i', $fbLink)) {
    $errors[] = 'Please enter a valid Facebook link.';
}

// Sanitize for storage
$name    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$fbLink  = htmlspecialchars($fbLink, ENT_QUOTES, 'UTF-8');
$number  = htmlspecialchars($number, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

if (!empty($errors)) {
    $errorMsg = implode(' ', $errors);
    echo "<script>alert(" . json_encode($errorMsg) . ");history.back();</script>";
    exit;
}

// ── Ensure contacts table exists ─────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contacts (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            fb_link TEXT,
            number VARCHAR(50),
            message TEXT,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    $stmt = $pdo->prepare("
        INSERT INTO contacts (name, fb_link, number, message)
        VALUES (:name, :fb_link, :number, :message)
    ");

    $stmt->execute([
        ':name'    => $name,
        ':fb_link' => $fbLink,
        ':number'  => $number,
        ':message' => $message
    ]);

    echo "<script>alert('Thank you for contacting us! We will get back to you soon.');window.location='index.php';</script>";

} catch (PDOException $e) {
    error_log('Contact form error: ' . $e->getMessage());
    echo "<script>alert('Something went wrong. Please try again later.');history.back();</script>";
}
?>
