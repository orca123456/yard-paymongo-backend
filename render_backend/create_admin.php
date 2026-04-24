<?php
require_once 'db.php';

$message = '';
$error = '';

try {
    // Create admins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id SERIAL PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    // Create preorders table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS preorders (
            id SERIAL PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
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
            created_at TIMESTAMPTZ DEFAULT NOW(),
            updated_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    // Change this if you want
    $username = 'admin';
    $password = 'admin123';

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = :username LIMIT 1");
    $stmt->execute([
        ':username' => $username
    ]);

    $existingAdmin = $stmt->fetch();

    if ($existingAdmin) {
        $stmt = $pdo->prepare("
            UPDATE admins
            SET password = :password
            WHERE username = :username
        ");

        $stmt->execute([
            ':password' => $hashedPassword,
            ':username' => $username
        ]);

        $message = "Admin account already existed. Password has been reset.";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO admins (username, password)
            VALUES (:username, :password)
        ");

        $stmt->execute([
            ':username' => $username,
            ':password' => $hashedPassword
        ]);

        $message = "Admin account created successfully.";
    }

} catch (PDOException $e) {
    $error = $e->getMessage();
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
        <p>Tables created:</p>
        <ul>
            <li>admins</li>
            <li>preorders</li>
        </ul>

        <p><strong>Username:</strong> admin</p>
        <p><strong>Password:</strong> admin123</p>

        <a href="admin_login.php">Go to Admin Login</a>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error">Error: <?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
