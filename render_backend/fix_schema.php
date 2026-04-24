<?php
require_once 'db.php';

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

    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS email VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS contact VARCHAR(50)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS fb_link TEXT");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS address TEXT");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS product VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS price NUMERIC(10, 2) DEFAULT 0");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS notes TEXT");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS payment_method VARCHAR(100)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS order_status VARCHAR(30) DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) DEFAULT 'pending'");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_checkout_id VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_payment_id VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMPTZ");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS created_at TIMESTAMPTZ DEFAULT NOW()");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ DEFAULT NOW()");

    echo "<h2>Database schema fixed successfully.</h2>";
    echo "<p>You can now test the preorder/payment again.</p>";
    echo "<p><strong>Important:</strong> Delete this fix_schema.php file after running it.</p>";
    echo '<a href="admin_dashboard.php">Go to Admin Dashboard</a>';

} catch (PDOException $e) {
    echo "<h2>Database schema fix failed.</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
