CREATE TABLE IF NOT EXISTS admins (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

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
);

CREATE TABLE IF NOT EXISTS contacts (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    fb_link TEXT,
    number VARCHAR(50),
    message TEXT,
    created_at TIMESTAMPTZ DEFAULT NOW()
);

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
);

ALTER TABLE preorders ADD COLUMN IF NOT EXISTS email VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS quantity INTEGER DEFAULT 1;
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paid_at TIMESTAMPTZ;
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_checkout_id VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS paymongo_payment_id VARCHAR(150);
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS payment_status VARCHAR(30) DEFAULT 'pending';
ALTER TABLE preorders ADD COLUMN IF NOT EXISTS updated_at TIMESTAMPTZ DEFAULT NOW();

-- Seed products (only if table is empty)
INSERT INTO products (name, description, price, old_price, category, image, stock, discount_label)
SELECT * FROM (VALUES
    ('Satin Flower Pot - Rose Elegance', 'Elegant rose arrangement perfect for home decor', 1.00, 50.00, 'Satin Flowers', 'images/img-1.jpg', 10, '-10%'),
    ('Satin Flower Pot - Davao Delight', 'Vibrant Davao-inspired floral centerpiece', 1.00, 10.00, 'Satin Flowers', 'images/img-2.jpg', 10, '-15%'),
    ('Satin Flower Pot - Blush Bouquet', 'Soft blush arrangement for special occasions', 1.00, 100.00, 'Satin Flowers', 'images/img-3.jpg', 10, '-8%'),
    ('Satin Flower Pot - Sunburst', 'Bright and cheerful sunburst design', 1.00, 10.00, 'Satin Flowers', 'images/img-4.jpg', 10, '-12%'),
    ('Satin Flower Pot - Lavender Dream', 'Premium lavender-themed floral arrangement', 1200.00, 1340.00, 'Satin Flowers', 'images/image-5.png', 10, '-10%')
) AS seed(name, description, price, old_price, category, image, stock, discount_label)
WHERE NOT EXISTS (SELECT 1 FROM products LIMIT 1);
