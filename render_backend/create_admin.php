<?php
require_once 'db.php';

$message = '';
$error = '';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id SERIAL PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

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

    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS email VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS quantity INTEGER DEFAULT 1");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMPTZ");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_checkout_id VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_payment_id VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ DEFAULT NOW()");

    $username = 'admin';
    $password = 'admin123';

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([
        ':username' => $username
    ]);

    $existingAdmin = $stmt->fetch();

    if ($existingAdmin) {
        $message = "Tables checked successfully. Admin account already exists. Password was not changed.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO admins (username, password)
            VALUES (:username, :password)
        ");

        $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword
        ]);

        $message = "Tables created and admin account created successfully.";
    }

} catch (PDOException $e) {
    error_log('Create admin error: ' . $e->getMessage());
    $error = 'Setup failed. Please check server logs.';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            padding: 40px;
        }

        .box {
            background: white;
            padding: 30px;
            max-width: 600px;
            margin: auto;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            background: #333;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 6px;
        }

        a:hover {
            background: #e84393;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Database Setup</h2>

    <?php if ($message): ?>
        <p class="success"><?= htmlspecialchars($message) ?></p>
        <p>Tables checked:</p>
        <ul>
            <li>admins</li>
            <li>preorders</li>
        </ul>

        <p><strong>Default username:</strong> admin</p>
        <p><strong>Default password if newly created:</strong> admin123</p>

        <a href="admin_login.php">Go to Admin Login</a>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">Error: <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
