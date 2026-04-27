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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price NUMERIC(10, 2) DEFAULT 0,
            old_price NUMERIC(10, 2) DEFAULT 0,
            category VARCHAR(100) DEFAULT 'Satin Flowers',
            image VARCHAR(255),
            stock INTEGER DEFAULT 10,
            discount_label VARCHAR(20),
            created_at TIMESTAMPTZ DEFAULT NOW()
        )
    ");

    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS email VARCHAR(150)");
    $pdo->exec("ALTER TABLE preorders ADD COLUMN IF NOT EXISTS quantity INTEGER DEFAULT 1");
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

    // Seed products if table is empty
    $productCount = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($productCount === 0) {
        $seedStmt = $pdo->prepare("
            INSERT INTO products (name, description, price, old_price, category, image, stock, discount_label)
            VALUES (:name, :description, :price, :old_price, :category, :image, :stock, :discount_label)
        ");

        $seedProducts = [
            ['name' => 'Satin Flower Pot - Rose Elegance', 'description' => 'Elegant rose arrangement perfect for home decor', 'price' => 1.00, 'old_price' => 50.00, 'category' => 'Satin Flowers', 'image' => 'images/img-1.jpg', 'stock' => 10, 'discount_label' => '-10%'],
            ['name' => 'Satin Flower Pot - Davao Delight', 'description' => 'Vibrant Davao-inspired floral centerpiece', 'price' => 1.00, 'old_price' => 10.00, 'category' => 'Satin Flowers', 'image' => 'images/img-2.jpg', 'stock' => 10, 'discount_label' => '-15%'],
            ['name' => 'Satin Flower Pot - Blush Bouquet', 'description' => 'Soft blush arrangement for special occasions', 'price' => 1.00, 'old_price' => 100.00, 'category' => 'Satin Flowers', 'image' => 'images/img-3.jpg', 'stock' => 10, 'discount_label' => '-8%'],
            ['name' => 'Satin Flower Pot - Sunburst', 'description' => 'Bright and cheerful sunburst design', 'price' => 1.00, 'old_price' => 10.00, 'category' => 'Satin Flowers', 'image' => 'images/img-4.jpg', 'stock' => 10, 'discount_label' => '-12%'],
            ['name' => 'Satin Flower Pot - Lavender Dream', 'description' => 'Premium lavender-themed floral arrangement', 'price' => 1200.00, 'old_price' => 1340.00, 'category' => 'Satin Flowers', 'image' => 'images/image-5.png', 'stock' => 10, 'discount_label' => '-10%'],
        ];

        foreach ($seedProducts as $sp) {
            $seedStmt->execute($sp);
        }
        echo "<p>✅ Seeded " . count($seedProducts) . " products.</p>";
    } else {
        echo "<p>ℹ️ Products table already has $productCount products. Skipping seed.</p>";
    }

    echo "<h2>Database schema fixed successfully.</h2>";
    echo "<p>All tables and columns are up to date.</p>";
    echo "<p><strong>Important:</strong> Delete this fix_schema.php file after running it.</p>";
    echo '<a href="admin_dashboard.php">Go to Admin Dashboard</a>';

} catch (PDOException $e) {
    error_log("Schema fix failed: " . $e->getMessage());
    echo "<h2>Database schema fix failed.</h2>";
    echo "<p>Please check server logs for details.</p>";
}
?>
